(function(angular, $, _) {
  var moduleName = "configImport";

  var moduleDependencies = ["ngRoute"];

  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/import/:tid", {
        controller: "configImport",
        templateUrl: "~/configImport/configImport.html",
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
    .controller("configImport", function($scope, $location, taskId) {
      $scope.ts = CRM.ts();
      $scope.$fileContent = "";
      $scope.showContent = function($fileContent) {
        $scope.content = $fileContent;
      };

      $scope.onImportPress = function() {
        try {
          CRM.api3("Sqltask", "importconfig", {
            id: taskId,
            import_json_data: JSON.parse($scope.content)
          }).done(function(result) {
            var message = "";
            var title = "";
            var type = "";
            if (result.is_error) {
              type = "error";
              title = "Error";
              message = "Invalid config file.";
            } else {
              type = "alert";
              title = "Update Complete";
              message = "Configuration imported successfully.";
            }
            CRM.alert(message, title, type);
          });
        } catch (error) {
          CRM.alert("Invalid config file.", "Error", "error");
        }
        $location.path("/sqltasks/manage");
      };
    });

  angular.module(moduleName).directive("onReadFile", function($parse) {
    return {
      restrict: "A",
      scope: false,
      link: function(scope, element, attrs) {
        var fn = $parse(attrs.onReadFile);

        element.on("change", function(onChangeEvent) {
          var reader = new FileReader();

          reader.onload = function(onLoadEvent) {
            scope.$apply(function() {
              fn(scope, { $fileContent: onLoadEvent.target.result });
            });
          };

          reader.readAsText(
            (onChangeEvent.srcElement || onChangeEvent.target).files[0]
          );
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
