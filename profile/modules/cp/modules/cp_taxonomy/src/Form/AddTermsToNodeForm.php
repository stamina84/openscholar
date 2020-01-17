<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Add terms to node entities form.
 */
class AddTermsToNodeForm extends FormBase {

  /**
   * The array of entities to delete.
   *
   * @var array
   */
  protected $entityInfo = [];

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type identifier.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Cp taxonomy helper.
   *
   * @var \Drupal\cp_taxonomy\CpTaxonomyHelper
   */
  protected $taxonomyHelper;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\cp_taxonomy\CpTaxonomyHelper $taxonomy_helper
   *   Taxonomy Helper.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Taxonomy Helper.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager, AccountInterface $current_user, CpTaxonomyHelper $taxonomy_helper, Renderer $renderer) {
    $this->tempStore = $temp_store_factory->get('cp_taxonomy_add_terms_node');
    $this->entityTypeManager = $manager;
    $this->currentUser = $current_user;
    $this->taxonomyHelper = $taxonomy_helper;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('cp.taxonomy.helper'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_terms_to_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $form['#title'] = $this->t('Apply Terms to Content');
    $this->entityTypeId = $entity_type_id;
    $this->entityInfo = $this->tempStore->get($this->currentUser->id());
    if (empty($this->entityInfo)) {
      return new RedirectResponse(Url::fromRoute('cp.content.collection')->toString());
    }
    $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $vocabs = $vocabulary_storage->loadMultiple();

    $options = [];
    $options_terms = [];
    foreach ($vocabs as $vocab) {
      $options[$vocab->id()] = $vocab->label();
      $terms = $term_storage->loadTree($vocab->id());
      foreach ($terms as $term) {
        // We have to collect all terms to prevent error from allowed values.
        $options_terms[$term->tid] = $term->name;
      }
    }
    $form['vocabulary'] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'callback' => '::getTermsAjaxCallback',
        'event' => 'change',
        'wrapper' => 'edit-terms',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Getting terms...'),
        ],
      ],
    ];

    $form['terms'] = [
      '#type' => 'select',
      '#options' => $options_terms,
      '#multiple' => 1,
      '#chosen' => 1,
      '#title' => $this->t('Terms'),
      '#prefix' => '<div id="edit-terms">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="vocabulary"]' => ['value' => ''],
        ],
      ],
    ];

    $form['entities'] = [
      '#title' => $this->t('The selected terms above will be applied to the following content:'),
      '#theme' => 'item_list',
      '#items' => $this->entityInfo,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#type'] == 'submit' && !empty($this->entityInfo)) {
      $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
      $selected_vocabulary = $form_state->getValue('vocabulary');
      $terms_to_apply = $form_state->getValue('terms');
      $terms = $term_storage->loadMultiple($terms_to_apply);
      $term_names = [];
      foreach ($terms as $term) {
        $term_names[] = $term->label();
      }
      $allowed_types = $vocabulary_storage->load($selected_vocabulary)->get('allowed_vocabulary_reference_types');
      $entities = $this->taxonomyHelper->explodeEntityBundles($allowed_types);
      if (empty($entities[$this->entityTypeId])) {
        $this->messenger()->addStatus($this->t('Selected vocabulary is not handle %entity_type_id entity type.', ['%entity_type_id' => $this->entityTypeId]));
      }
      $handled_bundles = $entities[$this->entityTypeId];

      $entities = $storage->loadMultiple(array_keys($this->entityInfo));
      $skipped_titles = [];
      $applied_titles = [];
      foreach ($entities as $entity) {
        $bundle = $entity->bundle();
        if (!in_array($bundle, $handled_bundles)) {
          $skipped_titles[] = $entity->label();
          continue;
        }
        /** @var \Drupal\Core\Field\FieldItemList $current_terms */
        $current_terms = $entity->get('field_taxonomy_terms');
        $attached_terms = [];
        foreach ($current_terms->getValue() as $value) {
          $attached_terms[] = $value['target_id'];
        }
        foreach ($terms as $term) {
          // Prevent append if exists.
          if (in_array($term->id(), $attached_terms)) {
            continue;
          }
          $current_terms->appendItem($term);
        }
        $entity->set('field_taxonomy_terms', $current_terms->getValue());
        $entity->save();
        $applied_titles[] = $entity->label();
      }

      $params = [
        '%terms' => implode(", ", $term_names),
      ];
      // Notify the user on the skipped nodes (nodes whose bundle is not
      // associated with the selected vocabulary).
      if (!empty($skipped_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_apply), 'Taxonomy term %terms could not be applied on the content:', '@count taxonomy terms %terms could not be applied on the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $skipped_titles,
          ],
        ];
        $this->messenger()->addWarning($this->renderer->renderPlain($message));
      }

      // Notify the user on the applied nodes.
      if (!empty($applied_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_apply), 'Taxonomy term %terms was applied on the content:', '@count taxonomy terms %terms were applied on the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $applied_titles,
          ],
        ];

        $this->messenger()->addStatus($this->renderer->renderPlain($message));
      }

      $this->tempStore->delete($this->currentUser->id());
    }

    $form_state->setRedirect('cp.content.collection');
  }

  /**
   * Ajax callback for handling vocabulary depends terms selection.
   */
  public function getTermsAjaxCallback(array &$form, FormStateInterface $form_state) {
    if ($selected_vocabulary = $form_state->getValue('vocabulary')) {
      $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $vocab = $vocabulary_storage->load($selected_vocabulary);
      // Get terms from selected vocabulary.
      $terms = $term_storage->loadTree($vocab->id());
      $options = [];
      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }
      $form['terms']['#options'] = $options;
      // Return the prepared element.
      return $form['terms'];
    }
    return [];
  }

}
