<?php

namespace Drupal\os_pages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os_pages\BooksHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Ajax helper form for section outline form.
 */
class AjaxSectionOutlineForm extends FormBase {


  /**
   * The node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * An array which has books list except current book which node belongs to.
   *
   * @var array
   */
  protected $options;

  /**
   * Constructor for SectionOutlineForm.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The custom block storage.
   * @param \Drupal\os_pages\BooksHelper $books_helper
   *   Books helper service.
   * @param array $options
   *   The vsite books as select options.
   */
  public function __construct(NodeInterface $node, BooksHelper $books_helper, array $options = []) {
    $this->node = $node;
    $this->options = $options;
    $this->booksHelper = $books_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os_pages.books_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "ajax_section_outline_{$this->node->id()}_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['move_navigation']['books_list'] = [
      '#type' => 'select',
      '#options' => $this->options,
    ];

    $form['move_navigation']['move'] = [
      '#type' => 'button',
      '#value' => $this->t('Move'),
      '#ajax' => [
        'callback' => '::moveSectionCallback',
        'event' => 'click',
        'wrapper' => 'section-outline-wrapper',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Method to move book pages to selected book.
   */
  public function moveSectionCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $current_page = $this->node;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $value = $form_state->getValue('books_list');
    if (isset($value) && !empty($element)) {
      $book_entity = $this->nodeStorage->load($value);
      $this->booksHelper->saveOtherBookPages($current_page, $book_entity);
    }
    $form_state->setRebuild();
  }

}
