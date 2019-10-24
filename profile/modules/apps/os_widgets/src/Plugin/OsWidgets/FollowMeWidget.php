<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class FollowMeWidget.
 *
 * @OsWidget(
 *   id = "follow_me_widget",
 *   title = @Translation("Follow Me")
 * )
 */
class FollowMeWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $profile_links = os_widgets_get_profile_url_links($block_content);
    unset($profile_links['links']['blank']);
    $display_social = $block_content->get('field_display_social')->getString();
    $rss_feed = $block_content->get('field_add_link_to_rss_feed_page')->getString();

    $build['follow_me'] = [
      '#theme' => 'os_widgets_follow_me',
      '#profile_links' => $profile_links['links'],
      '#display_social' => $display_social,
      '#rss_feed' => $rss_feed,
    ];
    $build['follow_me']['#attached']['library'][] = 'os_widgets/followMeWidgetCss';
  }

}
