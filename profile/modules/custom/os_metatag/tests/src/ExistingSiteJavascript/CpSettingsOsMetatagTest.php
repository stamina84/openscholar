<?php

namespace Drupal\Tests\os_metatag\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests os_metatag module.
 *
 * @group functional-javascript
 * @group metatag
 */
class CpSettingsOsMetatagTest extends OsExistingSiteJavascriptTestBase {

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
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Tests os_metatag cp settings form behavior.
   */
  public function testCpSettingsFormSave(): void {
    $web_assert = $this->assertSession();
    $this->drupalLogin($this->groupAdmin);

    $this->visitViaVsite("cp/settings/global-settings/seo", $this->group);
    $web_assert->statusCodeEquals(200);

    $edit = [
      'site_title' => 'Test Site Title',
      'meta_description' => 'LoremIpsumDolor',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $page = $this->getCurrentPage();
    $checkHtmlValue = $page->hasContent('The configuration options have been saved.');
    $this->assertTrue($checkHtmlValue, 'The form did not write the correct message.');

    // Check form elements load default values.
    $this->visitViaVsite("cp/settings/global-settings/seo", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $fieldValue = $page->findField('site_title')->getValue();
    $this->assertSame('Test Site Title', $fieldValue, 'Form is not loaded site title value.');
    $fieldValue = $page->findField('meta_description')->getValue();
    $this->assertSame('LoremIpsumDolor', $fieldValue, 'Form is not loaded meta description value.');
  }

  /**
   * Tests os_metatag cp settings form behavior.
   */
  public function testHtmlHeadValuesOnFrontPage(): void {
    $web_assert = $this->assertSession();
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite("cp/settings/global-settings/seo", $this->group);
    $web_assert->statusCodeEquals(200);

    $edit = [
      'site_title' => 'Test Site Title<>',
      'meta_description' => 'LoremIpsumDolor<"">',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $web_assert->statusCodeEquals(200);
    $this->visitViaVsite('', $this->group);
    $expectedHtmlValue = '<meta name="description" content="LoremIpsumDolor&amp;lt;&amp;quot;&amp;quot;&amp;gt;">';
    $this->assertContains($expectedHtmlValue, $this->getCurrentPageContent(), 'HTML head is not contains description.');
    $expectedHtmlValue = '<meta property="og:title" content="Test Site Title<>">';
    $this->assertContains($expectedHtmlValue, $this->getCurrentPageContent(), 'HTML head is not contains og title.');
  }

}
