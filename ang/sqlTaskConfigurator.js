(function(angular, $, _) {
  var moduleName = "sqlTaskConfigurator";

  var moduleDependencies = ["ngRoute", "ui.sortable"];

  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/configure/:tid", {
        controller: "sqlTaskConfiguratorCtrl",
        templateUrl: "~/sqlTaskConfigurator/sqlTaskConfigurator.html",
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
    .controller("sqlTaskConfiguratorCtrl", function($scope, $location, taskId) {
      $scope.ts = CRM.ts();

      $scope.taskOptions = {
        scheduled: ""
      };
      $scope.config = {
        actions: []
      };

      CRM.api3("Sqltask", "getrunpermissions").done(function(result) {
        var permissionsData = Object.keys(result.values[0]).map(key => {
          return {
            id: key,
            text: result.values[0][key]
          };
        });
        CRM.$(function($) {
          $("#run_permissions").select2({
            multiple: true,
            data: permissionsData,
            formatResult: format,
            formatSelection: format
          });
        });
      });

      $scope.onSchedulingOptionChange = function(params) {
        Object.keys($scope.config).forEach(element => {
          switch (element) {
            case "scheduled_month":
              $scope.config[element] = 1;
              break;
            case "scheduled_weekday":
              $scope.config[element] = 1;
              break;
            case "scheduled_day":
              $scope.config[element] = 1;
              break;
            case "scheduled_hour":
              $scope.config[element] = 0;
              break;
            case "scheduled_minute":
              $scope.config[element] = 0;
              break;
            default:
              break;
          }
        });
      };
      var previousOrder = [];
      $scope.sortableOptions = {
        update: function(e, ui) {
          previousOrder = $scope.config.actions.slice();
          $scope.$apply();
        },
        stop: function(e, ui) {
          var nextItemIndex = ui.item.sortable.dropindex;
          var nextElement = $scope.config.actions[nextItemIndex + 1];
          var currentElement = $scope.config.actions[nextItemIndex];
          var previousElement = $scope.config.actions[nextItemIndex - 1];
          if (currentElement.type === "CRM_Sqltasks_Action_ErrorHandler") {
            if (
              previousElement.type !== "CRM_Sqltasks_Action_PostSQL" &&
              previousElement.type !== "CRM_Sqltasks_Action_SuccessHandler" &&
              previousElement.type !== "CRM_Sqltasks_Action_ErrorHandler"
            ) {
              CRM.alert("Wrong actions order", "Error", "error");
              $scope.config.actions = previousOrder.slice();
              $scope.$apply();
            }
          } else if (
            currentElement.type === "CRM_Sqltasks_Action_SuccessHandler"
          ) {
            if (
              previousElement.type !== "CRM_Sqltasks_Action_PostSQL" &&
              previousElement.type !== "CRM_Sqltasks_Action_SuccessHandler"
            ) {
              CRM.alert("Wrong actions order", "Error", "error");
              $scope.config.actions = previousOrder.slice();
              $scope.$apply();
            }
          } else if (currentElement.type === "CRM_Sqltasks_Action_PostSQL") {
            if (
              previousElement.type !== "CRM_Sqltasks_Action_PostSQL" &&
              nextElement.type !== "CRM_Sqltasks_Action_SuccessHandler" &&
              nextElement.type !== "CRM_Sqltasks_Action_ErrorHandler"
            ) {
              CRM.alert("Wrong actions order", "Error", "error");
              $scope.config.actions = previousOrder.slice();
              $scope.$apply();
            }
          } else if (
            previousElement.type === "CRM_Sqltasks_Action_SuccessHandler" ||
            (previousElement.type === "CRM_Sqltasks_Action_ErrorHandler" &&
              nextElement) ||
            previousElement.type === "CRM_Sqltasks_Action_PostSQL"
          ) {
            CRM.alert("Wrong actions order", "Error", "error");
            $scope.config.actions = previousOrder.slice();
            $scope.$apply();
          }
        }
      };

      if (taskId) {
        CRM.api3("Sqltask", "get", {
          sequential: 1,
          id: taskId
        }).done(function(result) {
          if (!result.is_error) {
            var task = Object.assign({}, result.values);
            delete task["config"];
            $scope.config = result.values.config;
            delete $scope.config.version;
            $scope.selectedAction = result.values.config.actions[0].type;
            $scope.taskOptions = task;
          }
        });
      }

      CRM.api3("Sqltask", "gettaskactions").done(function(result) {
        $scope.actions = result.values;
        $scope.config.actions = [];
        $scope.actions.forEach(function(value) {
          $scope.addAction(value.type);
        });
        $scope.$apply();
      });

      CRM.api3("Sqltask", "getschedulingoptions").done(function(result) {
        $scope.schedulingOptions = result.values[0];
        var defaultOption = Object.keys(result.values[0])[0];
        if (defaultOption === "always" && !Number(taskId)) {
          $scope.taskOptions.scheduled = defaultOption;
          $scope.config = Object.assign($scope.config, {
            scheduled_month: 1,
            scheduled_weekday: 1,
            scheduled_day: 1
          });
        }
        $scope.$apply();
      });

      $scope.onFormSubmit = function() {
        var preparedData = {};
        if (taskId) {
          Object.assign(preparedData, { id: taskId });
        }
        Object.assign(preparedData, $scope.taskOptions);

        preparedData.config = $scope.config;
        
        CRM.api3("Sqltask", "create", preparedData).done(function(result) {
          if (result.is_error) {
            type = "error";
            title = "Error";
            if (Number(taskId)) {
              message = "Error updating task";
            } else {
              message = "Error creating task";
            }
          } else {
            type = "alert";
            title = "Update Complete";
            message = "Configuration imported successfully.";
            if (Number(taskId)) {
              message = "Task successfully updated";
            } else {
              message = "Task successfully created";
            }
          }
          CRM.alert(message, title, type);
        });
        $location.path("/sqltasks/manage");
      };

      $scope.addAction = function(actionName) {
        var array = $scope.config.actions;
        for (let index = array.length - 1; index >= 0; index--) {
          const element = array[index];
          if (actionName === element.type) {
            $scope.config.actions.splice(index + 1, 0, { type: actionName });
            return;
          }
        }
        $scope.config.actions.push({ type: actionName });
      };

      $scope.formNameFromType = function(type) {
        switch (type) {
          case "CRM_Sqltasks_Action_RunSQL":
            return ts("Run SQL Script");
          case "CRM_Sqltasks_Action_CreateActivity":
            return ts("Create Activity");
          case "CRM_Sqltasks_Action_APICall":
            return ts("API Call");
          case "CRM_Sqltasks_Action_CSVExport":
            return ts("CSV Export");
          case "CRM_Sqltasks_Action_SyncTag":
            return ts("Synchronise Tag");
          case "CRM_Sqltasks_Action_SyncGroup":
            return ts("Synchronise Group");
          case "CRM_Sqltasks_Action_CallTask":
            return ts("Run SQL Task(s)");
          case "CRM_Sqltasks_Action_PostSQL":
            return ts("Run Cleanup SQL Script");
          case "CRM_Sqltasks_Action_SuccessHandler":
            return ts("Success Handler");
          case "CRM_Sqltasks_Action_ErrorHandler":
            return ts("Error Handler");
          default:
            return "";
        }
      };

      $scope.shouldShowTimeFieldsByName = function(fieldName) {
        if (!$scope.taskOptions.scheduled) {
          return false;
        }
        switch (fieldName) {
          case "minute":
            return $scope.taskOptions.scheduled !== "always";
          case "hour":
            return !["always", "hourly"].includes($scope.taskOptions.scheduled);
          case "day":
            return !["always", "hourly", "daily"].includes(
              $scope.taskOptions.scheduled
            );
          case "weekday":
            return $scope.taskOptions.scheduled === "weekly";
          case "month":
            return $scope.taskOptions.scheduled === "yearly";
          default:
            return false;
        }
      };
    });

  function removeItemFromArray(index) {
    this.$parent.config.actions.splice(index, 1);
  }

  function format(item) {
    return item.text;
  }

  function getBooleanFromNumber(number) {
    return !!Number(number);
  }

  angular.module(moduleName).directive("runSql", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/RunSQL.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("apiCall", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/APICall.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("createActivity", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/CreateActivity.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        CRM.$(function($) {
          $("#activity_activity_type_id")
            .css("width", "25em")
            .crmSelect2();
          $("#activity_campaign_id")
            .css("width", "25em")
            .crmSelect2();
        });
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("csvExport", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/CSVExport.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        CRM.$(function($) {
          $("#csv_encoding")
            .css("width", "25em")
            .crmSelect2();
          $("#csv_email_template")
            .css("width", "25em")
            .crmSelect2();
        });
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("syncTag", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/SyncTag.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        CRM.$(function($) {
          $("#tag_tag_id")
            .css("width", "25em")
            .crmSelect2();
        });
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("syncGroup", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/SyncGroup.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        CRM.$(function($) {
          $("#group_group_id")
            .css("width", "25em")
            .crmSelect2();
        });
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("callTask", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/CallTask.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        CRM.api3("Sqltask", "gettaskcategories").done(function(result) {
          var categoriesData = Object.keys(result.values[0]).map(key => {
            var category = result.values[0][key];
            return {
              id: key,
              text: category
            };
          });
          CRM.$(function($) {
            $("#task_categories").select2({
              multiple: true,
              data: categoriesData,
              formatResult: format,
              formatSelection: format
            });
          });
        });
        CRM.api3("Sqltask", "getexecutiontasks").done(function(result) {
          var tasksData = Object.keys(result.values[0]).map(key => {
            var task = result.values[0][key];
            return {
              id: key,
              text: task
            };
          });
          CRM.$(function($) {
            $("#task_tasks").select2({
              multiple: true,
              data: tasksData,
              formatResult: format,
              formatSelection: format
            });
          });
        });
      }
    };
  });

  angular.module(moduleName).directive("postSql", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/PostSQL.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("successHandler", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/SuccessHandler.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });

  angular.module(moduleName).directive("errorHandler", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/ErrorHandler.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
      }
    };
  });
})(angular, CRM.$, CRM._);
