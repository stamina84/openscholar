<?php

namespace Drupal\os_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's Group to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "add_group_as_text",
 *   label = @Translation("Group (text) field"),
 *   description = @Translation("Adds the item's Group to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddGroupAsText extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Group (text)'),
        'description' => $this->t('Group for entity.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['custom_search_group'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $node = $item->getOriginalObject()->getValue();
    ksm($node);
  }

}
