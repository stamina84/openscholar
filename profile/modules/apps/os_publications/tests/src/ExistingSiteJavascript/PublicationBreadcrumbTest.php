<?php

namespace Drupal\Tests\os_publications\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Testing breadcrumbs for Publication pages.
 *
 * @group functional-javascript
 * @group publications
 */
class PublicationBreadcrumbTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * Group member.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember);
  }

  /**
   * Test reach the proper form and redirected.
   */
  public function testBreadcrumbs(): void {
    $this->drupalLogin($this->groupMember);
    $web_assert = $this->assertSession();
    $this->visitViaVsite("publications", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->elementExists('css', '.breadcrumb li');
    $web_assert->elementsCount('css', '.breadcrumb li', 2);
    $web_assert->elementTextContains('css', 'ol.breadcrumb li a', 'Home');
    $web_assert->pageTextContains('Publications');
    $this->visitViaVsite("publications/type", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->elementsCount('css', '.breadcrumb li', 2);
    $web_assert->elementTextNotContains('css', '.breadcrumb li', 'Publications by Type');
  }

  /**
   * Test Publications after modifying sort category.
   */
  public function testModifySortCategory() {
    $this->drupalLogin($this->groupAdmin);
    $web_assert = $this->assertSession();
    $script = <<<JS
    jQuery(document).ready(function(e) {
      jQuery("#edit-biblio-sort").val('year');
    });
JS;
    $this->visitViaVsite("cp/settings/apps-settings/publications", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->selectExists('edit-biblio-sort');
    $this->getSession()->executeScript($script);
    $this->getSession()->getPage()->pressButton('Save');
    $this->getSession()->getPage()->selectFieldOption('edit-biblio-sort', 'year');
    $this->drupalLogout();
    // Logging as group member.
    $this->drupalLogin($this->groupMember);
    $this->visitViaVsite("publications", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->elementsCount('css', '.breadcrumb li', 2);
    $web_assert->elementTextNotContains('css', '.breadcrumb li', 'Publications by Year');
  }

}
