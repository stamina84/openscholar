<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

use Behat\Mink\Exception\ExpectationException;
use Drupal\block\Entity\Block;
use Drupal\os_widgets\Entity\LayoutContext;

/**
 * Tests book pages.
 *
 * @group openscholar
 * @group functional-javascript
 * @group pages
 */
class PagesTest extends TestBase {

  /**
   * Tests visibility of book outline.
   */
  public function testBookVisibility() {
    /** @var \Drupal\book\BookManagerInterface $book_manager */
    $book_manager = $this->container->get('book.manager');
    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');
    /** @var \Drupal\Core\Config\ImmutableConfig $theme_config */
    $theme_config = \Drupal::config('system.theme');

    /** @var \Drupal\node\NodeInterface $book1 */
    $book1 = $this->createBookPage([
      'title' => 'Harry Potter and the Philosophers Stone',
    ]);
    $book_manager->updateOutline($book1);

    /** @var \Drupal\node\NodeInterface $page1 */
    $page1 = $this->createBookPage([
      'title' => 'The Boy Who Lived',
    ], $book1->id());
    $book_manager->updateOutline($page1);

    /** @var \Drupal\node\NodeInterface $book2 */
    $book2 = $this->createBookPage([
      'title' => 'Harry Potter and the Deathly Hallows',
    ]);
    $book_manager->updateOutline($book2);

    /** @var \Drupal\node\NodeInterface $event */
    $event = $this->createNode([
      'type' => 'events',
    ]);

    $section_block = Block::create([
      'id' => "booknavigation_{$book1->id()}",
      'theme' => $theme_config->get('default'),
      'region' => 'sidebar_second',
      'plugin' => 'book_navigation',
      'settings' => [
        'id' => 'book_navigation',
        'label' => 'Books',
        'provider' => 'book',
        'label_display' => 'visible',
        'block_mode' => 'all pages',
      ],
    ]);
    $section_block->save();
    $layout = LayoutContext::load('os_pages_section_' . $book1->id());
    $blocks = $layout->getBlockPlacements();
    $blocks[$section_block->id()] = [
      'id' => $section_block->id(),
      'region' => 'sidebar_second',
      'weight' => 0,
    ];
    $layout->setBlockPlacements($blocks);
    $layout->save();

    $page_block = Block::create([
      'id' => "entityviewcontent_{$page1->id()}",
      'theme' => $theme_config->get('default'),
      'region' => 'sidebar_second',
      'plugin' => 'entity_view:node',
      'settings' => [
        'id' => 'entity_view:node',
        'label' => $page1->label(),
        'provider' => 'ctools',
        'label_display' => '0',
        'view_mode' => 'block',
        'context_mapping' => [
          'entity' => '@node.node_route_context:node',
        ],
      ],
      'visibility' => [
        'condition_group' => [
          'id' => 'condition_group',
          'negate' => FALSE,
          'block_visibility_group' => "os_pages_page_{$page1->id()}",
          'context_mapping' => [],
        ],
      ],
    ]);
    $page_block->save();

    $web_assert = $this->assertSession();

    try {
      $this->visit($path_alias_manager->getAliasByPath("/node/{$book1->id()}"));

      $this->assertNotNull($web_assert->elementExists('css', '.block-book-navigation'));
      $web_assert->pageTextContains($book1->label());
      $web_assert->pageTextContains($page1->label());
      $web_assert->pageTextContains($book2->label());

      $this->visit($path_alias_manager->getAliasByPath("/node/{$page1->id()}"));

      $this->assertNotNull($web_assert->elementExists('css', '.block-book-navigation'));
      $web_assert->pageTextContains($book1->label());
      $web_assert->pageTextContains($page1->label());
      $web_assert->pageTextContains($book2->label());

      $web_assert->pageTextContains($page1->get('body')->first()->getValue()['value']);

      $this->visit($path_alias_manager->getAliasByPath("/node/{$event->id()}"));
      $web_assert->elementNotExists('css', '.block-book-navigation');
      $web_assert->pageTextNotContains($book1->label());
      $web_assert->pageTextNotContains($book2->label());

      $this->assertTrue(TRUE);
    }
    catch (ExpectationException $e) {
      $this->fail(sprintf("Test failed: %s\nBacktrace: %s", $e->getMessage(), $e->getTraceAsString()));
    }
  }

