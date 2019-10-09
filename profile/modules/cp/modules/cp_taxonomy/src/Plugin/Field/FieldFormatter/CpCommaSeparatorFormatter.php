<?php

namespace Drupal\cp_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'CpCommaSeparator' formatter.
 *
 * @FieldFormatter(
 *   id = "cp_taxonomy_comma_separator",
 *   label = @Translation("CpCommaSeparator"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class CpCommaSeparatorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => t('See also'),
      'number_of_characters' => 94,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field label'),
      '#default_value' => $this->getSetting('label'),
    ];
    $elements['number_of_characters'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Number of characters'),
      '#description' => $this->t('Counting will include the comma and space between tags.'),
      '#default_value' => $this->getSetting('number_of_characters'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Label: @label', ['@label' => $this->getSetting('label')]);
    $summary[] = $this->t('Number of characters: @number_of_characters', ['@number_of_characters' => $this->getSetting('number_of_characters')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $list = [];
    $list_hidden = [];
    if ($items->isEmpty()) {
      return [];
    }
    $referenced_entites = $items->referencedEntities();
    $chars_count = 0;
    foreach ($referenced_entites as $entity) {
      // Add strlen and comma and space.
      $chars_count += strlen($entity->label()) + 2;
      $route_name = "entity.{$entity->getEntityTypeId()}.canonical";
      $route_params = [
        "{$entity->getEntityTypeId()}" => $entity->id(),
      ];
      $link = [
        '#title' => $entity->label(),
        '#type' => 'link',
        '#url' => Url::fromRoute($route_name, $route_params),
      ];
      if ($chars_count <= $this->getSetting('number_of_characters')) {
        $list[] = $link;
        continue;
      }
      $list_hidden[] = $link;
    }

    return [
      '#theme' => 'cp_taxonomy_comma_separator',
      '#title' => $this->getSetting('label') . ':',
      '#items' => $list,
      '#items_hidden' => $list_hidden,
      '#show_more_less' => !empty($list_hidden),
      '#attached' => [
        'library' => [
          'cp_taxonomy/cp_taxonomy.toggle_terms',
        ],
      ],
    ];
  }

}
