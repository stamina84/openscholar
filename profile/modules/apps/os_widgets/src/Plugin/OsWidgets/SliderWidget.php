<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\block_content\Entity\BlockContent;

/**
 * Class SliderWidget.
 *
 * @OsWidget(
 *   id = "slider_widget",
 *   title = @Translation("Slider")
 * )
 */
class SliderWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $contents = [];
    $add_widgets = $block_content->get('field_widget_collection')->getValue();
    $count = 0;
    foreach ($add_widgets as $widget) {
      $bid = $widget['target_id'];
      $block = BlockContent::load($bid);
      $render = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
      $contextual_link_placeholder = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($render['#contextual_links']),
      ];
      $section_title = $widget['section_title'];

      $contents[$count . '-' . $bid] = [
        'widget' => $render,
        'contextual_link' => $contextual_link_placeholder,
        'section_title' => $section_title,
      ];
      $count++;
    }

    $build['slider'] = [
      '#theme' => 'os_widgets_slider',
      '#bid' => $block_content->id(),
      '#contents' => $contents,
      '#slider_height' => $block_content->get('field_slider_height')->getString(),
      '#display_scrollbar' => (bool) $block_content->get('field_display_scrollbar')->getString() == 1,
    ];

    $settings = [
      'id' => 'slider-' . $block_content->id(),
      'field_display_arrows' => (bool) $block_content->get('field_display_arrows')->getString() == 1,
      'field_duration' => $block_content->get('field_duration')->getString(),
      'field_transition_speed' => $block_content->get('field_transition_speed')->getString(),
    ];

    $build['#attached']['drupalSettings']['sliderWidget'][$block_content->id()] = $settings;
    $build['#attached']['library'][] = 'os_widgets/sliderWidget';
  }

}
