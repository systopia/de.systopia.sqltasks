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
    $scope.isTaskReturnsEmptyLogs = false;
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

      var data = {}
      data['task_id'] = taskId;

      if ($scope.isInputValueRequired()) {
        data['input_val'] = $scope.inputValue;
      }

      CRM.alert("Task execution has started", "Task execution", 'info');
      $scope.isTaskRunning = true;

      CRM.api3("Sqltask", "execute", data).done(function(result) {
        if (result.values && !result.is_error) {
          if (result.values.log !== undefined && Array.isArray(result.values.log)) {
            $scope.resultLogs = result.values.log;
            $scope.isTaskReturnsEmptyLogs = $scope.resultLogs.length  === 0;
          } else {
            $scope.isTaskReturnsEmptyLogs = true;
          }
          $scope.isTaskRunning = false;
          $scope.isShowLogs = true;
          CRM.alert("Task execution completed", "Task execution", 'success');
        } else {
          CRM.alert(result.error_message, ts("Error task execution"), "error");
          $scope.resultLogs = [ts('Task returns error: ') + result.error_message];
          $scope.isTaskRunning = false;
          $scope.isShowLogs = true;
        }
        $scope.runButtonText = $scope.ts('Run again');
        $scope.$apply();
      }).fail(function() {
        $scope.runButtonText = $scope.ts('Run again');
        $scope.resultLogs = ["An unknown error occurred during task execution. Please check your server logs for details before proceeding."];
        $scope.isTaskRunning = false;
        $scope.isShowLogs = true;
        $scope.$apply();
        CRM.alert("Task failed to execute", "Task execution", 'error');
      });
    };

    $scope.showError = function(message) {
      $scope.errors.push(message);
    };

    $scope.cleanErrors = function() {
      $scope.errors = [];
    };

    $scope.isInputValueRequired = function() {
      return $scope.task['input_required'] === 1 || $scope.task['input_required'] === '1';
    }

    $scope.loadTask();
  });
})(angular, CRM.$, CRM._);
