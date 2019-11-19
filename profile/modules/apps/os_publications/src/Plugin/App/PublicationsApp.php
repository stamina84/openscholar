<?php

namespace Drupal\os_publications\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Publications app.
 *
 * @App(
 *   title = @Translation("Publication"),
 *   canDisable = true,
 *   entityType = "bibcite_reference",
 *   viewsTabs = {
 *     "publications" = {
 *       "page_1",
 *       "page_2",
 *       "page_3",
 *       "page_4",
 *     },
 *   },
 *   id = "publications"
 * )
 */
class PublicationsApp extends AppPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateLinks() {
    return [
      'publication' => [
        'menu_name' => 'control-panel',
        'route_name' => 'entity.bibcite_reference.add_page',
        'parent' => 'cp.content.add',
        'title' => $this->getTitle(),
      ],
    ];
  }

}
