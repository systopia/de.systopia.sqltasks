(function(angular, $, _) {
  var moduleName = "taskRunner";
  var moduleDependencies = ["ngRoute"];
  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/run/:tid", {
        controller: "taskRunnerCtrl",
        templateUrl: "~/taskRunner/taskRunner.html",
        resolve: {
          taskId: function($route) {
            return $route.current.params.tid;
          },
        }
      });
    }
  ]);

  angular.module(moduleName).controller("taskRunnerCtrl", function($scope, $location, taskId) {
    setTimeout(function() {
      CRM.$("body").addClass("sql-task-body-page-wrapper");
    }, 0);

    $scope.backgroundQueueEnabled = false;
    $scope.errors = [];
    $scope.inputRequired = false;
    $scope.inputValue = "";
    $scope.logs = [];
    $scope.runButtonLabel = "Run Task";
    $scope.state = null;
    $scope.taskId = taskId;
    $scope.task = null;
    $scope.ts = CRM.ts();

    $scope.allowedToRun = () => {
      return $scope.task !== null
        && !["loading", "running"].includes($scope.state)
        && (!$scope.inputRequired || $scope.inputValue.length > 0);
    }

    $scope.api3 = (entity, action, params) => new Promise((resolve, reject) => {
      CRM.api3(entity, action, params).done(resolve).fail(reject);
    });

    $scope.checkBackgroundQueueEnabled = () => {
      return CRM.api4("Setting", "get", {
        select: ["enableBackgroundQueue"]
      }).catch((error) => {
        console.error(error);
        return [];
      }).then((result) => {
        $scope.backgroundQueueEnabled = result.at(0)?.value == 1;
      });
    };

    $scope.loadTask = () => {
      $scope.state = "loading";

      return $scope.api3("Sqltask", "get", {
        id: $scope.taskId,
        sequential: 1,
      }).then((result) => {
        if (result.is_error) {
          throw new Error(result.error_message);
        } else {
          $scope.state = "loaded";
          $scope.task = structuredClone(result.values);
          $scope.inputRequired = [1, "1"].includes($scope.task["input_required"]);
        }
      }).catch((error) => {
        CRM.alert("Failed to load task", "Error", "error");
        $scope.state = "error";
        $scope.errors.push(error);
      });
    }

    $scope.runTask = async () => {
      $scope.errors = [];
      $scope.logs = [];
      $scope.state = "running";

      if ($scope.inputRequired && $scope.inputValue.length < 1) {
        $scope.state = "error";
        $scope.errors.push(new Error("Input value is required"));
        // $scope.$apply();
        return;
      }

      const taskExecResult = await $scope.api3("Sqltask", "execute", {
        async: $scope.backgroundQueueEnabled,
        input_val: $scope.inputValue,
        task_id: taskId,
      }).then((result) => {
        if (result.is_error) throw new Error(result.error_message);
        return result;
      }).catch((error) => {
        console.error(error);
        $scope.state = "error";
        $scope.errors.push(error);
      });

      if ($scope.state === "error") {
        CRM.alert("Task execution failed", "Task execution", "error");
        $scope.runButtonLabel = "Run again";
        $scope.$apply();
        return;
      } else if (!$scope.backgroundQueueEnabled){
        if (taskExecResult.values.error_count > 0) {
          CRM.alert(
            "Task execution encountered errors. See execution logs for more details",
            "Task execution",
            "error"
          );
        } else {
          CRM.alert("Task execution completed", "Task execution", "success");
        }

        $scope.state = "done";
        $scope.logs = taskExecResult.values.logs;
        $scope.runButtonLabel = "Run again";
        $scope.$apply();
        return;
      }

      while ($scope.state === "running") {
        await new Promise(resolve => setTimeout(resolve, 2000));

        const execution = await CRM.api4("SqltasksExecution", "get", {
          where: [[ "id", "=", taskExecResult.values.execution_id ]],
          limit: 1,
        }).then((result) => {
          return result.at(0);
        }).catch(({ error_message }) => {
          $scope.state = "error";
          $scope.errors.push(new Error(error_message));
        });

        if ($scope.state === "error") {
          CRM.alert("Failed to load execution logs", "Error", "error");
          $scope.runButtonLabel = "Run again";
          $scope.$apply();
          break;
        }

        $scope.logs = JSON
          .parse(execution.log)
          .map(({ message, message_type: type }) => `${type}: [Task ${taskId}] ${message}`);

        $scope.state = execution.end_date === null ? "running" : "done";
        $scope.runButtonLabel = $scope.state === "done" ? "Run again" : $scope.runButtonLabel;
        $scope.$apply();

        if ($scope.state === "done") {
          if (execution.error_count > 0) {
            CRM.alert(
              "Task execution encountered errors. See execution logs for more details",
              "Task execution",
              "error"
            );
          } else {
            CRM.alert("Task execution completed", "Task execution", "success");
          }
        }
      }
    };

    Promise.all([
      $scope.checkBackgroundQueueEnabled(),
      $scope.loadTask(),
    ]).then(() => $scope.$apply());
  });
})(angular, CRM.$, CRM._);
