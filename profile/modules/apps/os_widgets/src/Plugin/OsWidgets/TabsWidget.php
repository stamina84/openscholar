<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\block_content\Entity\BlockContent;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class TabsWidget.
 *
 * @OsWidget(
 *   id = "tabs_widget",
 *   title = @Translation("Tabs")
 * )
 */
class TabsWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $contents = [];
    $add_widgets = $block_content->get('field_widget_collection')->getValue();

    foreach ($add_widgets as $widget) {
      $bid = $widget['target_id'];
      $block = BlockContent::load($bid);
      $render = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
      $contents[$bid] = $render;
    }

    $build['tabs'] = [
      '#theme' => 'os_widgets_tabs',
      '#contents' => $contents,
    ];
  }

}
