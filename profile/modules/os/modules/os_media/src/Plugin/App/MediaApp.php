<?php

namespace Drupal\os_media\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Plugin for the Media App.
 *
 * @App(
 *   title = @Translation("Media"),
 *   canDisable = true,
 *   entityType = "media",
 *   id = "media",
 *   listPageRoute = "view.os_media.page_1"
 * )
 */
class MediaApp extends AppPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypes() {
    return '*';
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateLinks() {
    return [];
  }

}
