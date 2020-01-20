<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage terms node entities base form.
 */
abstract class ManageTermsNodeFormBase extends FormBase {

  /**
   * The array of entities to delete.
   *
   * @var array
   */
  protected $entityInfo = [];

  /**
   * The tempstore factory object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

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
    $this->tempStoreFactory = $temp_store_factory;
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
