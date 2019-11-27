<?php

namespace Drupal\os_widgets\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the entity_reference_with_value formatter.
 *
 * @FieldFormatter(
 *   id = "list_collection_formatter",
 *   label = @Translation("List collection"),
 *   field_types = {
 *     "enttty_reference_with_value",
 *   },
 * )
 */
class ListCollectionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = ['#markup' => $item->value];
    }

    return $element;
  }

}
