<?php

namespace Drupal\os_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the common title to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "add_title_as_text",
 *   label = @Translation("Custom Title (text) field"),
 *   description = @Translation("Adds common title for all entites to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddTitleasText extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Custom Title (text)'),
        'description' => $this->t('Common Title for all entities.'),
        'type' => 'text',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['custom_title'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $object = $item->getOriginalObject()->getValue();
    $custom_bundle = $object->getEntityTypeId();

    $custom_title = '';
    if ($custom_bundle == 'node') {
      $custom_title = $object->getTitle();
    }
    elseif ($custom_bundle == 'bibcite_reference') {
      $custom_title = $object->get('title')->getValue()[0]['value'];
    }

    $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'custom_title');

    foreach ($fields as $field) {
      $field->addValue($custom_title);
    }

  }

}