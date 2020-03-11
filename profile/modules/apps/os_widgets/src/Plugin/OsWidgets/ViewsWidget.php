<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\group\Entity\GroupInterface;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class ViewsWidget.
 *
 * @OsWidget(
 *   id = "views_widget",
 *   title = @Translation("Views")
 * )
 */
class ViewsWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function createWidget(array $data, $bundle, GroupInterface $group): void {
    $storage = $this->entityTypeManager->getStorage('block_content');
    foreach ($data as $row) {
      $block = $storage->create([
        'type' => $bundle,
        'info' => $row['Info'],
        'field_widget_title' => $row['Title'],
        'field_view' => [
          'target_id' => $row['View'],
          'display_id' => $row['Display'],
        ],
      ]);
      $block->save();
      $group->addContent($block, "group_entity:block_content");
      $block_uuid = $block->uuid();
      $this->saveWidgetLayout($row, $block_uuid);
    }
  }

}
