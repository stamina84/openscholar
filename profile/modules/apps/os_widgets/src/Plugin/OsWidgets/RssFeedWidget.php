<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Core\Url;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class RssFeedWidget.
 *
 * @OsWidget(
 *   id = "rss_feed_widget",
 *   title = @Translation("RSS Feed")
 * )
 */
class RssFeedWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $field_is_show_all_content_values = $block_content->get('field_is_show_all_content')->getValue();

    $types = [];
    foreach ($block_content->field_content_to_display as $item) {
      if (!empty($item->value)) {
        $types[] = $item->value;
      }
    }
    $argument_types = '';
    if (empty($field_is_show_all_content_values[0]['value']) && count($types)) {
      $argument_types = implode("+", $types);
    }
    $build['rss_feed'] = [
      '#title' => t('Subscribe'),
      '#type' => 'link',
      '#url' => Url::fromRoute('view.os_feeds.feed_1', ['arg_0' => $argument_types], ['absolute' => TRUE]),
      '#attributes' => [
        'class' => [
          'rss-feed-link',
        ],
      ],
    ];
    $build['rss_feed']['#attached']['library'][] = 'os_widgets/rss_feed_copy';
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
      ]);
      $block->save();
      $group->addContent($block, "group_entity:block_content");
      $block_uuid = $block->uuid();
      $this->saveWidgetLayout($row, $block_uuid);
    }
  }

}
