<?php

namespace Drupal\Tests\vsite\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests vsite module.
 *
 * @group functional-javascript
 * @group vsite
 * @coversDefaultClass \Drupal\vsite\Form\MySitesForm
 */
class MySitesPageTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $vsiteOwner;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteOwner = $this->createUser();
    $this->addGroupAdmin($this->vsiteOwner, $this->group);
    $this->group->setOwner($this->vsiteOwner)->save();
    $this->drupalLogin($this->vsiteOwner);
  }

  /**
   * Tests if My Sites link exist under user menu.
   */
  public function testMySitesLinkExists() {
    $web_assert = $this->assertSession();
    $this->visitViaVsite('mysites', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();

    $user_menu = $page->find('css', '.toolbar-icon-user:not(.is-active)');
    $user_menu->click();

    $user_menu_links = $page->find('css', '#toolbar-item-user-tray li a');
    $user_menu_links->hasLink('My Sites');
  }

  /**
   * Tests if My sites listing appears with form.
   */
  public function testMySitesForm() {
    $web_assert = $this->assertSession();
    $this->visitViaVsite('mysites', $this->group);
    $web_assert->elementExists('css', 'form.my-sites-form');
  }

  /**
   * Tests if relevant sites are listed under My Sites & Other Sites sections.
   */
  public function testMySitesSections() {
    // Section - Other sites.
    $other_group = $this->createGroup();
    $other_group->addMember($this->vsiteOwner);

    $this->visitViaVsite('mysites', $this->group);

    $page = $this->getCurrentPage();
    $sites_wrapper = $page->findById('edit-my-vsites-owner')->getHtml();
    $this->assertContains($this->group->get('label')->value, $sites_wrapper);
    $sites_wrapper = $page->findById('edit-my-vsites-member')->getHtml();
    $this->assertContains($other_group->get('label')->value, $sites_wrapper);
  }

}
