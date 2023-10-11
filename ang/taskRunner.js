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
      CRM.$('body').addClass('sql-task-body-page-wrapper');
    }, 0);

    $scope.taskId = taskId;
    $scope.ts = CRM.ts();
    $scope.resultLogs = [];
    $scope.isShowLogs = false;
    $scope.isTaskRunning = false;
    $scope.isTaskLoading = true;
    $scope.isTaskSuccessfullyLoaded = false;
    $scope.runButtonText = $scope.ts('Run task');
    $scope.task = null;
    $scope.inputValue = '';
    $scope.errors = [];

    $scope.loadTask = function() {
      CRM.api3("Sqltask", "get", {
        sequential: 1,
        id: $scope.taskId
      }).done(function(result) {
        if (!result.is_error) {
          $scope.task = Object.assign({}, result.values);
          $scope.isTaskSuccessfullyLoaded = true;
        } else {
          $scope.showError('Getting task api returns error: ' + result.error_message);
        }

        $scope.isTaskLoading = false;
        $scope.$apply();
      });
    }

    $scope.runTask = function() {
      $scope.cleanErrors();

      if ($scope.isInputValueRequired()) {
        if ($scope.inputValue.length === 0) {
          $scope.showError('Input value is required');
        }
      }

      if ($scope.errors.length > 0 ) {
        return;
      }

      const data = {
        task_id: taskId,
        async: true,
      };

      if ($scope.isInputValueRequired()) {
        data['input_val'] = $scope.inputValue;
      }

      $scope.isShowLogs = true;
      $scope.resultLogs = [];

      new Promise((resolve, reject) => {
        CRM.api3("Sqltask", "execute", data).done(resolve).fail(reject);
      }).then(async (result) => {
        if (result.is_error !== 0) throw new Error(result.error_message);

        CRM.alert("Task execution added to background queue", "Task queued", "info");

        $scope.isTaskRunning = true;
        $scope.$apply();

        while ($scope.isTaskRunning) {
          await new Promise(resolve => setTimeout(resolve, 2000));

          const [execution] = await CRM.api4("SqltasksExecution", "get", {
            where: [[ "sqltask_id", "=", taskId ]],
            orderBy: { "start_date": "DESC" },
            limit: 1,
          }).catch(({ error_message }) => {
            throw new Error(error_message);
          });

          $scope.resultLogs = JSON
            .parse(execution.log)
            .map(({ message, message_type: type }) => `${type}: [Task ${taskId}] ${message}`);

          $scope.isTaskRunning = execution.end_date === null;
          $scope.$apply();
        }

        CRM.alert("Task execution completed", "Task execution", 'success');
      }).catch($scope.handleExecutionError);
    };

    $scope.showError = function(message) {
      $scope.errors.push(message);
    };

    $scope.cleanErrors = function() {
      $scope.errors = [];
    };

    $scope.handleExecutionError = function (error) {
      console.error(error);

      CRM.alert(ts(error.message), ts("Execution error"), "error");

      $scope.resultLogs.push(`Error: ${error.message}`);
      $scope.isTaskRunning = false;
      $scope.runButtonText = $scope.ts("Run again");
      $scope.$apply();
    }

    $scope.isInputValueRequired = function() {
      return $scope.task['input_required'] === 1 || $scope.task['input_required'] === '1';
    }

    $scope.loadTask();
  });
})(angular, CRM.$, CRM._);
