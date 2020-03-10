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
    /** @var \Drupal\Core\Entity\EntityStorageInterface $path_alias_manager */
    $path_alias_manager = $this->container->get('entity_type.manager')->getStorage('path_alias');
    $this->node = $this->createNode();
    $this->group->addContent($this->node, "group_node:{$this->node->bundle()}");
    $exist_aliases = $path_alias_manager->loadByProperties(['path' => '/node/' . $this->node->id()]);
    $exist_alias = array_pop($exist_aliases);
    $this->nodePath = $this->groupAlias . $exist_alias->getAlias();
    // Fix group alias of the node.
    $exist_alias->setAlias('/[vsite:' . $this->group->id() . ']' . $exist_alias->getAlias());
    $exist_alias->save();

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
