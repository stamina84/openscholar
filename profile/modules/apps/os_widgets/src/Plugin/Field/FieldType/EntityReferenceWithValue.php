<?php

namespace Drupal\os_widgets\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Class EntityReferenceWithValue.
 *
 *   A custom fieldType to have entity_reference field
 *   with a custom value to save custom labels.
 *
 * @FieldType(
 *   id = "entity_reference_with_value",
 *   label = @Translation("Entity reference with value"),
 *   category = @Translation("Reference with label"),
 *   default_widget = "list_collection_widget",
 *   default_formatter = "list_collection_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class EntityReferenceWithValue extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['section_title'] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['section_title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Collection section title'))
      ->setRequired(FALSE);

    return $properties;
  }

}
