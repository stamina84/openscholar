<?php

namespace Drupal\cp_taxonomy\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override original functionality to handle taxonomy term reference.
 *
 * @FieldWidget(
 *   id = "cp_entity_reference_autocomplete_tags",
 *   label = @Translation("CP Autocomplete tags"),
 *   description = @Translation("Manage to handle autocomplete with autocreate."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class CpEntityReferenceAutocompleteTagsWidget extends CpEntityReferenceAutocompleteWidget implements ContainerFactoryPluginInterface {

  /**
   * Id of the vocabulary bundle.
   *
   * @var string
   */
  protected $autoCreateBundle;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $pluginEntityReferenceSelection;

  /**
   * Vsite Context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Constructs a CpEntityReferenceAutocompleteTagsWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $pluginEntityReferenceSelection
   *   Selection Plugin Manager.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite Context Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, SelectionPluginManagerInterface $pluginEntityReferenceSelection, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
    $this->pluginEntityReferenceSelection = $pluginEntityReferenceSelection;
    $this->vsiteContextManager = $vsite_context_manager;
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
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();

    $element['target_id']['#tags'] = TRUE;
    $element['target_id']['#default_value'] = $items->referencedEntities();

    $element['target_id']['#autocreate'] = [
      'bundle' => $this->autoCreateBundle,
      'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : $this->currentUser->id(),
    ];

    $element['target_id']['#element_validate'] = [
      [$this, 'elementAutoCreate'],
    ];
    TaxonomyTermsWidget::addVocabularyToSelectionSettingsArguments($element, $this->autoCreateBundle);

    return $element['target_id'];
  }

  /**
   * Prepare the values with new elements.
   *
   * @param array $element
   *   Form reference element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure in array.
   */
  public function elementAutoCreate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value'])) {
      $options = $element['#selection_settings'] + [
        'target_type' => $element['#target_type'],
        'handler' => 'default',
      ];
      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = $this->pluginEntityReferenceSelection->getInstance($options);
      $autocreate = TRUE;

      // GET forms might pass the validated data around on the next request, in
      // which case it will already be in the expected format.
      if (is_array($element['#value'])) {
        $value = $element['#value'];
      }
      else {
        $input_values = $element['#tags'] ? Tags::explode($element['#value']) : [$element['#value']];

        foreach ($input_values as $input) {
          $match = EntityAutocomplete::extractEntityIdFromAutocompleteInput($input);
          if ($match === NULL) {
            // Try to get a match from the input string when the user didn't use
            // the autocomplete but filled in a value manually.
            $entities_by_bundle = $handler->getReferenceableEntities($input, '=', 6);
            $entities = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
              return $flattened + $bundle_entities;
            }, []);
            if (count($entities) == 1) {
              $match = key($entities);
            }
          }

          if ($match !== NULL) {
            $value[] = [
              'target_id' => $match,
            ];
          }
          elseif ($autocreate) {
            // Create the term and save on submit.
            $term = $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid']);
            $term->save();
            if ($group = $this->vsiteContextManager->getActiveVsite()) {
              $group->addContent($term, 'group_entity:' . $term->getEntityTypeId());
            }
            $value[] = [
              'target_id' => $term->id(),
            ];
          }
        }
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * Allow to set vocabulary id.
   *
   * @param string $bundle
   *   Bundle of vocabulary.
   */
  public function setAutocreateBundle(string $bundle) {
    $this->autoCreateBundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $value) {
      if (empty($value)) {
        continue;
      }
      $new_values[] = $value['target_id'];
    }

    return $new_values;
  }

}
