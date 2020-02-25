<?php

namespace Drupal\os_pages\Form;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\os_pages\BooksHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Displays the book outline form.
 */
class AddOtherBooksForm extends FormBase {

  /**
   * The book being displayed.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Vsite Manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteManager;
  /**
   * Current Vsite.
   *
   * @var \Drupal\group\Entity\GroupInterface|null
   */
  protected $vsite;
  /**
   * BookManager service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The books Helper.
   *
   * @var \Drupal\os_pages\BooksHelper
   */
  protected $booksHelper;

  /**
   * Constructs a BookOutlineForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The BookManager service.
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsite_manager
   *   The vsite Manager service.
   * @param \Drupal\os_pages\BooksHelperInterface $books_helper
   *   Books helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BookManagerInterface $book_manager, VsiteContextManager $vsite_manager, BooksHelperInterface $books_helper) {
    $this->bookManager = $book_manager;
    $this->vsiteManager = $vsite_manager;
    $this->vsite = $this->vsiteManager->getActiveVsite();
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->booksHelper = $books_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('book.manager'),
      $container->get('vsite.context_manager'),
      $container->get('os_pages.books_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_other_books_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->entity = $node;
    $form['add_other_books'] = [
      '#title' => $this->t('Pages'),
      '#type' => 'textfield',
      '#description' => $this->t('Books along with children will be added to this book.'),
      '#autocomplete_route_name' => 'os_pages.autocomplete.books',
      '#autocomplete_route_parameters' => ['group' => $this->vsite->id(), 'node' => $node->id()],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $book_entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('add_other_books'));

    if (!empty($book_entity_id)) {
      $selected_book = $this->nodeStorage->load($book_entity_id);
      $book_data = $this->bookManager->bookTreeGetFlat($this->entity->book);
      $last_child_weight = (int) end($book_data)['weight'];
      // Saving selected node to book.
      if (!empty($selected_book->book['bid'])) {
        $this->booksHelper->saveOtherBookPages($selected_book, $this->entity);
      }
      else {
        $selected_book->book = [
          'nid' => $book_entity_id,
          'bid' => $this->entity->book['bid'],
          'pid' => $this->entity->book['bid'],
          'weight' => $last_child_weight + 1,
          'has_children' => 0,
        ];
        $this->bookManager->saveBookLink($selected_book->book, TRUE);
      }
      $this->messenger()->addMessage('Page added to the book.');
    }

    $form_state->setRedirect(
      'os_pages.book_outline',
      ['node' => $this->entity->id()]
    );

  }

}
