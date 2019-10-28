<?php

namespace Drupal\os_widgets;

/**
 * Interface OsWidgetsContextInterface.
 *
 * @package Drupal\os_widgets
 */
interface OsWidgetsContextInterface {

  /**
   * Add bundle.
   *
   * @param string $bundle
   *   Bundle name with entity name (eg. "node:article").
   */
  public function addBundle(string $bundle) : void;

  /**
   * Get collected bundles.
   *
   * @return array
   *   List of bundles with entity name.
   */
  public function getBundles() : array;

}
