(function(angular, $, _) {
  var moduleName = "deleteTask";

  var moduleDependencies = ["ngRoute"];

  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/delete/:tid", {
        controller: "deleteTask",
        templateUrl: "~/deleteTask/deleteTask.html",
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
    .controller("deleteTask", function($scope, $location, taskId) {
      $scope.ts = CRM.ts();
      $scope.taskData = {};
      $scope.onTaskDelete = function() {
        CRM.api3("Sqltask", "deletetask", {
          id: taskId
        }).done(function(result) {
          if (result.values[0] === "Task doesn't exist anymore." && !result.is_error) {
            CRM.alert(ts("Task deleted successfully"), ts("Deleting task"), "success");
          } else {
            CRM.alert(ts("Error deleting task"), ts("Deleting task"), "error");
          }
          $location.path("/sqltasks/manage");
          $scope.$apply();
        });
      };

      $scope.onBackPress = function() {
        $location.path("/sqltasks/manage");
      };

      CRM.api3("Sqltask", "get", {
        sequential: 1,
        id: taskId
      }).done(function(result) {
        $scope.taskData = result.values;
        $scope.$apply();
      });
    });
})(angular, CRM.$, CRM._);
