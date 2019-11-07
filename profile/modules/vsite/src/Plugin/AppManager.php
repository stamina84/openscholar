<?php

namespace Drupal\vsite\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views\ViewExecutable;

/**
 * Manager for the App plugin system.
 */
class AppManager extends DefaultPluginManager implements AppManagerInterface {

  /**
   * Constructs an AppManager object.
   *
   * @param \Traversable $namespaces
   *   Namespace object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/App',
      $namespaces,
      $module_handler,
      'Drupal\vsite\AppInterface',
      'Drupal\vsite\Annotation\App'
    );

    $this->alterInfo('app_info');
    $this->setCacheBackend($cache_backend, 'app_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getAppForBundle(string $entity_type_id, string $bundle): string {
    $defs = $this->getDefinitions();
    // @TODO: It is possible to $app can be multiple.
    $app = '';
    foreach ($defs as $d) {
      if ($d['entityType'] != $entity_type_id) {
        continue;
      }
      if (isset($d['bundle']) && \in_array($bundle, $d['bundle'], TRUE) || $bundle == '*') {
        $app = $d['id'];
      }
    }

    if ($app) {
      return $app;
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getAppsForView(ViewExecutable $view): array {
    $defs = $this->getDefinitions();
    $apps = [];
    foreach ($defs as $d) {
      if (!empty($d['viewsTabs'])) {
        foreach ($d['viewsTabs'] as $view_id => $displays) {
          if ($view_id != $view->id()) {
            continue;
          }
          foreach ($displays as $display) {
            if ($display != $view->current_display) {
              continue;
            }
            $apps[] = $d['id'];
          }
        }
      }
    }

    return $apps;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $provider = $this->extractProviderFromDefinition($plugin_definition);
      if ($provider && !in_array($provider, ['core', 'component']) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewContentGroupPermissionsForApp(string $app_id): array {
    $group_permissions = [];

    if ($app_id === 'publications') {
      $group_permissions[] = 'view group_entity:bibcite_reference entity';
    }
    else {
      $definition = $this->getDefinition($app_id);

      foreach ($definition['bundle'] as $bundle) {
        $group_permissions[] = "view group_node:$bundle entity";
      }
    }

    return $group_permissions;
  }

}
