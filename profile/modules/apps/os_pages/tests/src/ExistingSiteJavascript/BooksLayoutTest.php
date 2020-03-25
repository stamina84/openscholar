<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

/**
 * Class BooksLayoutTest.
 *
 * @group functional-javascript
 * @group pages
 */
class BooksLayoutTest extends TestBase {

  /**
   * Test widgets on Book pages.
   */
  public function testWidgetsOnBooks() {
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager */
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $vsiteContextManager->activateVsite($this->group);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->group->setOwner($groupAdmin)->save();
    $this->drupalLogin($groupAdmin);

    // Setup data required for tests.
    $web_assert = $this->assertSession();
    $region = 'content';
    $context = "all_pages";

    // Content setup.
    $widget1 = $this->createBlockContent([
      'info' => 'Apple Widget',
      'field_widget_title' => [
        'value' => 'Apple Widget',
      ],
    ]);
    $this->group->addContent($widget1, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget1, $region, $context, 1);

    $book = $this->createBookPage([
      'title' => 'First book',
    ]);
    $book_context = "os_pages_section_{$book->id()}";
    $sub_page1 = $this->createBookPage([
      'title' => 'Sub page',
    ], $book->id());

    $sub_page2 = $this->createBookPage([
      'title' => 'Sub page 2',
    ], $book->id());

    $this->addGroupContent($book, $this->group);
    $this->addGroupContent($sub_page1, $this->group);
    $this->addGroupContent($sub_page2, $this->group);

    $widget2 = $this->createBlockContent([
      'info' => 'The Doors Widget',
      'field_widget_title' => [
        'value' => 'The Doors Widget',
      ],
    ]);
    $this->group->addContent($widget2, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget2, $region, $book_context, 2);

    // Note: Manually updated the config to replicate the same as in interface.
    $book_context_config = $config_factory->getEditable('os_widgets.layout_context.' . $book_context);
    $data = $book_context_config->get('data');
    $data[] = [
      'id' => 'block_content|' . $widget1->uuid(),
      'region' => $region,
      'weight' => 1,
    ];
    $book_context_config->set('data', $data);
    $book_context_config->save();

    $this->visit($path_alias_manager->getAliasByPath("/node/{$book->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('First book');
    $web_assert->pageTextContains('Apple Widget');
    $web_assert->pageTextContains('The Doors Widget');

    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page2->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Sub page 2');
    $web_assert->pageTextContains('The Doors Widget');
    $this->getSession()->getPage()->findLink('Layout')->click();
    $web_assert->statusCodeEquals(200);
    $this->getSession()->getPage()->findButton('Save')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->statusCodeEquals(200);

    // Removing Apple Widget from this book's context.
    $this->removeBlockFromRegion($widget1, $region, $book_context);

    // Testing Widget2 non-existence on non-book pages.
    $this->visitViaVsite("", $this->group);
    $web_assert->pageTextContains('Apple Widget');
    $web_assert->pageTextNotContains('The Doors Widget');

    // Testing widget2 non-existence from all book pages after removal.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page2->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Sub page 2');
    $web_assert->pageTextContains('The Doors Widget');
    $this->drupalLogout();
  }

  /**
   * Testing no individual config for Book pages.
   */
  public function testConfigOnBooks() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    $book = $this->createBookPage([
      'title' => 'First book',
    ]);

    $sub_page1 = $this->createBookPage([
      'title' => 'Sub page',
    ], $book->id());

    $grand_child = $this->createBookPage([
      'title' => 'Grand child',
    ], $book->id(), $sub_page1->id());

    $book_context = "os_pages_section_{$grand_child->book['bid']}";

    // Check book context exists.
    $book_context_config = $config_factory->getEditable('os_widgets.layout_context.' . $book_context);
    $this->assertNotEmpty($book_context_config->get('data'));
  }

}
