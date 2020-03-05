<?php

namespace Drupal\os_pages\Form;

use Drupal\book\BookManager;
use Drupal\book\BookManagerInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\os_pages\BooksHelper;
use Drupal\Core\Render\RendererInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\core\Url;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Provides a form for section Outline form.
 */
class SectionOutlineForm extends FormBase {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Vsite Manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteManager;

  /**
   * The books Helper.
   *
   * @var \Drupal\os_pages\BooksHelper
   */
  protected $booksHelper;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructor for SectionOutlineForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The custom block storage.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager
   *   Vsite context manager.
   * @param \Drupal\os_pages\BooksHelper $books_helper
   *   Books helper service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder.
   */
  public function __construct(EntityStorageInterface $node_storage, BookManagerInterface $book_manager, VsiteContextManagerInterface $vsiteContextManager, BooksHelper $books_helper, RendererInterface $renderer, FormBuilderInterface $form_builder) {
    $this->nodeStorage = $node_storage;
    $this->bookManager = $book_manager;
    $this->booksHelper = $books_helper;
    $this->vsiteManager = $vsiteContextManager;
    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('node'),
      $container->get('book.manager'),
      $container->get('vsite.context_manager'),
      $container->get('os_pages.books_helper'),
      $container->get('renderer'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'section_outline';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['#title'] = $node->label();
    $form['#node'] = $node;
    $this->bookAdminTable($node, $form);
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save book pages'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('tree_hash') != $form_state->getValue('tree_current_hash')) {
      $form_state->setErrorByName('', $this->t('This book has been modified by another user, the changes could not be saved.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save elements in the same order as defined in post rather than the form.
    // This ensures parent is updated before its children, preventing orphans.
    $user_input = $form_state->getValues();
    if (isset($user_input['table'])) {
      $order = array_flip(array_keys($user_input['table']));
      $form['table'] = array_merge($order, $form['table']);

      foreach (Element::children($form['table']) as $key) {
        if ($form['table'][$key]['#item']) {
          $row = $form['table'][$key];
          $values = $form_state->getValue(['table', $key]);
          // Update menu item if moved.
          if ($row['parent']['pid']['#default_value'] != $values['pid'] || $row['weight']['#default_value'] != $values['weight']) {
            $link = $this->bookManager->loadBookLink($values['nid'], FALSE);
            $link['weight'] = $values['weight'];
            $link['pid'] = $values['pid'];
            $this->bookManager->saveBookLink($link, FALSE);
          }

          // Update the title if changed.
          if ($row['title']['#default_value'] != $values['title']) {
            $node = $this->nodeStorage->load($values['nid']);
            $node->revision_log = $this->t('Title changed from %original to %current.', ['%original' => $node->label(), '%current' => $values['title']]);
            $node->title = $values['title'];
            $node->book['link_title'] = $values['title'];
            $node->setNewRevision();
            $node->save();
            $this->logger('content')->notice('book: updated %title.', ['%title' => $node->label(), 'link' => $node->toLink($this->t('View'))->toString()]);
          }
          // Update the status of node's section nav block if changed.
          if ($row['hide']['#default_value'] != $values['hide']) {
            $this->updateHiddenSectionChildPages($values['nid'], $values['hide']);
          }
        }
      }
    }

    $this->messenger()->addStatus($this->t('Updated book %title.', ['%title' => $form['#node']->label()]));
  }

  /**
   * Method to update parent and child nodes upon checkbox selection.
   *
   * @param int $parent_nid
   *   Parent nid.
   * @param bool $hidden
   *   Boolean status.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateHiddenSectionChildPages($parent_nid, $hidden) {
    $node = $this->nodeStorage->load($parent_nid);
    $tree = $this->bookManager->bookSubtreeData($node->book);
    $node->set('field_is_hidden_section_nav', $hidden);
    $node->save();
    $tree = array_shift($tree);
    if ($tree['below']) {
      foreach ($tree['below'] as $data) {
        $this->updateHiddenSectionChildPages($data['link']['nid'], $hidden);
      }
    }
  }

  /**
   * Builds the table portion of the form for the section outline page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node of the top-level page in the book.
   * @param array $form
   *   The form that is being modified, passed by reference.
   *
   * @see self::buildForm()
   */
  protected function bookAdminTable(NodeInterface $node, array &$form) {
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Hide from section navigation'),
        $this->t('Move to section navigation'),
        $this->t('Weight'),
        $this->t('Parent'),
      ],
      '#empty' => $this->t('No book content available.'),
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'book-pid',
          'subgroup' => 'book-pid',
          'source' => 'book-nid',
          'hidden' => TRUE,
          'limit' => BookManager::BOOK_MAX_DEPTH - 2,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'book-weight',
        ],
      ],
      '#prefix' => "<div id='section-outline-wrapper'>",
      '#suffix' => '</div>',
    ];

