<?php

namespace Drupal\os_widgets;

/**
 * Class OsWidgetsContext to handle active apps.
 *
 * @package Drupal\os_widgets
 */
class OsWidgetsContext implements OsWidgetsContextInterface {

  private $appIds = [];

  /**
   * {@inheritdoc}
   */
  public function addApp($app_id): void {
    if (in_array($app_id, $this->appIds)) {
      return;
    }
    $this->appIds[] = $app_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveApps(): array {
    return $this->appIds;
  }

}
