<?php

namespace Drupal\os_pages\Form;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
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
   * Constructs a BookOutlineForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The BookManager service.
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsite_manager
   *   The vsite Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BookManagerInterface $book_manager, VsiteContextManager $vsite_manager) {
    $this->bookManager = $book_manager;
    $this->vsiteManager = $vsite_manager;
    $this->vsite = $this->vsiteManager->getActiveVsite();
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('book.manager'),
      $container->get('vsite.context_manager')
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
        $link = $this->bookManager->loadBookLink($book_entity_id, FALSE);
        $link['bid'] = $this->entity->book['bid'];
        $link['pid'] = $this->entity->book['bid'];
        $link['weight'] = $last_child_weight + 1;
        $link['has_children'] = $selected_book->book['has_children'];
        $this->bookManager->saveBookLink($link, FALSE);
        if ($selected_book->book['has_children'] > 0) {
          $this->setBidForChildren($selected_book->book, $this->entity->book['bid']);
        }
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

  /**
   * Looping through children elements to save all of them.
   *
   * @param array $book_link
   *   Parent node entity's book array.
   * @param int $bid
   *   Book Id pages should be added to.
   */
  public function setBidForChildren(array $book_link, $bid) {
    $flat = $this->bookManager->bookTreeGetFlat($book_link);

    if ($book_link['has_children']) {
      // Walk through the array until we find the current page.
      do {
        $link = array_shift($flat);
      } while ($link && ($link['nid'] != $book_link['nid']));
      // Continue though the array and collect links whose parent is this page.
      while (($link = array_shift($flat)) && $link['pid'] == $book_link['nid']) {
        $child = $this->nodeStorage->load($link['nid']);
        if ($child->book['has_children'] > 0) {
          $this->setBidForChildren($child->book, $bid);
        }
        $link = $this->bookManager->loadBookLink($link['nid'], FALSE);
        $link['bid'] = $bid;
        $link['pid'] = $book_link['nid'];
        $this->bookManager->saveBookLink($link, FALSE);
      }
    }

  }

}
