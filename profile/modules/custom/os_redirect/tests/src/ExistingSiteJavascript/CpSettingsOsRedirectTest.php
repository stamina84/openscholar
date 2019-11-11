<?php

namespace Drupal\Tests\os_redirect\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests os_redirect module.
 *
 * @group functional-javascript
 * @group redirect-settings
 */
class CpSettingsOsRedirectTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    echo "setUp starting...\n";
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
    echo "logged in...\n";
  }

  /**
   * Tests os_redirect cp settings form behavior.
   */
  public function testCpSettingsFormSave(): void {
    $web_assert = $this->assertSession();

    echo "visit settings...\n";
    $this->visitViaVsite("cp/settings/global-settings/redirect_maximum", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    echo "set field...\n";
    $page->fillField('maximum_number', 20);
    echo "press button...\n";
    $page->pressButton('Save configuration');
    echo "get page...\n";
    $page = $this->getCurrentPage();
    $check_html_value = $page->hasContent('The configuration options have been saved.');
    $this->assertTrue($check_html_value, 'The form did not write the correct message.');

    // Check form elements load default values.
    echo "visit again...\n";
    $this->visitViaVsite("cp/settings/global-settings/redirect_maximum", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    echo "find field...\n";
    $field_value = $page->findField('maximum_number')->getValue();
    $this->assertSame('20', $field_value, 'Form is not loaded maximum_number value.');
    echo "finish";
  }

}
