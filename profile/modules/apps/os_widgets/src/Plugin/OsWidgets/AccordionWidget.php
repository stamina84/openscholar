<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\block_content\Entity\BlockContent;

/**
 * Class AccordionWidget.
 *
 * @OsWidget(
 *   id = "accordion_widget",
 *   title = @Translation("Accordion")
 * )
 */
class AccordionWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $contents = [];
    $add_widgets = $block_content->get('field_widget_collection')->getValue();
    $collapsible = $block_content->get('field_all_panels_closed')->getValue();
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

    $build['accordion'] = [
      '#theme' => 'os_widgets_accordion',
      '#bid' => $block_content->id(),
      '#contents' => $contents,
      '#collapsible' => $collapsible[0]['value'],
    ];
    $build['#attached']['library'][] = 'os_base/accordion';
  }

}
