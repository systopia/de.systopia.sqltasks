(function(angular, $, _) {
  var moduleName = "sqlTaskManager";
  var moduleDependencies = ["ngRoute", "ui.sortable"];
  angular.module(moduleName, moduleDependencies);

  angular.module(moduleName).config([
    "$routeProvider",
    function($routeProvider) {
      $routeProvider.when("/sqltasks/manage", {
        controller: "sqlTaskManagerCtrl",
        templateUrl: "~/sqlTaskManager/sqlTaskManager.html"
      });
    }
  ]);

  angular
    .module(moduleName)
    .controller("sqlTaskManagerCtrl", function($scope, $location) {
      $scope.taskIdWithOpenPanel = null;
      $scope.showPanelForTaskId = function(taskId) {
        $scope.taskIdWithOpenPanel = taskId;
      };
      $scope.ts = CRM.ts();
      $scope.dispatcher_frequency = null;
      $scope.resourceBaseUrl = CRM.config.resourceBase;
      getAllTasks();
      getCurrentDispatcherFrequency();

      $scope.sortableOptions = {
        handle: ".handle-drag",
        update: function() {
          const oldOrder = $scope.tasks.map(task => {
            return task.id;
          });
          $scope.oldOrder = oldOrder;
          $scope.$apply();
        },
        stop: function() {
          const newOrder = $scope.tasks.map(task => {
            return task.id;
          });
          CRM.api3("Sqltask", "sort", {
            data: newOrder,
            task_screen_order: $scope.oldOrder
          }).done(function(result) {
            if (result.is_error) {
              CRM.alert(ts("Error sorting tasks."), ts("Error"), "error");
            }
          });
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

      $scope.moveTaskInList = function(taskId, value) {
        var index = $scope.tasks.findIndex(el => el.id === taskId);
        var arrayOfIds = [];
        $scope.tasks.forEach(task => arrayOfIds.push(task.id));
        if (index !== -1 && arrayOfIds.length) {
          var newOrder = swapElementsByAction(value, arrayOfIds, index);
          if (newOrder !== null) {
            CRM.api3("Sqltask", "sort", {
              data: newOrder,
              task_screen_order: arrayOfIds
            }).done(function(result) {
              if (result.values && !result.is_error) {
                $scope.tasks = swapElementsByAction(value, $scope.tasks, index);
                $scope.$apply();
              } else {
                CRM.alert(
                  ts("Error changing tasks order."),
                  ts("Error"),
                  "error"
                );
              }
            });
          }
        }
      };

      $scope.getBooleanFromNumber = getBooleanFromNumber;

      function getBooleanFromNumber(number) {
        return !!Number(number);
      }

      function swapElementsByAction(action, initialArray, index) {
        var array = initialArray.slice();
        var newIndex = getNewIndexByAction(action, index, array);
        if (newIndex !== null) {
          if (action === "up" || action === "down") {
            if (array[newIndex] === undefined) {
              return null;
            }
            var tmp = array[newIndex];
            array[newIndex] = array[index];
            array[index] = tmp;
          } else if (action === "bottom" || action === "top") {
            var cuttedElement = array.splice(index, 1);
            if (action === "bottom" && cuttedElement.length > 0) {
              array.push(cuttedElement[0]);
            } else if (action === "top" && cuttedElement.length > 0) {
              array.unshift(cuttedElement[0]);
            }
          }
        }
        return array;
      }

      function getNewIndexByAction(action, index, lastElementIndex) {
        switch (action) {
          case "up":
            return index - 1;
          case "down":
            return index + 1;
          case "top":
            return 0;
          case "bottom":
            return lastElementIndex - 1;
          default:
            return null;
        }
      }

      $scope.onToggleEnablePress = function(taskId, value) {
        var index = $scope.tasks.findIndex(el => el.id === taskId);
        if (index !== -1) {
          CRM.api3("Sqltask", "create", {
            id: taskId,
            enabled: value
          }).done(function(result) {
            if (result.values && !result.is_error) {
              CRM.alert(
                ts("Task enabled / disabled successfully"),
                ts("Enable / disable task"),
                "success"
              );
              $scope.tasks[index] = result.values;
              $scope.$apply();
            } else {
              CRM.alert(
                ts("Error enabling / disabing task"),
                ts("Enable / disable task"),
                "error"
              );
            }
          });
        }
      };

      $scope.onDeletePress = function(taskId) {
        $location.path("/sqltasks/delete/" + taskId);
      };

      $scope.getNumberFromString = function(stringValue) {
        return Number(stringValue);
      };

      function getAllTasks() {
        CRM.api3("Sqltask", "getalltasks").done(function(result) {
          $scope.tasks = result.values;
          $scope.$apply();
        });
      }

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
})(angular, CRM.$, CRM._);
