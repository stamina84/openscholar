<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

use Drupal\os_widgets\Plugin\DisplayVariant\PlaceBlockPageVariant;

/**
 * OsSearchJsTest.
 *
 * @group functional-javascript
 * @group os-search
 */
class OsSearchLayoutJsTest extends SearchJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Test to check facet widget search should not be listed.
   */
  public function testIgnoreBlockList(): void {
    $ignored_block_list = PlaceBlockPageVariant::IGNORE_BLOCK_TYPE_LIST;

    $web_assert = $this->assertSession();

    $this->visitViaVsite("?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    $block_types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple($ignored_block_list);

    foreach ($block_types as $block_type) {
      $web_assert->pageTextNotMatches('/' . $block_type->label() . '\b/');
    }

  }

}
