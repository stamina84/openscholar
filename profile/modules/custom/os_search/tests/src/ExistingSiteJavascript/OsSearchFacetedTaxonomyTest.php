<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

/**
 * OsSearchFacetedTaxonomyTest.
 *
 * @group functional-javascript
 * @group os-search
 */
class OsSearchFacetedTaxonomyTest extends SearchJavascriptTestBase {

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

    $web_assert = $this->assertSession();

    $this->visitViaVsite("?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    $web_assert->pageTextNotContains('Faceted Taxonomy');

  }

}
