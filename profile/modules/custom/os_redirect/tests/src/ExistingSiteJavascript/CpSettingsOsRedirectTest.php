<?php

namespace Drupal\Tests\os_redirect\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests os_redirect module.
 *
 * @group functional-javascript
 * @group redirect
 */
class CpSettingsOsRedirectTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * User with administer redirect permission access.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $siteAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->siteAdmin = $this->createUser([
      'administer control panel redirects',
    ]);
    $this->addGroupAdmin($this->siteAdmin, $this->group);
  }

  /**
   * Restrict os_redirect settings access.
   */
  public function testOsRedirectSettingsAccess() {
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/global-settings/redirect_maximum', $this->group);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests os_redirect cp settings form behavior.
   */
  public function testCpSettingsFormSave(): void {
    $this->drupalLogin($this->siteAdmin);

    $web_assert = $this->assertSession();
    $this->visitViaVsite("cp/settings/global-settings/redirect_maximum", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->fillField('maximum_number', 20);
    $page->pressButton('Save configuration');
    $page = $this->getCurrentPage();
    $check_html_value = $page->hasContent('The configuration options have been saved.');
    $this->assertTrue($check_html_value, 'The form did not write the correct message.');

    // Check form elements load default values.
    $this->visitViaVsite("cp/settings/global-settings/redirect_maximum", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $field_value = $page->findField('maximum_number')->getValue();
    $this->assertSame('20', $field_value, 'Form is not loaded maximum_number value.');
  }

}
