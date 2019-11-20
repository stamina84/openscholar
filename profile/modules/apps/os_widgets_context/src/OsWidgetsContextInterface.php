<?php

namespace Drupal\os_widgets_context;

/**
 * Interface OsWidgetsContextInterface.
 *
 * @package Drupal\os_widgets
 */
interface OsWidgetsContextInterface {

  /**
   * Add app.
   *
   * @param string $app_id
   *   App id.
   */
  public function addApp(string $app_id) : void;

  /**
   * Get collected active apps.
   *
   * @return array
   *   List of apps.
   */
  public function getActiveApps() : array;

  /**
   * Reset collected apps.
   */
  public function resetApps() : void;

}
