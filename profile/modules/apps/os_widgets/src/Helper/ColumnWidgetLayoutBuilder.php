<?php

declare(strict_types = 1);

namespace Drupal\os_widgets\Helper;

use Drupal\layout_builder\SectionComponent;

/**
 * Helps building the layout for column widgets.
 */
final class ColumnWidgetLayoutBuilder {

  /**
   * Returns embeddable section components from widgets.
   *
   * @param \Drupal\block_content\Entity\BlockContent[] $widgets
   *   The widgets.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   The section components.
   */
  public function widgetsToSectionComponents(array $widgets): array {
    $section_components = [];

    foreach ($widgets as $widget) {
      /** @var \Drupal\block\Entity\Block[] $block_instances */
      $block_instances = $widget->getInstances();

      $block = reset($block_instances);
      $uuid = $block->uuid();
      $section_components[$uuid] = new SectionComponent($uuid, 'first', [
        'id' => "block_content:{$widget->uuid()}",
      ]);
    }

    return $section_components;
  }

}
