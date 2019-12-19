<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests Search sort widget creation.
 *
 * @group functional-javascript
 * @group widgets
 * @covers \Drupal\os_search\Plugin\OsWidgets\SearchSortWidget
 */
class SearchSortWidgetTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Widget name.
   *
   * @var string
   */
  protected $widgetName;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User with required permissions.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->user = $this->createUser(['administer blocks', 'administer taxonomy']);
    $this->addGroupAdmin($this->user, $this->group);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests os_search Search Sort widget.
   */
  public function testSearchSort() {
    $web_assert = $this->assertSession();

    $this->visitViaVsite("block/add/search_sort", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();

    $this->widgetName = $this->randomString();
    $edit = [
      'info[0][value]' => $this->widgetName,
      'field_widget_title[0][value]' => $this->widgetName,
    ];
    $this->submitForm($edit, 'edit-submit');
    $web_assert->statusCodeEquals(200);

    $block = $this->entityTypeManager->getStorage('block_content')->loadByProperties(['field_widget_title' => $this->widgetName]);
    $this->assertNotEmpty($block, 'A match was not found which means block was created successfully.');

    $this->placeBlockContentToRegion(current($block), 'sidebar_second');
    $this->visitViaVsite("search", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($this->widgetName);

    // Check that sort links exists.
    $web_assert->linkByHrefExists("{$this->groupAlias}/search?sort=title&dir=ASC");
    $web_assert->linkByHrefExists("{$this->groupAlias}/search?sort=type&dir=ASC");
    $web_assert->linkByHrefExists("{$this->groupAlias}/search?sort=date&dir=ASC");
  }

  /**
   * Delete the widget created during testing.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function tearDown() {
    $blocks = $this->entityTypeManager->getStorage('block_content')->loadByProperties(['field_widget_title' => $this->widgetName]);
    /** @var \Drupal\block_content\Entity\BlockContent $block */
    foreach ($blocks as $block) {
      $block->delete();
    }
    parent::tearDown();
  }

}
