<?php

namespace Civi\Sqltasks\Actions;

use \Civi\ActionProvider\Action\AbstractAction;
use \Civi\ActionProvider\Exception\ExecutionException;
use \Civi\ActionProvider\Parameter\ParameterBagInterface;
use \Civi\ActionProvider\Parameter\SpecificationBag;
use \Civi\ActionProvider\Parameter\Specification;

use CRM_Sqltasks_ExtensionUtil as E;

class RunSQLTask extends AbstractAction {

  /**
   * Run the action
   *
   * @param ParameterInterface $parameters
   * @param ParameterBagInterface $output
   * @return void
   */
  protected function doAction(ParameterBagInterface $parameters, ParameterBagInterface $output) {
    // Configuration
    $task_id = $this->configuration->getParameter('task_id');
    $log_to_file = $this->configuration->getParameter('log_to_file');
    $return_parameter = $this->configuration->getParameter('return_parameter');

    // Parameters
    $input_values = $parameters->getParameter('input_values');

    try {
      civicrm_api3('Sqltask', 'get', [ 'id' => $task_id ]);
    } catch (\Exception $ex) {
      throw new ExecutionException(E::ts("Task with ID '%1' not found", [1 => $task_id]));
    }

    $exec_params = [
      'id'          => $task_id,
      'input_val'   => $input_values,
      'log_to_file' => $log_to_file,
    ];

    if (is_array($input_values)) {
      if (count($input_values) < 1) {
        $exec_params['input_val'] = NULL;
      } else if (count($input_values) < 2) {
        $exec_params['input_val'] = $input_values[0];
      }
    }

    try {
      $exec_result = civicrm_api3('Sqltask', 'execute', $exec_params);

      $output->setParameter('error_count', $exec_result['values']['error_count']);
      $output->setParameter('logs', $exec_result['values']['logs']);
      $output->setParameter('runtime', $exec_result['values']['runtime']);

      if (isset($return_parameter) && isset($exec_result['values'][$return_parameter])) {
        $output->setParameter('return_value', $exec_result['values'][$return_parameter]);
      }
    } catch (\Exception $ex) {
      throw new ExecutionException(E::ts('Task execution failed'));
    }
  }

  /**
   * @return SpecificationBag
   */
  public function getConfigurationSpecification() {
    return new SpecificationBag([
      new Specification(
        'task_id',        // string $name
        'Integer',        // string $dataType
        E::ts('Task ID'), // string $title
        TRUE,             // bool $required
        NULL,             // mixed $defaultValue
        NULL,             // string|null $fkEntity
        NULL,             // array $options
        FALSE             // bool $multiple
      ),
      new Specification(
        'log_to_file',
        'Boolean',
        E::ts('Log to file?'),
        FALSE,
        FALSE,
        NULL,
        NULL,
        FALSE
      ),
      new Specification(
        'return_parameter',
        'String',
        E::ts('Return Parameter'),
        FALSE,
        NULL,
        NULL,
        NULL,
        FALSE
      ),
    ]);
  }

  /**
   * @return SpecificationBag
   */
  public function getParameterSpecification() {
    return new SpecificationBag([
      new Specification(
        'input_values',
        'String',
        E::ts('Input values'),
        FALSE,
        NULL,
        NULL,
        NULL,
        TRUE
      ),
    ]);
  }

  /**
   * @return SpecificationBag
   */
  public function getOutputSpecification() {
    return new SpecificationBag([
      new Specification(
        'error_count',
        'Integer',
        E::ts('Error count'),
        FALSE,
        NULL,
        NULL,
        NULL,
        FALSE
      ),
      new Specification(
        'logs',
        'String',
        E::ts('Execution logs'),
        FALSE,
        NULL,
        NULL,
        NULL,
        FALSE
      ),
      new Specification(
        'runtime',
        'String',
        E::ts('Runtime'),
        FALSE,
        NULL,
        NULL,
        NULL,
        FALSE
      ),
      new Specification(
        'return_value',
        'String',
        E::ts('Return value'),
        FALSE,
        NULL,
        NULL,
        NULL,
        FALSE
      ),
    ]);
  }

}