  /**
   * Tests for contextual links.
   */
  public function testBookContextualLinks() {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);
    /** @var \Drupal\book\BookManagerInterface $book_manager */
    $book_manager = $this->container->get('book.manager');
    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    /** @var \Drupal\node\NodeInterface $book1 */
    $book1 = $this->createBookPage([
      'title' => 'Test contextual links',
    ]);
    $book_manager->updateOutline($book1);

    $web_assert = $this->assertSession();
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book1->id()}"));
    $web_assert->pageTextContains('Test contextual links');
    $web_assert->waitForElement('css', '.contextual');
    $web_assert->elementExists('css', '.contextual');
  }

  /**
   * Tests for print-friendly link.
   */
  public function testPagePrintLinks() {
    /** @var \Drupal\book\BookManagerInterface $book_manager */
    $book_manager = $this->container->get('book.manager');
    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    /** @var \Drupal\node\NodeInterface $book1 */
    $book1 = $this->createBookPage([
      'title' => 'Test print links',
    ]);
    $book_manager->updateOutline($book1);

    $web_assert = $this->assertSession();
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book1->id()}"));
    $web_assert->pageTextContains('Test print links');
    $web_assert->linkExists('Printer-friendly version');
    $web_assert->elementExists('css', '.book-printer');
  }

  /**
   * Tests for Add child page link.
   */
  public function testNoAddChildLink() {
    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');
    /** @var \Drupal\node\NodeInterface $book1 */
    $book = $this->createBookPage([
      'title' => 'Book page',
    ]);
    $child = $this->createBookPage([
      'title' => 'Child page',
    ], $book->id());

    $web_assert = $this->assertSession();
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Book page');
    $web_assert->linkNotExists('Add child page');
    $this->visit($path_alias_manager->getAliasByPath("/node/{$child->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Child page');
    $web_assert->linkNotExists('Add child page');
  }

  /**
   * Checks if Book traversal links present on book pages.
   */
  public function assertNoTraversalLinks() {
    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');
    $book = $this->createBookPage([
      'title' => 'First book',
    ]);
    $sub_page = $this->createBookPage(['title' => 'Sub page'], $book->id());
    // Assertions.
    $web_assert = $this->assertSession();
    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Sub page');
    $web_assert->pageTextNotContains('Book Traversal links for Sub page');
    $web_assert->linkNotExists('Up');
    $web_assert->linkNotExists('First book');
  }

  /**
   * Checks Outline access on book/non-book pages.
   */
  public function checkOutlineOnPages() {
    /** @var \Drupal\Core\Path\AliasManagerInterface $path_alias_manager */
    $path_alias_manager = $this->container->get('path.alias_manager');
    $book = $this->createBookPage([
      'title' => 'Book with outline',
    ]);
    $this->addGroupContent($book, $this->group);
    $web_assert = $this->assertSession();
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->linkExists('Outline');
    $web_assert->linkByHrefExists("node/{$book->id()}/book-outline");

    $page = $this->createNode([
      'title' => 'Standalone page',
      'type' => 'page',
    ]);
    $this->addGroupContent($page, $this->group);
    $this->visit($path_alias_manager->getAliasByPath("/node/{$page->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->linkNotExists('Outline');
    $web_assert->linkByHrefNotExists("node/{$page->id()}/book-outline");
    $this->visitViaVsite("node/{$page->id()}/book-outline");
    $web_assert->statusCodeEquals(403);
  }

  /**
   * Tests - hide section navigation widget on layouts widgets section.
   */
  public function assertNoSectionNavWidget() {
    $book = $this->createBookPage([
      'title' => 'First book',
    ]);
    // Assertions.
    $web_assert = $this->assertSession();
    $this->visitViaVsite("node/{$book->id()}?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    $web_assert->pageTextNotContains('Section navigation');
  }

}
