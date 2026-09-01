<?php

declare(strict_types = 1);

namespace Civi\Sqltasks;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use CRM_Sqltasks_ExtensionUtil as E;

class CompilerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    if ($container->hasDefinition('action_provider')) {
      $action_provider_definition = $container->getDefinition('action_provider');

      $action_provider_definition->addMethodCall('addAction', [
        'SqltasksRunSQLTask',
        'Civi\Sqltasks\Actions\RunSQLTask',
        E::ts('Run SQL Task'),
      ]);
    }
  }

}
