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

  angular.module(moduleName).service('warningMessageService', function() {
    this.doAction = function(action, context, actionData, continueCallback, cancelCallback) {
      CRM.api3('Sqltask', 'get_warning_message', {
        "action": action,
        "context": context,
        "action_data": actionData
      }).then(function(result) {
        if (result.is_error === 1) {
          CRM.status('Error via trying to do "' + action + '"', 'error');
          console.error('Sqltask->get_warning_message error:');
          console.error(result.error_message);
        } else {
          if (result['values']['isAllowDoAction']) {
            continueCallback();
          } else {
            showWarningModalWindow(result, continueCallback, cancelCallback);
          }
        }
      }, function(error) {
        console.error('Sqltask->get_warning_message error:');
        console.error(error);
      });
    }

    var showWarningModalWindow = function(result, continueCallback, cancelCallback) {
      var warningWindow = result['values']['warningWindow'];
      var buttonSettings = [
        {
          text: warningWindow['noButtonText'],
          icon: warningWindow['noButtonIcon'],
          class: warningWindow['noButtonClasses'],
          click: function () {
            cancelCallback();
            $(this).dialog("close");
          }
        }
      ];
      if (warningWindow['isShowYesButton']) {
        buttonSettings.push({
          text: warningWindow['yesButtonText'],
          icon: warningWindow['yesButtonIcon'],
          class: warningWindow['yesButtonClasses'],
          click: function () {
            continueCallback();
            $(this).dialog("close");
          }
        })
      }

      CRM.confirm({
        title: warningWindow['title'],
        message: warningWindow['message'],
        options: {yes: warningWindow['yesButtonText'], no: warningWindow['noButtonText']},
        open: function(event, ui) {
          //hide 'close' button, because cannot run 'cancelCallback' when user click on 'close' button
          $(this).parent().children().children('.ui-dialog-titlebar-close').hide();
        },
      }).dialog("option", "buttons", buttonSettings);
    };
  });

  angular
    .module(moduleName)
    .controller("sqlTaskManagerCtrl", function($scope, $location, highlightTaskId, $timeout, warningMessageService) {
      //to add ability to use styles only for this page
      setTimeout(function() {
        CRM.$('body').addClass('sql-task-body-page-wrapper');
      }, 0);

      $scope.url = CRM.url;
      $scope.infoMessages = [];
      $scope.taskIdWithOpenPanel = null;
      $scope.$location = $location;
      $scope.tasks = [];
      $scope.displayTasks = [];
      $scope.previousTaskOrder = [];
      $scope.isTasksLoading = false;
      $scope.getBooleanFromNumber = function(number) {return !!Number(number);};
      $scope.getNumberFromString = function(stringValue) {return Number(stringValue);};
      $scope.showPanelForTaskId = function(taskId) {$scope.taskIdWithOpenPanel = taskId;};
      $scope.ts = CRM.ts();
      $scope.resourceBaseUrl = CRM.config.resourceBase;
      $scope.tasksDisplayPreferences = {
        'isShowArchivedTask' : '0',
        'isShowEnabledTask' : '1',
        'isShowDisabledTask' : '1',
      };
      $scope.templateOptions = [];
      $scope.selectTemplateModel = { templateId: undefined };
      $scope.getInfoMessages = function() {
        CRM.api3('Sqltask', 'get_info_messages').then(function(result) {
          $scope.infoMessages = result.values;
          $scope.$apply();
        }, function(error) {
          console.error('Sqltask.get_info_messages error: error');
          console.error(error);
        });
      };

      getAllTasks();
      getAllTemplates();
      getDefaultTemplate();
      $scope.getInfoMessages();

      function getAllTasks() {
        $scope.isTasksLoading = true;

        CRM.api4("SqlTask", "get", {
          select: ["*"],
          orderBy: { "weight": "ASC" },
        }).then(tasks => {
          $scope.tasks = tasks.map(formatTaskData);
          $scope.redrawTaskList();
          $scope.updatePreviousTaskOrder();
          $scope.isTasksLoading = false;
          $scope.$apply();
          $scope.handleHighlightTask(highlightTaskId);
        });
      }

      function formatTaskData(task) {
        const desc = task.description ?? "";
        const lastExec = task.last_execution ?? "never";

        return {
          ...task,
          is_archived: task.archive_date !== null,
          last_executed: lastExec,
          last_runtime: renderDuration(task.last_runtime),
          schedule_label: mapToScheduleLabel(task.scheduled),
          short_desc: desc.length > 64 ? `${desc.substring(0, 64)}...` : desc,
        };
      }

      function mapToScheduleLabel(scheduled) {
        switch (scheduled) {
          case "always": return "always";
          case "hourly": return "every hour";
          case "daily": return "every day (after midnight)";
          case "weekly": return "every week";
          case "monthly": return "every month";
          case "yearly": return "annually";
          default: return "never";
        }
      }

      function renderDuration(milliseconds) {
        if (milliseconds === null) return "n/a";

        if (milliseconds < 60e3) {
          const seconds = (milliseconds / 1000).toFixed(3);
          return `${seconds}s`;
        }

        const minutes = Math.floor(milliseconds / 60e3);
        const seconds = Math.floor((milliseconds % 60e3) / 1000).toString(10).padStart(2, "0");
        return `${minutes}:${seconds} min`;
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

          if (result.result !== 'undefined') {
            $scope.selectTemplateModel.templateId = result.result.toString();
          }

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
          if ($scope.tasksDisplayPreferences.isShowArchivedTask === '1' && task.is_archived) {
            return true;
          }

          if ($scope.tasksDisplayPreferences.isShowEnabledTask === '1' && task.enabled) {
            return true;
          }

          if ($scope.tasksDisplayPreferences.isShowArchivedTask !== '1') {
            if ($scope.tasksDisplayPreferences.isShowDisabledTask === '1' && !task.enabled && !task.is_archived) {
              return true;
            }
          } else if ($scope.tasksDisplayPreferences.isShowDisabledTask === '1' && !task.enabled) {
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
        $scope.tasks[indexTasks] = formatTaskData(taskData);
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
          var movedTaskId = parseInt(helper.item.context.getAttribute("data-task-id"));
          var moveToTaskId = $scope.previousTaskOrder[$scope.displayTasks.findIndex(task => task.id === movedTaskId)];
          $scope.applySortingTasks(movedTaskId, moveToTaskId);
        }
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
        var action = value === 1 ? 'enableTask' : 'disableTask';
        warningMessageService.doAction(action,'sqlTaskManager',{'taskId': taskId}, function () {
          $scope.onToggleEnablePressApiCall(taskId, value);
        },
        function () {});
      };

      $scope.onToggleEnablePressApiCall = function(taskId, value) {
        CRM.api4("SqlTask", "update", {
          reload: true,
          values: { "enabled": value },
          where: [["id", "=", taskId]],
        }).then((results) => {
          $scope.updateTaskData(taskId, results[0]);

          CRM.alert(
            ts(`Task has successfully been ${value ? "enabled" : "disabled"}`),
            ts(`${value ? "Enabled" : "Disabled"} task`),
            "success",
          );
        }).catch((error) => {
          CRM.alert(
            error?.error_message ?? "",
            ts(`Error ${value ? "enabling" : "disabling"} task`),
            "error",
          );
        });
      }

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

      $scope.onArchivePressApiCall = function(taskId) {
        CRM.api3("Sqltask", "archive", {id: taskId}).done(function(result) {
          if (result.values && !result.is_error) {
            CRM.alert(ts('Task was successfully archived'), ts("Archiving task"), "success");
            $scope.updateTaskData(taskId, result.values);
          } else {
            CRM.alert(result.error_message, ts("Error archiving task"), "error");
          }
        });
      };

      $scope.onArchivePress = function(taskId) {
        warningMessageService.doAction('archiveTask','sqlTaskManager',{'taskId': taskId}, function () {
            $scope.onArchivePressApiCall(taskId);
          },
          function () {});
      };

      $scope.showWhereTaskIsUsed = function(taskId) {
        warningMessageService.doAction('showWhereTaskIsUsed','sqlTaskManager',
          {'taskId': taskId},
          function () {},
          function () {});
      };

      $scope.onDeletePressRedirect = function(taskId) {
        $location.path("/sqltasks/delete/" + taskId);
        $scope.$apply();
      };

      $scope.onDeletePress = function(taskId) {
        warningMessageService.doAction('deleteTask','sqlTaskManager',{'taskId': taskId}, function () {
            $scope.onDeletePressRedirect(taskId);
          },
          function () {});
      };

      $scope.onExecutePress = function(taskId) {
        $location.path("/sqltasks/run/" + taskId);
      };

      $scope.onExecutePress = function(taskId) {
        $location.path("/sqltasks/run/" + taskId);
      };

      $scope.addNewTask = function () {
        $location.url(`/sqltasks/configure/0?template=${$scope.selectTemplateModel.templateId}`);
      };
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
