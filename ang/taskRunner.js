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
          }
        }
      });
    }
  ]);

  angular
    .module(moduleName)
    .controller("taskRunnerCtrl", function($scope, $location, taskId) {
      $scope.taskId = taskId;
      $scope.ts = CRM.ts();

      if (window.waitSqlTaskId === taskId) {
        window.waitSqlTaskId = null;
        runTask();
      }

      $scope.runTask = runTask;

      function runTask() {
        $scope.resultLogs = [];
        CRM.alert("Task execution has started", "Task execution", 'info');
        CRM.api3("Sqltask", "execute", {
          task_id: taskId,
          input_val: 0
        }).done(function(result) {
          $scope.resultLogs = result.values.log;
          $scope.$apply();
          CRM.alert("Task execution completed", "Task execution", 'success');
        }).fail(function() {
          $scope.resultLogs = ["An unknown error occurred during task execution. Please check your server logs for details before proceeding."];
          $scope.$apply();
          CRM.alert("Task failed to execute", "Task execution", 'error');
        });
      }
    });
})(angular, CRM.$, CRM._);
