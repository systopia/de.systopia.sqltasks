(function(angular, $, _) {
  var moduleName = "sqlTaskManager";
  var moduleDependencies = ["ngRoute", "ui.sortable"];
  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/manage/:highlightTaskId?", {
        controller: "sqlTaskManagerCtrl",
        templateUrl: "~/sqlTaskManager/sqlTaskManager.html",
        resolve: {
          highlightTaskId: function($route) {
            return angular.isDefined($route.current.params.highlightTaskId) ? $route.current.params.highlightTaskId : false;
          }
        }
      });
    }
  ]);

  angular
    .module(moduleName)
    .controller("sqlTaskManagerCtrl", function($scope, $location, highlightTaskId, $timeout) {
      $scope.url = CRM.url;
      $scope.taskIdWithOpenPanel = null;
      $scope.tasks = [];
      $scope.displayTasks = [];
      $scope.previousTaskOrder = [];
      $scope.isTasksLoading = false;
      $scope.getBooleanFromNumber = function(number) {return !!Number(number);};
      $scope.getNumberFromString = function(stringValue) {return Number(stringValue);};
      $scope.showPanelForTaskId = function(taskId) {$scope.taskIdWithOpenPanel = taskId;};
      $scope.ts = CRM.ts();
      $scope.dispatcher_frequency = null;
      $scope.resourceBaseUrl = CRM.config.resourceBase;
      $scope.tasksDisplayPreferences = {
        'isShowArchivedTask' : '0',
        'isShowEnabledTask' : '1',
        'isShowDisabledTask' : '1',
      };
      $scope.templateOptions = [];
      $scope.selectTemplateModel = { templateId: undefined };

      getAllTasks();
      getAllTemplates();
      getDefaultTemplate();
      getCurrentDispatcherFrequency();

      function getAllTasks() {
        $scope.isTasksLoading = true;
        CRM.api3("Sqltask", "getalltasks").done(function(result) {
          $scope.tasks = result.values;
          $scope.redrawTaskList();
          $scope.updatePreviousTaskOrder();
          $scope.isTasksLoading = false;
          $scope.$apply();
          $scope.handleHighlightTask(highlightTaskId);
        });
      }

      function getAllTemplates () {
        CRM.api3("SqltaskTemplate", "get_all").done(result => {
          if (result.is_error) {
            console.error(result.error_message);
            return;
          }

          $scope.templateOptions = result.values.map(
            template => ({ value: template.id, name: template.name })
          );

          $scope.$apply();
        });
      }

      function getDefaultTemplate () {
        CRM.api3("Setting", "getvalue", { name: "sqltasks_default_template" }).done(result => {
          if (result.is_error) {
            console.error(result.error_message);
            return;
          }

          $scope.selectTemplateModel.templateId = result.result;
          $scope.$apply();
        });
      }

      $scope.handleHighlightTask = function(taskId) {
        if (!taskId) {
          return;
        }

        var taskRowElement = CRM.$(".sql-task-row-item[data-task-id='" + taskId + "'] ");
        if (taskRowElement.length === 1) {
          CRM.$(window).scrollTop(taskRowElement.offset().top - 80);
          taskRowElement.effect('highlight', {}, 5000);
        }
      };

      $scope.getDisplayedTasks = function() {
        return $scope.tasks.filter(function(task) {
          if ($scope.tasksDisplayPreferences.isShowArchivedTask === '1' && task.is_archived == 1) {
            return true;
          }

          if ($scope.tasksDisplayPreferences.isShowEnabledTask === '1' && task.enabled == 1) {
            return true;
          }

          if (!($scope.tasksDisplayPreferences.isShowArchivedTask === '1')) {
            if ($scope.tasksDisplayPreferences.isShowDisabledTask === '1' && task.enabled == 0 && task.is_archived != 1) {
              return true;
            }
          } else if ($scope.tasksDisplayPreferences.isShowDisabledTask === '1' && task.enabled == 0) {
            return true;
          }

          return false;
        });
      };

      $scope.redrawTaskList = function() {
        $scope.displayTasks = $scope.getDisplayedTasks();
      };

      $scope.updateTaskData = function(taskId, taskData) {
        var indexTasks = $scope.tasks.findIndex(task => task.id === taskId);
        $scope.tasks[indexTasks] = taskData;
        $scope.redrawTaskList();
        $scope.$apply();
      };

      $scope.updatePreviousTaskOrder = function() {
        $scope.previousTaskOrder = $scope.displayTasks.map(task => task.id);
      };

      $scope.updateAllTasksOrder = function(movedTaskId, moveToTaskId) {
        var firstTaskIndex = $scope.tasks.findIndex(task => task.id === movedTaskId);
        var secondTaskIndex = $scope.tasks.findIndex(task => task.id === moveToTaskId);
        var firstTask = $scope.tasks[firstTaskIndex];
        $scope.tasks.splice(firstTaskIndex, 1);
        $scope.tasks.splice(secondTaskIndex, 0, firstTask);
      };

      $scope.updateDisplayTasksAfterMoving = function(movedTaskId, moveToTaskId) {
        var firstTaskIndex = $scope.displayTasks.findIndex(task => task.id === movedTaskId);
        var secondTaskIndex = $scope.displayTasks.findIndex(task => task.id === moveToTaskId);
        var firstTask = $scope.displayTasks[firstTaskIndex];
        $scope.displayTasks.splice(firstTaskIndex, 1);
        $scope.displayTasks.splice(secondTaskIndex, 0, firstTask);
      };

      $scope.applySortingTasks = function(movedTaskId, moveToTaskId) {
        if (movedTaskId === moveToTaskId) {
          return;
        }

        var beforeSortTaskOrder = $scope.tasks.map(task => task.id);
        $scope.updateAllTasksOrder(movedTaskId, moveToTaskId);
        $scope.updatePreviousTaskOrder();

        CRM.api3("Sqltask", "sort", {
          after_sort_tasks_order: $scope.tasks.map(task => task.id),
          before_sort_tasks_order: beforeSortTaskOrder
        }).done(function(result) {
          if (result.is_error) {
            CRM.alert(ts("Error sorting tasks. Refresh the page and try again."), ts("Error"), "error");
          }
        });
      };

      $scope.sortableOptions = {
        handle: ".handle-drag",
        placeholder: 'sql-task-manager-target-highlight-place',
        revert: 300,
        cursor: "move",
        scroll: true,
        update: $scope.updatePreviousTaskOrder,
        stop: function(event, helper) {
          var movedTaskId = helper.item.context.getAttribute("data-task-id");
          var moveToTaskId = $scope.previousTaskOrder[$scope.displayTasks.findIndex(task => task.id === movedTaskId)];
          $scope.applySortingTasks(movedTaskId, moveToTaskId);
        }
      };

      $scope.confirmRunTaskWithInputVariable = function(taskId) {
        var inputVariable = CRM.$('.sql-task-run-task-with-variable-dialog input.run-sql-task-input-variable').val();
        if (inputVariable === undefined || inputVariable.length < 1) {
          CRM.alert(ts("The 'variable' field is required. Please fill the input and try again."), ts("Variable field"), "warning");
        } else {
          window.waitSqlTaskId = taskId;
          $location.path("/sqltasks/run/" + taskId + '/' + inputVariable);
        }
      };

      $scope.confirmRunTask = function(taskId) {
        window.waitSqlTaskId = taskId;
        $location.path("/sqltasks/run/" + taskId);
      };

      $scope.moveTaskInList = function(movedTaskId, direction) {
        var movedTaskIndex = $scope.displayTasks.findIndex(task => task.id === movedTaskId);
        var moveToTaskId;
        var moveToTaskIndex;

        switch (direction) {
          case "up":
            moveToTaskIndex = movedTaskIndex - 1;
            if (moveToTaskIndex === -1) {
              break;
            }
            moveToTaskId = $scope.displayTasks[moveToTaskIndex].id;
            $scope.updateDisplayTasksAfterMoving(movedTaskId, moveToTaskId);
            $scope.applySortingTasks(movedTaskId, moveToTaskId);
            break;
          case "down":
            moveToTaskIndex = movedTaskIndex + 1;
            if ($scope.displayTasks[moveToTaskIndex] === undefined) {
              break;
            }
            moveToTaskId = $scope.displayTasks[moveToTaskIndex].id;
            $scope.updateDisplayTasksAfterMoving(movedTaskId, moveToTaskId);
            $scope.applySortingTasks(movedTaskId, moveToTaskId);
            break;
          case "top":
            moveToTaskId = $scope.displayTasks[0].id;
            $scope.updateDisplayTasksAfterMoving(movedTaskId, moveToTaskId);
            $scope.applySortingTasks(movedTaskId, moveToTaskId);
            break;
          case "bottom":
            moveToTaskId = $scope.displayTasks[$scope.displayTasks.length - 1].id;
            $scope.updateDisplayTasksAfterMoving(movedTaskId, moveToTaskId);
            $scope.applySortingTasks(movedTaskId, moveToTaskId);
            break;
          default:
        }
        $scope.updatePreviousTaskOrder();
      };

      $scope.onToggleEnablePress = function(taskId, value) {
        CRM.api3("Sqltask", "create", {
          id: taskId,
          enabled: value
        }).done(function(result) {
          var isEnabling = value === 1;
          if (result.values && !result.is_error) {
            CRM.alert(ts('Task has successfully ' + (isEnabling ? 'enabled' : 'disabled')), ts((isEnabling ? 'Enabling' : 'Disabling') + ' task'), "success");
            $scope.updateTaskData(taskId, result.values);
          } else {
            CRM.alert(result.error_message, ts('Error ' + (isEnabling ? 'enabling' : 'disabling' + ' task'), "error"));
          }
        });
      };

      $scope.onUnarchivePress = function(taskId) {
        CRM.api3("Sqltask", "unarchive", {id: taskId}).done(function(result) {
          if (result.values && !result.is_error) {
            CRM.alert(ts('Task was successfully unarchived'), ts("Unarchiving task"), "success");
            $scope.updateTaskData(taskId, result.values);
          } else {
            CRM.alert(result.error_message, ts("Error unarchiving task"), "error");
          }
        });
      };

      $scope.onArchivePress = function(taskId) {
        CRM.api3("Sqltask", "archive", {id: taskId}).done(function(result) {
          if (result.values && !result.is_error) {
            CRM.alert(ts('Task was successfully archived'), ts("Archiving task"), "success");
            $scope.updateTaskData(taskId, result.values);
          } else {
            CRM.alert(result.error_message, ts("Error archiving task"), "error");
          }
        });
      };

      $scope.onDeletePress = function(taskId) {
        $location.path("/sqltasks/delete/" + taskId);
      };

      $scope.addNewTask = function () {
        $location.url(`/sqltasks/configure/0?template=${$scope.selectTemplateModel.templateId}`);
      };

      function getCurrentDispatcherFrequency() {
        CRM.api3("Job", "get", {
          sequential: 1,
          api_entity: "Sqltask",
          api_action: "execute",
          is_active: 1
        }).done(function(result) {
          var jobs = result.values;
          if (jobs.length > 0) {
            jobs.forEach(job => {
              switch (job.run_frequency) {
                case "Always":
                  $scope.dispatcher_frequency = "Always";
                  break;
                case "Hourly":
                  if ($scope.dispatcher_frequency === null || $scope.dispatcher_frequency === "Daily") {
                    $scope.dispatcher_frequency = "Hourly";
                  }
                  break;
                case "Daily":
                  if ($scope.dispatcher_frequency === null) {
                    $scope.dispatcher_frequency = "Daily";
                  }
                  break;
                default:
                  console.log(`Unexpected run frequency: ${job.run_frequency}`);
                  break;
              }
            });
            $scope.$apply();
          }
        });
      }

    });

  angular.module(moduleName).directive("select2", () => ({
    restrict: "E",
    templateUrl: "~/sqlTaskManager/select2.html",
    scope: {
      id: "<",
      isRequired: "<",
      model: "=",
      name: "<",
      options: "<",
    },
    controller: async ($scope) => {
      while (true) {
        const component = $(`select[data-directive="select2"]#${$scope.id}`);

        if (component.length > 0) {
          component.select2();
          break;
        }

        await new Promise(resolve => setTimeout(resolve, 200));
      }
    }
  }));

})(angular, CRM.$, CRM._);
