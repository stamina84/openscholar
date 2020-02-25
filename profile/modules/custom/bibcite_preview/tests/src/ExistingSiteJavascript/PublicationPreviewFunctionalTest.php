<?php

namespace Drupal\Tests\bibcite_preview\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class PublicationPreviewFunctionalTest.
 *
 * @group functional-javascript
 * @group publications
 *
 * @package Drupal\Tests\bibcite_preview\ExistingSiteJavascript
 */
class PublicationPreviewFunctionalTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Reference Entity.
   *
   * @var \Drupal\bibcite_entity\Entity\ReferenceInterface
   */
  protected $reference;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->createAdminUser());
    $this->reference = $this->createReference();
    $this->group->addContent($this->reference, 'group_entity:bibcite_reference');
  }

  /**
   * Test publication edit and press preview and get back.
   */
  public function testPublicationEditPreviewAndBack() {
    $web_assert = $this->assertSession();
    // Visit edit page.
    $this->visitViaVsite('bibcite/reference/' . $this->reference->id() . '/edit', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->fillField('bibcite_year[0][value]', '2019');

    // Find and press Preview button.
    $preview_button = $page->findButton('Preview');
    $preview_button->press();
    $web_assert->statusCodeEquals(200);

    $back_link = $page->findLink('Back to content editing');
    $back_link->press();
    $web_assert->statusCodeEquals(200);

    // Check going back to edit page and see modified title.
    $page = $this->getCurrentPage();
    $this->assertContains('2019', $page->getHtml());
  }

  /**
   * Test publication create and press preview get back.
   */
  public function testPublicationCreatePreviewAndBack() {
    // Visit edit page.
    $this->visitViaVsite('bibcite/reference/add/artwork', $this->group);
    $web_assert = $this->assertSession();
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->fillField('bibcite_year[0][value]', '1990');

    // Find and press Preview button.
    $preview_button = $page->findButton('Preview');
    $preview_button->press();
    $web_assert->statusCodeEquals(200);

    $back_link = $page->findLink('Back to content editing');
    $back_link->press();
    $web_assert->statusCodeEquals(200);

    // Check going back to edit page and see modified title.
    $page = $this->getCurrentPage();
    $this->assertContains('1990', $page->getHtml());
  }

  /**
   * Tests Preview works with destination parameter in url for publications.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPublicationPreviewWithDestination(): void {
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
    $destination = '/edit?destination=' . $this->group->get('path')->getValue()[0]['alias'];
    $this->visitViaVsite('bibcite/reference/' . $this->reference->id() . $destination, $this->group);
    $this->getSession()->getPage()->pressButton('Preview');
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertContains('preview', $current_url);
    $this->assertContains('citation', $current_url);
  }

}
