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
      $scope.executionBlock = {
        'isLoaded' : false,
        'currentLoadedComponents' : 0,
        'componentsNumber' : 2,
      };
      $scope.handleBlockLoading = handleBlockLoading;
      $scope.isExecutionBlockLoaded = function() {
        return $scope.executionBlock.isLoaded;
      };
      $scope.taskOptions = {
        scheduled: ""
      };
      $scope.config = {
        actions: [],
        scheduled_month: "1",
        scheduled_weekday: "1",
        scheduled_day: "1",
        scheduled_hour: "0",
        scheduled_minute: "0"
      };
      $scope.taskId = taskId;

      $scope.onInfoPress = onInfoPress;
      $scope.getBooleanFromNumber = getBooleanFromNumber;

      $scope.$on("$viewContentLoaded", function() {
        setTimeout(function() {
          openCheckedActions();
        }, 1500);

        var form = document.querySelector("#sql-task-form");
        var triggerButton = document.querySelector(
          "#_qf_Configure_submit-bottom"
        );

        triggerButton.onclick = function() {
          openCheckedActions();
          setTimeout(function() {
            if (form.reportValidity()) {
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
                    message = "Error while updating task";
                  } else {
                    message = "Error while creating task";
                  }
                } else {
                  type = "success";
                  title = "Task updated";
                  message = "Configuration imported successfully.";
                  if (Number(taskId)) {
                    message = "Task successfully updated";
                  } else {
                    message = "Task successfully created";
                  }
                  $location.path("/sqltasks/manage");
                  $scope.$apply();
                }
                CRM.alert(message, title, type);
              });

            }
          }, 500);
        };
      });

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

      CRM.$(function($) {
        setTimeout(function() {
          $("body").on("click", ".input-checkbox", function(e) {
            e.stopPropagation();
          });
        }, 1500);
      });

      CRM.api3("Sqltaskfield", "getrunpermissions").done(function(result) {
        permissionsData = [];
        Object.keys(result.values[0]).map(key => {
          permissionsData.push({
            value: key,
            name: result.values[0][key]
          });
        });
        $scope.permissionsData = permissionsData;
        $scope.executionBlock = $scope.handleBlockLoading($scope.executionBlock);
      });

      $scope.onSchedulingOptionChange = function(params) {
        Object.keys($scope.config).forEach(element => {
          switch (element) {
            case "scheduled_month":
              $scope.config[element] = "1";
              break;
            case "scheduled_weekday":
              $scope.config[element] = "1";
              break;
            case "scheduled_day":
              $scope.config[element] = "1";
              break;
            case "scheduled_hour":
              $scope.config[element] = "0";
              break;
            case "scheduled_minute":
              $scope.config[element] = "0";
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
          $scope.taskOptions.enabled = 0;
          $scope.taskOptions.parallel_exec = 0;
          $scope.taskOptions.input_required = 0;
          $scope.config = Object.assign($scope.config, {
            scheduled_month: 1,
            scheduled_weekday: 1,
            scheduled_day: 1
          });
        }
        $scope.executionBlock = $scope.handleBlockLoading($scope.executionBlock);
        $scope.$apply();
      });

      $scope.addAction = function(actionName) {
        var array = $scope.config.actions;
        for (let index = array.length - 1; index >= 0; index--) {
          const element = array[index];
          if (actionName === element.type) {
            $scope.config.actions.splice(index + 1, 0, { type: actionName });
            return;
          }
        }
        if (
          actionName === "CRM_Sqltasks_Action_RunSQL" ||
          actionName === "CRM_Sqltasks_Action_PostSQL"
        ) {
          $scope.config.actions.push({ type: actionName, enabled: "1" });
        } else {
          $scope.config.actions.push({ type: actionName });
        }
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
          case "CRM_Sqltasks_Action_SegmentationAssign":
            return ts("Assign to Campaign (Segmentation)");
          case "CRM_Sqltasks_Action_SegmentationExport":
            return ts("Segmentation Export");
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

      CRM.api3("Campaign", "get", {
        sequential: 1,
        return: ["id", "title"],
        is_active: 1,
        options: { limit: 0, sort: "title ASC" }
      }).done(function(result) {
        if (!result.is_error) {
          result.values.map(type => {
            campaignData.push({
              value: type.id,
              name: type.title
            });
          });
        }
        $scope.campaignData = campaignData;
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
  var campaignData = [];

  function removeItemFromArray(index) {
    this.$parent.config.actions.splice(index, 1);
  }

  function getBooleanFromNumber(number) {
    return !!Number(number);
  }

  function openCheckedActions() {
    CRM.$(function($) {
      var inputArray = $('[id*="enabled"]');
      $(inputArray).each(function() {
        if ($(this).is(":checked")) {
          var parent = $(this).closest(".crm-accordion-wrapper");
          if ($(parent).is(".collapsed")) {
            $(parent).removeClass("collapsed");
            $(parent)
              .find(".crm-accordion-body")
              .css("display", "block");
          }
        }
      });
      $('#sqlTasksActionsBlock').removeClass('loading');
    });
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
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
        $scope.campaignData = campaignData;
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
          $scope.setDataLoadedForOrdinarySelect2('activity_activity_type_id_' + $scope.ctrl.index);
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
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.templateOptions = templateOptions;
        $scope.onInfoPress = onInfoPress;
        CRM.api3("Sqltaskfield", "getfileencoding").done(function(result) {
          var encodingData = [];
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
          $scope.setDataLoadedForOrdinarySelect2('csv_encoding_' + $scope.ctrl.index);
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
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
        $scope.ts = CRM.ts();
        CRM.api3("Tag", "get", {
          sequential: 1,
          return: ["name", "id"],
          is_enabled: 1,
          options: { limit: 0 }
        }).done(function(result) {
          var tagsData = [];
          result.values.map(tag => {
            tagsData.push({
              value: tag.id,
              name: tag.name
            });
          });
          $scope.tagsData = tagsData;
          $scope.setDataLoadedForOrdinarySelect2('tag_tag_id_' + $scope.ctrl.index);
          $scope.$apply();
        });
        CRM.api3("Sqltaskfield", "getsynctagentities").done(function(result) {
          var entityData = [];
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
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
        $scope.ts = CRM.ts();

        CRM.api3("Group", "get", {
          sequential: 1,
          return: ["id", "title"],
          is_active: 1,
          options: { limit: 0 }
        }).done(function(result) {
          var groupData = [];
          result.values.map(group => {
            groupData.push({
              value: group.id,
              name: group.title
            });
          });
          $scope.groupData = groupData;
          $scope.setDataLoadedForOrdinarySelect2('group_group_id' + $scope.ctrl.index);
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
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
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
          $scope.setDataLoadedForOrdinarySelect2('task_categories_' + $scope.ctrl.index);
          $scope.$apply();
        });

        CRM.api3("Sqltaskfield", "getexecutiontasks").done(function(result) {
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
            $scope.setDataLoadedForOrdinarySelect2('task_tasks_' + $scope.ctrl.index);
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

  function handleBlockLoading(block) {
    block.currentLoadedComponents = block.currentLoadedComponents  + 1;
    if (block.currentLoadedComponents >= block.componentsNumber) {
      block.isLoaded = true;
    }

    return block;
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

  angular.module(moduleName).directive("segmentationAssign", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/SegmentationAssign.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.campaignData = campaignData;
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.onInfoPress = onInfoPress;

        $scope.checkboxChange = function(value) {
          if (value == "1") {
            this.ctrl.model.segment_name = "";
          }
        };

        $scope.statusChanged = function() {
          this.ctrl.model.segment_order_table = "";
          this.ctrl.model.segment_order = "";
        };

        var statusesData = [];

        CRM.api3("Sqltaskfield", "get_campaign_statuses").done(function(
          result
        ) {
          statusesData = [];
          Object.keys(result.values[0]).map(key => {
            var status = result.values[0][key];
            if (key) {
              statusesData.push({
                value: key,
                name: status
              });
            }
          });
          $scope.statusesData = statusesData;
          $scope.$apply();
        });
      }
    };
  });

  angular.module(moduleName).directive("segmentationExport", function() {
    return {
      restrict: "E",
      templateUrl: "~/sqlTaskConfigurator/SegmentationExport.html",
      scope: {
        model: "=",
        index: "<"
      },
      bindToController: true,
      controllerAs: "ctrl",
      controller: function($scope) {
        $scope.ordinarySelect2LoadDataStatus = [];
        $scope.isDataLoadedForOrdinarySelect2 = function isDataLoadedForOrdinarySelect2(select2Id) {
          return $scope.ordinarySelect2LoadDataStatus.includes(select2Id);
        };
        $scope.setDataLoadedForOrdinarySelect2 = function setDataLoadedForOrdinarySelect2(select2Id) {
          $scope.ordinarySelect2LoadDataStatus.push(select2Id);
        };
        $scope.templateOptions = templateOptions;
        $scope.campaignData = campaignData;
        $scope.ts = CRM.ts();
        $scope.removeItemFromArray = removeItemFromArray;
        $scope.getBooleanFromNumber = getBooleanFromNumber;
        $scope.onInfoPress = onInfoPress;

        $scope.checkboxChange = function(value) {
          if (value == "1") {
            this.ctrl.model.date_from = "";
            this.ctrl.model.date_to = "";
          }
        };

        if ($scope.ctrl.model && getBooleanFromNumber($scope.ctrl.model.campaign_id)) {
          CRM.api3("Segmentation", "segmentlist", {
            campaign_id: $scope.ctrl.model.campaign_id
          }).done(function(result) {
            var segmentationData = [];
            Object.keys(result.values).map(key => {
              var entity = result.values[key];
              if (key) {
                segmentationData.push({
                  value: key,
                  name: entity
                });
              }
            });
            $scope.segmentationData = segmentationData;
            $scope.setDataLoadedForOrdinarySelect2('segmentation_export_segments' + $scope.ctrl.index);
            $scope.$apply();
          });
        }

        $scope.statusChanged = function(value, fieldId) {
          CRM.$(function($) {
            $scope.ctrl.model.segments = "";
            var inputStyles =  {
              'width' : '100%',
              'max-width' : '300px',
              'font-family' : 'monospace, monospace !important',
              'box-sizing' : 'border-box',
              'height' : '28px'
            };
            setTimeout(() => {
              $("#" + fieldId)
                .css(inputStyles)
                .select2();
            }, 0);
          });
          if (getBooleanFromNumber(value)) {
            CRM.api3("Segmentation", "segmentlist", {
              campaign_id: value
            }).done(function(result) {
              var segmentationData = [];
              Object.keys(result.values).map(key => {
                var entity = result.values[key];
                if (key) {
                  segmentationData.push({
                    value: key,
                    name: entity
                  });
                }
              });
              $scope.segmentationData = segmentationData;
              $scope.$apply();
            });
          }
        };

        CRM.api3("Sqltaskfield", "get_segmentation_exporter").done(function(result) {
          exporterData = [];
          Object.keys(result.values[0]).map(key => {
            var entity = result.values[0][key];
            if (key) {
              exporterData.push({
                value: key,
                name: entity
              });
            }
          });
          $scope.exporterData = exporterData;
          $scope.setDataLoadedForOrdinarySelect2('segmentation_export_exporter' + $scope.ctrl.index);
          $scope.$apply();
        });
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
        columnsNumber: "<columnsnumber",
        inputMaxWidth: "<inputmaxwidth",
      },
      controller: function($scope) {
        $scope.columnsNumber = angular.isDefined($scope.columnsNumber) ? $scope.columnsNumber : 74;
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        $scope.textAreaStyles = {
          'width' : '100%',
          'max-width' : $scope.inputMaxWidth,
          'font-family' : 'monospace, monospace !important',
          'box-sizing' : 'border-box',
        };
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
        showHelpIcon: "<showhelpicon",
        sizeLength: "<sizelength",
        isDisabled: "<disabled",
        inputMaxWidth: "<inputmaxwidth",
      },
      controller: function($scope) {
        $scope.isDisabled = angular.isDefined($scope.isDisabled) ? $scope.isDisabled : false;
        $scope.componentModel = angular.isDefined($scope.isDisabled) ? $scope.componentModel : "";
        $scope.sizeLength = angular.isDefined($scope.sizeLength) ? $scope.sizeLength : 32;
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        $scope.inputStyle =  {
          'width' : '100%',
          'max-width' : $scope.inputMaxWidth,
          'font-family' : 'monospace, monospace !important',
          'box-sizing' : 'border-box',
          'height' : '28px'
        };
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
        showHelpIcon: "<showhelpicon",
        checkboxChange: "&"
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
        showHelpIcon: "<showhelpicon",
        inputMaxWidth: "<inputmaxwidth",
      },
      controller: function($scope) {
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        $scope.selectStyle = {
          'width' : '100%',
          'max-width' : $scope.inputMaxWidth,
          'box-sizing' : 'border-box',
          'height' : '28px'
        };
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
        isDataLoaded: "<isdataloaded",
        optionsArray: "<optionsarray",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon",
        selectChange: "&",
        inputMaxWidth: "<inputmaxwidth",
        fieldIdToChange: "<"
      },
      controller: function($scope) {
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        var selectStyles = {
          'width' : "100%",
          'max-width' : $scope.inputMaxWidth,
          'box-sizing' : 'border-box',
          'height' : '28px'
        };

        if (angular.isDefined($scope.isDataLoaded) && $scope.isDataLoaded == false) {
          var timerId = setInterval(function() {
            if ($scope.isDataLoaded) {
              $("#" + $scope.fieldId).css(selectStyles).select2();
              clearInterval(timerId);
            }
          }, 300);
        } else {
          CRM.$(function($) {
            setTimeout(function() {
              $("#" + $scope.fieldId).css(selectStyles).select2();
            }, 1500);
          });
        }
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
        isDataLoaded: "<isdataloaded",
        fieldId: "<fieldid",
        optionsArray: "<optionsarray",
        helpAction: "&helpaction",
        showHelpIcon: "<showhelpicon",
        inputMaxWidth: "<inputmaxwidth",
      },
      controller: function($scope) {
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        var selectStyles = {
          'width' : "100%",
          'max-width' : $scope.inputMaxWidth,
          'box-sizing' : 'border-box',
          'height' : '28px'
        };
        if (angular.isDefined($scope.isDataLoaded) && $scope.isDataLoaded == false) {
          var timerId = setInterval(function() {
            if ($scope.isDataLoaded) {
              $("#" + $scope.fieldId).css(selectStyles).select2();
              clearInterval(timerId);
            }
          }, 300);
        } else {
          CRM.$(function($) {
            setTimeout(function() {
              $("#" + $scope.fieldId).css(selectStyles).select2();
            }, 1500);
          });
        }
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
        showHelpIcon: "<showhelpicon",
        inputMaxWidth: "<inputmaxwidth",
      },
      controller: function($scope) {
        $scope.inputMaxWidth = angular.isDefined($scope.inputMaxWidth) ? $scope.inputMaxWidth : "300px";
        var selectStyles = {
          'width' : "100%",
          'max-width' : $scope.inputMaxWidth,
          'box-sizing' : 'border-box',
          'height' : '28px'
        };
        $scope.dataParams = angular.isDefined($scope.dataParams) ? $scope.dataParams : [];
        CRM.$(function($) {
          setTimeout(function() {
            $("#" + $scope.fieldId).css(selectStyles).crmEntityRef();
          }, 0);
        });
      }
    };
  });
})(angular, CRM.$, CRM._);
