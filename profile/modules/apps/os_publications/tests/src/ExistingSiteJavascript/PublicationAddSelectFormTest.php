<?php

namespace Drupal\Tests\os_publications\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Create test for adding publications by new custom form.
 *
 * @group functional-javascript
 * @group publications
 */
class PublicationAddSelectFormTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * Test reach the proper form and redirected.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testAddPublicationByNewSelectForm(): void {
    $web_assert = $this->assertSession();
    $this->visitViaVsite("bibcite/reference/add-select", $this->group);
    $web_assert->statusCodeEquals(200);

    $bundle_url_select_found = $this->getCurrentPage()->hasField('bundle_url');
    $this->assertTrue($bundle_url_select_found);
    $this->getCurrentPage()->selectFieldOption('bundle_url', 'Artwork');
    $this->waitForAjaxToFinish();
    $web_assert->statusCodeEquals(200);
    $web_assert->waitForText('Create Artwork');
    // Assert redirected to publication form page.
    $web_assert->pageTextContains('Create Artwork');
    $current_url = $this->getUrl();
    $this->assertContains('bibcite/reference/add/artwork', $current_url);
  }

}
