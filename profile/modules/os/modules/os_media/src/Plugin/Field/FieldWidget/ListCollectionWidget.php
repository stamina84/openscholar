<?php

namespace Drupal\os_media\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * Class ListCollectionWidget.
 *
 *   A field widget which has a select option with an Add more button where
 *   selected options can be re-ordered/removed using tabledrag and with a
 *   remove button.
 *
 * @FieldWidget(
 *   id = "list_collection_widget",
 *   label = @Translation("List Collection"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ListCollectionWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Utility\Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Drupal\vsite\Plugin\VsiteContextManagerInterface service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountProxy $current_user, Token $token, VsiteContextManagerInterface $vsiteContextManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
    $this->token = $token;
    $this->vsiteContextManager = $vsiteContextManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
     $plugin_id,
     $plugin_definition,
     $configuration['field_definition'],
     $configuration['settings'],
     $configuration['third_party_settings'],
     $container->get('current_user'),
     $container->get('token'),
     $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this
      ->getFieldSettings();
    $referenced_entities = $items->referencedEntities();
    $widget_title = '';
    $entity = NULL;

    if (isset($referenced_entities[$delta])) {
      $entity = $referenced_entities[$delta];
      $widget_title = $entity->field_widget_title->getString();
    }

    if ($entity) {
      $element += [
        'label' => $entity->label(),
        'target_id' => [
          '#type' => 'value',
          '#value' => $entity->id(),
        ],
        'widget_title' => $field_settings['widget_title'] ?? $widget_title,
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['target_id'];
  }

  /**
   * Overriding method massageFormValues.
   *
   * Filtering values with target_id as it has selected block_id and this will
   * be saved as field_widget_collection data.
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (isset($value['target_id'])) {
        if (is_array($value['target_id'])) {
          unset($values[$key]['target_id']);
          if (isset($value['target_id']['target_id'])) {
            $values[$key]['target_id'] = $value['target_id']['target_id'];
          }
          else {
            $values[$key] += $value['target_id'];
          }
        }
      }
      else {
        unset($values[$key]);
      }

    }
    return $values;
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The selected items or list.
   * @param array $form
   *   Block form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state to retreive form values.
   *
   * @return array
   *   Returns field element in an array.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();
    $parents = $form['#parents'];

    $values = $form_state->getValue($field_name);
    if (isset($values)) {
      $this->extractFormValues($items, $form, $form_state);
    }
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        break;

      default:
        $max = $cardinality - 1;
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create($this->token
      ->replace($this->fieldDefinition->getDescription()));
    $elements = [
      'add_new_element' => [
        '#type' => 'container',
        'select_input' => [
          '#title' => $title,
          '#description' => $description,
          '#type' => 'select',
          '#options' => $this->getOptions($items->getEntity()),
          '#default_value' => NULL,
        ],
        'add' => [
          '#type' => 'submit',
          '#value' => $this->t('Add this'),
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
          '#attributes' => [
            'class' => [
              'add-button',
            ],
          ],
        ],
      ],

    ];

    $widget_table = [
      '#type' => 'table',
      '#header' => [],
      '#empty' => $this->t('There are no items yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'widget-table-order-weight',
        ],
      ],
    ];

    for ($delta = 0; $delta < $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      $element = [];
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if (!empty($element['target_id'])) {
        // TableDrag: Mark the table row as draggable.
        $widget_table[$delta]['#attributes']['class'][] = 'draggable';

        $widget_table[$delta]['target_id'] = $element['target_id'];
        // TableDrag: Sort table row according to its configured weight.
        $widget_table[$delta]['#weight'] = $element['#weight'];

        // Some table columns containing raw markup.
        $widget_table[$delta]['label'] = [
          '#plain_text' => $element['label'],
        ];

        $widget_table[$delta]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $element['label']]),
          '#title_display' => 'invisible',
          '#default_value' => $element['#weight'],
          // Classify the weight element for #tabledrag.
          '#attributes' => ['class' => ['widget-table-order-weight']],
        ];

        $widget_table[$delta]['widget_title_input'] = [
          '#type' => 'textfield',
          '#default_value' => $element['widget_title'],
        ];

        $widget_table[$delta]['remove'] = [
          '#type' => 'submit',
          '#name' => 'remove_' . $delta,
          '#value' => $this->t('Remove'),
          '#submit' => [[get_class($this), 'removeSubmit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#ajax' => [
            'callback' => [get_class($this), 'removeAjax'],
            'effect' => 'fade',
            'wrapper' => $wrapper_id,
          ],
        ];
      }
    }

    $elements['add_new_element']['widget_table'] = $widget_table;

    if ($elements) {
      $elements += [
        '#field_name' => $field_name,
        '#entity_type' => $this->getFieldSetting('target_type'),
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()
          ->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
      $elements['#suffix'] = '</div>';
    }

    return $elements;
  }

  /**
   * Submission handler for the "Add another item" button.
   *
   * @param array $form
   *   The block_content form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The block_content form_state values.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $parents);

    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    // Add new values to form_state.
    $delta = $element['#max_delta'];
    $values = $form_state->getValue($element['#field_name']);
    $id = $values['add_new_element']['select_input'];
    $values = ListCollectionWidget::alignWidgetTableValues($values);
    $values = array_merge($values, [$delta => ['target_id' => ['target_id' => $id]]]);
    $form_state->setValueForElement($element, $values);
    $user_input = $form_state->getUserInput();
    unset($user_input[$element['#field_name']]['add_new_element']);
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    $values = ListCollectionWidget::alignWidgetTableValues($values);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);
        unset($values['add_new_element']);
        unset($values['widget_table']);
        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);
      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);

    if (!isset($button['#ajax']['callback'])) {
      if ($violations->count()) {
        // Locate the correct element in the form.
        $element = NestedArray::getValue($form_state->getCompleteForm(), $field_state['array_parents']);
        $field_values = $form_state->getValue($field_name);
        if (empty($field_values['add_new_element']['widget_table'])) {
          $form_state->setError($element, $this->t('This field cannot be null.'));
        }
      }
    }
  }

  /**
   * Ajax callback for the "Add this" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   *
   * @param array $form
   *   The block_content form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The block_content form_state values.
   *
   * @return mixed
   *   Returns the field element.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $form_state->setRebuild();
    return $element;
  }

  /**
   * Submit callback for Remove button.
   *
   * @param array $form
   *   The block_content form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The block_content form_state values.
   */
  public static function removeSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#array_parents'], 0, -4);
    $element = NestedArray::getValue($form, $parents);
    // Add new values to form_state.
    $delta = array_slice($button['#parents'], -2, 1);
    $delta = reset($delta);
    $values = $form_state->getValue($element['#field_name']);
    $values = ListCollectionWidget::alignWidgetTableValues($values);
    unset($values[$delta]);
    $form_state->setValue($element['#field_name'], $values);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Remove" button.
   *
   * @param array $form
   *   The block_content form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The block_content form_state values.
   *
   * @return mixed
   *   Returns the field element.
   */
  public static function removeAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -5));
    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = [];
    if ($vsite = $this->vsiteContextManager->getActiveVsite()) {
      $vSiteBlocks = $vsite->getContent('group_entity:block_content');
      foreach ($vSiteBlocks as $vSiteBlock) {
        /** @var \Drupal\block_content\BlockContentInterface $block_content */
        $block_content = $vSiteBlock->getEntity();
        $options[$block_content->id()] = $block_content->label();
      }
    }

    return $options;
  }

  /**
   * Returns aligned form_state field values.
   *
   * @param mixed $values
   *   Form state values for the field.
   *
   * @return mixed
   *   Returns the aligned values.
   */
  protected static function alignWidgetTableValues($values) {
    $table_values = [];
    if (!empty($values['add_new_element']['widget_table'])) {
      $table_values = $values['add_new_element']['widget_table'];
      unset($values['add_new_element']['widget_table']);
    }
    $values = array_merge($values, $table_values);
    return $values;
  }

}
