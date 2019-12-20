<?php

namespace Drupal\Tests\cp\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * CpCancelButtonTest.
 *
 * @group functional
 * @group cp
 */
class CpCancelButtonTest extends OsExistingSiteTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $node = $this->createNode();
    $this->group->addContent($node, "group_node:{$node->bundle()}");

    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Test for visit from listing page and press cancel.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testNodeDeleteCancelButtonList(): void {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Visit cp browse path.
    $this->visitViaVsite('cp/content/browse/node', $this->group);
    $web_assert->statusCodeEquals(200);
    /** @var \Behat\Mink\Element\NodeElement|null $edit_link */
    $edit_link = $this->getSession()->getPage()->find('css', '.view-id-site_content .view-content .views-field-dropbutton .edit-node a');
    $this->assertNotNull($edit_link);
    $edit_link->click();
    // Go to edit path.
    $page = $this->getCurrentPage();
    $cancel_button = $page->findLink('Cancel');
    // Click to cancel.
    $cancel_button->press();
    $web_assert->statusCodeEquals(200);

    // Assert url is a browse path with group alias.
    $url = $session->getCurrentUrl();
    $this->assertContains($this->group->get('path')->first()->getValue()['alias'] . '/cp/content', $url);
  }

}
