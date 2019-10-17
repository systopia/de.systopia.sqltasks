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

      $scope.onInfoPress = onInfoPress;

      if (taskId) {
        CRM.api3("Sqltask", "get", {
          sequential: 1,
          id: taskId
        }).done(function(result) {
          if (!result.is_error) {
            var task = Object.assign({}, result.values);
            $scope.config = Object.assign({}, task.config);
            delete task["config"];
            delete $scope.config.version;
            $scope.taskOptions = task;
          }
        });
      }

      CRM.api3("Sqltaskfield", "getrunpermissions").done(function(result) {
        permissionsData = [];
        Object.keys(result.values[0]).map(key => {
          permissionsData.push({
            value: key,
            name: result.values[0][key]
          });
        });
        $scope.permissionsData = permissionsData;
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
          if (!currentElement) {
            return;
          }
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

      CRM.api3("Sqltask", "gettaskactions").done(function(result) {
        $scope.actions = result.values;
        if (!getBooleanFromNumber(taskId)) {
          $scope.actions.forEach(function(value) {
            $scope.addAction(value.type);
          });
        }
        $scope.$apply();
      });

      CRM.api3("Sqltaskfield", "getschedulingoptions").done(function(result) {
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

      CRM.api3("Sqltaskfield", "getmessagetemplates").done(function(result) {
        if (!result.is_error) {
          Object.keys(result.values[0]).map(key => {
            templateOptions.push({
              value: key,
              name: result.values[0][key]
            });
          });
          $scope.templateOptions = templateOptions;
        }
      });

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

  var templateOptions = [];

  function removeItemFromArray(index) {
    this.$parent.config.actions.splice(index, 1);
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
        $scope.onInfoPress = onInfoPress;
        $scope.onSqlScriptPress = function() {
          CRM.help("SQL Script", {
            id: "id-sql-script",
            file: "CRM/Sqltasks/Action/RunSQL"
          });
          return false;
        };
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
        $scope.onInfoPress = onInfoPress;
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
        CRM.api3("OptionValue", "get", {
          sequential: 1,
          return: ["value", "label"],
          option_group_id: "activity_type",
          options: { limit: 0 },
          is_active: 1
        }).done(function(result) {
          var activityTypeData = [];
          if (!result.is_error) {
            result.values.map(type => {
              activityTypeData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.activityTypeData = activityTypeData;
          $scope.$apply();
        });
        CRM.api3("OptionValue", "get", {
          sequential: 1,
          return: ["value", "label"],
          option_group_id: "activity_status",
          options: { limit: 0 },
          is_active: 1
        }).done(function(result) {
          var statusData = [];
          if (!result.is_error) {
            result.values.map(type => {
              statusData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.statusData = statusData;
          $scope.$apply();
        });

        CRM.api3("Campaign", "get", {
          sequential: 1,
          return: ["id", "title"],
          is_active: 1,
          options: { limit: 0, sort: "title ASC" }
        }).done(function(result) {
          var campaignData = [];
          if (!result.is_error) {
            result.values.map(type => {
              campaignData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.campaignData = campaignData;
          $scope.$apply();
        });

        CRM.api3("OptionValue", "get", {
          sequential: 1,
          return: ["value", "label"],
          option_group_id: "priority",
          options: { limit: 0 },
          is_active: 1
        }).done(function(result) {
          var priorityData = [];
          if (!result.is_error) {
            result.values.map(type => {
              priorityData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.priorityData = priorityData;
          $scope.$apply();
        });

        CRM.api3("OptionValue", "get", {
          sequential: 1,
          return: ["value", "label"],
          option_group_id: "engagement_index",
          options: { limit: 0 },
          is_active: 1
        }).done(function(result) {
          var engagementIndexData = [];
          if (!result.is_error) {
            result.values.map(type => {
              engagementIndexData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.engagementIndexData = engagementIndexData;
          $scope.$apply();
        });

        CRM.api3("OptionValue", "get", {
          sequential: 1,
          return: ["value", "label"],
          option_group_id: "encounter_medium",
          options: { limit: 0 },
          is_active: 1
        }).done(function(result) {
          var mediumData = [];
          if (!result.is_error) {
            result.values.map(type => {
              mediumData.push({
                value: type.value,
                name: type.label
              });
            });
          }
          $scope.mediumData = mediumData;
          $scope.$apply();
        });

        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.onInfoPress = onInfoPress;
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
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.templateOptions = templateOptions;
        $scope.onInfoPress = onInfoPress;
        CRM.api3("Sqltaskfield", "getfileencoding").done(function(result) {
          encodingData = [];
          Object.keys(result.values[0]).map(key => {
            var entity = result.values[0][key];
            if (key) {
              encodingData.push({
                value: key,
                name: entity
              });
            }
          });
          $scope.encodingData = encodingData;
          $scope.$apply();
        });
        CRM.api3("Sqltaskfield", "getdelimiter").done(function(result) {
          delimiterData = [];
          Object.keys(result.values[0]).map(key => {
            var entity = result.values[0][key];
            if (key) {
              delimiterData.push({
                value: key,
                name: entity
              });
            }
          });
          delimiterData.push({
            value: "other",
            name: "other"
          });
          $scope.delimiterData = delimiterData;
          $scope.$apply();
        });
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
        CRM.api3("Tag", "get", {
          sequential: 1,
          return: ["name", "id"],
          is_enabled: 1,
          options: { limit: 0 }
        }).done(function(result) {
          tagsData = [];
          result.values.map(tag => {
            tagsData.push({
              value: tag.id,
              name: tag.name
            });
          });
          $scope.tagsData = tagsData;
        });
        CRM.api3("Sqltaskfield", "getsynctagentities").done(function(result) {
          entityData = [];
          Object.keys(result.values[0]).map(key => {
            var entity = result.values[0][key];
            if (key) {
              entityData.push({
                value: key,
                name: entity
              });
            }
          });
          $scope.entityData = entityData;
          $scope.$apply();
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

        CRM.api3("Group", "get", {
          sequential: 1,
          return: ["id", "title"],
          is_active: 1,
          options: { limit: 0 }
        }).done(function(result) {
          groupData = [];
          result.values.map(group => {
            groupData.push({
              value: group.id,
              name: group.title
            });
          });
          $scope.groupData = groupData;
          $scope.$apply();
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
        $scope.onInfoPress = onInfoPress;
        $scope.isDataExists = function(array) {
          return Boolean(array && array.length);
        };

        var tasksData = [];
        var categoriesData = [];

        CRM.api3("Sqltaskfield", "gettaskcategories").done(function(result) {
          categoriesData = [];
          Object.keys(result.values[0]).map(key => {
            var category = result.values[0][key];
            if (key) {
              categoriesData.push({
                value: key,
                name: category
              });
            }
          });
          $scope.categoriesData = categoriesData;
          $scope.$apply();
        });

        CRM.api3("Sqltask", "getexecutiontasks").done(function(result) {
          tasksData = [];
          if (!result.is_error) {
            Object.keys(result.values[0]).map(key => {
              var task = result.values[0][key];
              tasksData.push({
                value: key,
                name: task
              });
            });
            $scope.tasksData = tasksData;
            $scope.$apply();
          }
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
        $scope.onInfoPress = onInfoPress;
      }
    };
  });

  function onInfoPress(entity, id, file) {
    CRM.help(entity, {
      id: id,
      file: file
    });
    return false;
  }

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
        $scope.templateOptions = templateOptions;
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.onInfoPress = onInfoPress;
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
        $scope.templateOptions = templateOptions;
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.onInfoPress = onInfoPress;
      }
    };
  });

  // Components
  angular.module(moduleName).directive("textArea", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/textArea.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        rowsNumber: "<rowsnumber",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon",
        columnsNumber: "<columnsnumber"
      },
      controller: function($scope) {
        $scope.columnsNumber = angular.isDefined($scope.columnsNumber)
          ? $scope.columnsNumber
          : 80;
      }
    };
  });

  angular.module(moduleName).directive("textInput", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/textInput.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      }
    };
  });

  angular.module(moduleName).directive("checkBox", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/checkBox.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      }
    };
  });

  angular.module(moduleName).directive("ordinarySelect", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/ordinarySelect.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        optionsArray: "<optionsarray",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      }
    };
  });

  angular.module(moduleName).directive("ordinarySelect2", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/ordinarySelect2.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        optionsArray: "<optionsarray",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      },
      controller: function($scope) {
        CRM.$(function($) {
          setTimeout(function() {
            $("#" + $scope.fieldId)
              .css("width", "25em")
              .select2();
          }, 1500);
        });
      }
    };
  });

  angular.module(moduleName).directive("multipleSelect2", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/multipleSelect2.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        optionsArray: "<optionsarray",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      },
      controller: function($scope) {
        CRM.$(function($) {
          setTimeout(function() {
            $("#" + $scope.fieldId)
              .css("width", "25em")
              .select2();
          }, 1500);
        });
      }
    };
  });

  angular.module(moduleName).directive("selectEntityref", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/selectEntityref.html",
      scope: {
        isRequired: "<isrequired",
        componentModel: "=model",
        fieldLabel: "<fieldlabel",
        fieldId: "<fieldid",
        dataParams: "<dataparams",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon"
      },
      controller: function($scope) {
        $scope.dataParams = angular.isDefined($scope.dataParams)
          ? $scope.dataParams
          : [];
        CRM.$(function($) {
          setTimeout(function() {
            $("#" + $scope.fieldId)
              .css("width", "25em")
              .crmEntityRef();
          }, 0);
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
