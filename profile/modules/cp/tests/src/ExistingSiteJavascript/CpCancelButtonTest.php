<?php

namespace Drupal\Tests\cp\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * CpCancelButtonTest.
 *
 * @group functional-javascript
 * @group cp
 */
class CpCancelButtonTest extends OsExistingSiteJavascriptTestBase {

  protected $node;
  protected $nodePath;
  protected $vsiteAlias;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = $this->container->get('path.alias_storage');
    $this->vsiteAlias = $this->group->get('path')->first()->getValue()['alias'];
    $this->node = $this->createNode();
    $this->group->addContent($this->node, "group_node:{$this->node->bundle()}");
    $exist_alias = $path_alias_storage->load(['source' => '/node/' . $this->node->id()]);
    $this->nodePath = $this->vsiteAlias . $exist_alias['alias'];

    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Test for visit from node page and press cancel.
   */
  public function testNodeDeleteCancelButtonPage() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Visit node.
    $this->visit($this->nodePath);
    $this->assertSession()->waitForElement('css', '.contextual-links .entitynodeedit-form');
    $this->assertSession()->statusCodeEquals(200);
    /** @var \Behat\Mink\Element\NodeElement|null $edit_contextual_link */
    $edit_contextual_link = $this->getSession()->getPage()->find('css', '.contextual-links .entitynodeedit-form a');
    $edit_contextual_link->press();
    // Go to edit path.
    $page = $this->getCurrentPage();
    $cancel_button = $page->findLink('Cancel');
    // Click to cancel.
    $cancel_button->press();
    $web_assert->statusCodeEquals(200);

    // Assert url is a node path with group alias.
    $url = $session->getCurrentUrl();
    $this->assertContains($this->nodePath, $url);
  }

}