    $tree = $this->bookManager->bookSubtreeData($node->book);
    // Do not include the book item itself.
    $tree = array_shift($tree);
    if ($tree['below']) {
      $hash = Crypt::hashBase64(serialize($tree['below']));
      // Store the hash value as a hidden form element so that we can detect
      // if another user changed the book hierarchy.
      $form['tree_hash'] = [
        '#type' => 'hidden',
        '#default_value' => $hash,
      ];
      $form['tree_current_hash'] = [
        '#type' => 'value',
        '#value' => $hash,
      ];
      $this->bookAdminTableTree($tree['below'], $form['table'], $node);
    }
  }

  /**
   * Helps build the main table in the section outline page form.
   *
   * @param array $tree
   *   A subtree of the book menu hierarchy.
   * @param array $form
   *   The form that is being modified, passed by reference.
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   *
   * @see self::buildForm()
   */
  protected function bookAdminTableTree(array $tree, array &$form, NodeInterface $node) {
    $options = $this->booksHelper->getVsiteBooks($this->vsiteManager->getActiveVsite(), $node);
    // The delta must be big enough to give each node a distinct value.
    $count = count($tree);
    $delta = ($count < 30) ? 15 : (int) ($count / 2) + 1;

    foreach ($tree as $data) {
      $nid = $data['link']['nid'];
      $id = 'book-admin-' . $nid;

      $form[$id]['#item'] = $data['link'];
      $form[$id]['#nid'] = $nid;
      $form[$id]['#attributes']['class'][] = 'draggable';
      $form[$id]['#weight'] = $data['link']['weight'];

      if (isset($data['link']['depth']) && $data['link']['depth'] > 2) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $data['link']['depth'] - 2,
        ];
      }

      $form[$id]['title'] = [
        '#prefix' => !empty($indentation) ? $this->renderer->render($indentation) : '',
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $data['link']['title'],
        '#maxlength' => 255,
        '#size' => 40,
        '#title_display' => 'invisible',
      ];

      $hidden_section_nav = $this->nodeStorage->load($nid);
      $form[$id]['hide'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide from navigation'),
        '#default_value' => $hidden_section_nav->field_is_hidden_section_nav->value ?? 0,
        '#return_value' => 1,
        '#title_display' => 'invisible',
      ];

      $form[$id]['move_navigation']['books_list'] = [
        '#type' => 'select',
        '#title' => $this->t('Select from books list'),
        '#options' => $options,
        '#name' => "books-list-{$id}",
        '#title_display' => 'invisible',
      ];

      $form[$id]['move_navigation']['move'] = [
        '#type' => 'button',
        '#name' => "move-btn-{$id}",
        '#value' => $this->t('Move'),
        '#ajax' => [
          'callback' => '::moveSectionCallback',
          'event' => 'click',
          'wrapper' => 'section-outline-wrapper',
        ],
      ];

      $form[$id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $data['link']['weight'],
        '#delta' => max($delta, abs($data['link']['weight'])),
        '#title' => $this->t('Weight for @title', ['@title' => $data['link']['title']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['book-weight'],
        ],
      ];

      $form[$id]['parent']['nid'] = [
        '#parents' => ['table', $id, 'nid'],
        '#type' => 'hidden',
        '#value' => $nid,
        '#attributes' => [
          'class' => ['book-nid'],
        ],
      ];

      $form[$id]['parent']['pid'] = [
        '#parents' => ['table', $id, 'pid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['pid'],
        '#attributes' => [
          'class' => ['book-pid'],
        ],
      ];

      $form[$id]['parent']['bid'] = [
        '#parents' => ['table', $id, 'bid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['bid'],
        '#attributes' => [
          'class' => ['book-bid'],
        ],
      ];

      if ($data['below']) {
        $this->bookAdminTableTree($data['below'], $form, $node);
      }
    }
  }

  /**
   * Method to move book pages to selected book.
   */
  public function moveSectionCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $values = $form_state->getUserInput();
    $key = 'books-list-' . $button['#array_parents'][1];
    $selected_book_entity = $values[$key];
    if (!empty($selected_book_entity) && !empty($element)) {
      $current_page = $this->nodeStorage->load($element['#nid']);
      $book_entity = $this->nodeStorage->load($selected_book_entity);
      $this->booksHelper->saveOtherBookPages($current_page, $book_entity);
      $response = new AjaxResponse();
      $currentURL = Url::fromRoute('<current>');
      $response->addCommand(new RedirectCommand($currentURL->toString()));
      return $response;
    }
    $form_state->setRebuild();
  }

}
