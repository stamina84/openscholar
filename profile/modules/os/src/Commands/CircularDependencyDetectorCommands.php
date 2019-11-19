<?php

namespace Drupal\os\Commands;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Commands\DrushCommands;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\cdd\CircularDependencyTaskLoader;

/**
 * Class CircularDependencyDetectorCommands.
 */
class CircularDependencyDetectorCommands extends DrushCommands implements ContainerInjectionInterface, BuilderAwareInterface {

  use CircularDependencyTaskLoader;
  use TaskAccessor;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleExtensionList $moduleExtensionList) {
    parent::__construct();
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module')
    );
  }

  /**
   * Validate module dependencies.
   *
   * @command validate:module-dependencies
   * @bootstrap configuration
   */
  public function validateModuleDependencies(): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addCode(function (RoboStateData $data): int {
        $data['moduleDependencies'] = $this->collectModuleDependencies();

        return 0;
      })
      ->addTask(
        $this
          ->taskCircularDependencyDetector()
          ->setItemLabel('module')
          ->deferTaskConfiguration('setItems', 'moduleDependencies')
      );
  }

  /**
   * Collect module dependencies.
   */
  protected function collectModuleDependencies(): array {
    $modules = $this->moduleExtensionList->reset()->getList();
    $dependency_graph = [];
    foreach ($modules as $fromName => $module) {
      if (!isset($dependency_graph[$fromName])) {
        $dependency_graph[$fromName] = [];
      }
      if (empty($module->info['dependencies'])) {
        continue;
      }
      $dependency_graph[$fromName] = $this->parseModuleNamesFromDependencies($module->info['dependencies']);
      foreach ($dependency_graph[$fromName] as $toName) {
        if (!isset($dependency_graph[$toName])) {
          $dependency_graph[$toName] = [];
        }
      }
    }

    return $dependency_graph;
  }

  /**
   * Parse module names.
   */
  protected function parseModuleNamesFromDependencies(array $dependencies): array {
    $moduleNames = [];

    foreach ($dependencies as $dependency) {
      $moduleNames[] = $this->parseModuleNameFromDependency($dependency);
    }

    return $moduleNames;
  }

  /**
   * Parse module name without prefix and version.
   */
  protected function parseModuleNameFromDependency(string $dependency): string {
    $pattern = '/^((?P<vendor>[^:]+):){0,1}(?P<name>[^\s]+)(\s+(?P<version>.+)){0,1}$/';

    $matches = ['name' => ''];
    preg_match($pattern, $dependency, $matches);

    return $matches['name'];
  }

}
