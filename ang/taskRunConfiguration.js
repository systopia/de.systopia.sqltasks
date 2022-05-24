(function(angular, $, _) {
  var moduleName = "taskRunConfiguration";
  var moduleDependencies = ["ngRoute"];
  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config(["$routeProvider", function($routeProvider) {
      $routeProvider.when("/sqltasks/run/configuration/:tid/:is_required_input_value?", {
        controller: "taskRunConfigurationCtrl",
        templateUrl: "~/taskRunConfiguration/taskRunConfiguration.html",
        resolve: {
          taskId: function($route) {
            return $route.current.params.tid;
          },
          isRequiredInputValue: function($route) {
            if ($route.current.params.is_required_input_value == 1) {
              return true;
            }

            return false;
          },
        }
      });
    }
  ]);

  angular.module(moduleName).controller("taskRunConfigurationCtrl", function($scope, $location, taskId, isRequiredInputValue) {
    $scope.taskId = taskId;
    $scope.isRequiredInputValue = isRequiredInputValue;
    $scope.inputValue = '';
    $scope.errors = [];
    $scope.ts = CRM.ts();

    $scope.confirmRunningTask = function() {
      $scope.validateInputValue();

      if ($scope.errors.length > 0) {
        return;
      }

      var link = "/sqltasks/run/" + taskId;
      if ($scope.isRequiredInputValue) {
        link += '/' + $scope.inputValue;
      }

      $location.path(link);
    };

    $scope.validateInputValue = function() {
      $scope.cleanErrors();

      if ($scope.isRequiredInputValue) {
        if ($scope.inputValue.length === 0) {
          $scope.showError('Input value is required');
        }
      }

      //TODO: check if we need any extra validation
    };

    $scope.showError = function(message) {
      $scope.errors.push(message);
    };

    $scope.cleanErrors = function() {
      $scope.errors = [];
    };
  });
})(angular, CRM.$, CRM._);
