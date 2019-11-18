<?php

namespace Drupal\vsite\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\views\ViewExecutable;

/**
 * Interface for classes managing App plugin system.
 */
interface AppManagerInterface extends PluginManagerInterface {

  /**
   * Gets App for bundle.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $bundle
   *   Bundle name.
   *
   * @return string
   *   App name.
   */
  public function getAppForBundle(string $entity_type_id, string $bundle) : string;

  /**
   * Get Apps for view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View entity.
   *
   * @return array
   *   Array of App ids.
   */
  public function getAppsForView(ViewExecutable $view) : array;

  /**
   * Get group permissions for an app.
   *
   * @param string $app_id
   *   The app id.
   *
   * @return array
   *   The group permissions.
   */
  public function getViewContentGroupPermissionsForApp(string $app_id): array;

  /**
   * Get all bundles from active apps.
   *
   * @return array
   *   Return array of bundle keys.
   */
  public function getBundlesFromApps(): array;

}
