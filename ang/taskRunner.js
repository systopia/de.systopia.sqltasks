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
      $scope.ts = CRM.ts();
      runTaskAgain();

      $scope.configureTask = function() {
        $location.path("/sqltasks/configure/" + taskId);
      };

      $scope.backToManager = function() {
        $location.path("/sqltasks/manage");
      };

      $scope.runTaskAgain = runTaskAgain;

      function runTaskAgain() {
        CRM.alert("Task executed again", "Task execution", 'info');
        CRM.api3("Sqltask", "execute", {
          task_id: taskId,
          input_val: 0
        }).done(function(result) {
          $scope.resultLogs = result.values.log;
          $scope.$apply();
        });
      }
    });
})(angular, CRM.$, CRM._);
