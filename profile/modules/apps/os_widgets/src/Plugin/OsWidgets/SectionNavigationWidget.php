<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SectionNavigationWidget.
 *
 * @OsWidget(
 *   id = "section_navigation_widget",
 *   title = @Translation("Section navigation")
 * )
 */
class SectionNavigationWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * The Book manager.
   *
   * @var Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The node storage.
   *
   * @var Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Request stack to fetch current node.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, BookManagerInterface $book_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->bookManager = $book_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('book.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $tree = [];
    $node = $this->requestStack->getCurrentRequest()->attributes->get('node');
    if (!empty($node->book) && isset($node->book['bid'])) {
      if ($node->field_is_hidden_section_nav->value == 0) {
        $book_node = $this->nodeStorage->load($node->book['bid']);
        $tree = $this->bookTreeOutput($book_node->book, $node->book);
        $build['section_navigation'] = $tree;
      }
    }
  }

  /**
   * Get book themed output.
   *
   * @param array $book_link
   *   The node book array.
   * @param array $active_book_link
   *   The current node book array.
   */
  protected function bookTreeOutput(array $book_link, array $active_book_link) {
    $build = [];
    $children = [];
    $tree = $this->bookManager->bookSubtreeData($book_link);

    if ($book_link['has_children']) {
      $link = array_shift($tree);
      $data['link'] = $link['link'];
      $data['below'] = $link['below'] ?? '';
      $children[] = $data;
      // Fetching elements.
      $items = $this->buildItems($children, $active_book_link);
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the book id from the last link.
      $item = end($items);
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'book_tree__book_toc_' . $item['original_link']['bid'];
      $build['#items'] = $items;
      // Set cache tag.
      $build['#cache']['tags'][] = 'config:system.book.' . $item['original_link']['bid'];
    }

    return $build;
  }

  /**
   * Builds the #items property for a book tree's renderable array.
   *
   * Helper function for ::bookTreeOutput().
   *
   * @param array $tree
   *   A data structure representing the tree.
   * @param array $active_book_link
   *   The current node book array.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   */
  protected function buildItems(array $tree, array $active_book_link) {
    $items = [];

    foreach ($tree as $data) {
      $element = [];

      // Generally we only deal with visible links, but just in case.
      if (!$data['link']['access']) {
        continue;
      }
      // Set a class for the <li> tag. Since $data['below'] may contain local
      // tasks, only set 'expanded' to true if the link also has children within
      // the current book.
      $element['is_expanded'] = FALSE;
      $element['is_collapsed'] = FALSE;
      if ($data['link']['has_children'] && $data['below']) {
        $element['is_expanded'] = TRUE;
      }
      elseif ($data['link']['has_children']) {
        $element['is_collapsed'] = TRUE;
      }

      // Set a helper variable to indicate whether the link is in the active
      // trail.
      $element['in_active_trail'] = FALSE;
      if ($data['link']['in_active_trail']) {
        $element['in_active_trail'] = TRUE;
      }

      // Allow book-specific theme overrides.
      $node = $this->nodeStorage->load($data['link']['nid']);
      if ($node->field_is_hidden_section_nav->value == 0) {
        $element['attributes'] = new Attribute();
        if ($data['link']['nid'] == $active_book_link['nid']) {
          $element['attributes']->addClass('active-nav-link');
        }
        $element['title'] = $data['link']['title'];
        $element['url'] = $node->toUrl();
        $element['localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : [];
        $element['localized_options']['set_active_class'] = TRUE;
        $element['below'] = $data['below'] ? $this->buildItems($data['below'], $active_book_link) : [];
        $element['original_link'] = $data['link'];
        // Index using the link's unique nid.
        $items[$data['link']['nid']] = $element;
      }
    }

    return $items;
  }

}
