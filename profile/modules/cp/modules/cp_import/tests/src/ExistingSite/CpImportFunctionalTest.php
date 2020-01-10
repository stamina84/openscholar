<?php

namespace Drupal\Tests\cp_import\ExistingSite;

use Drupal\os_app_access\AppAccessLevels;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * CpImportJsTest.
 *
 * @group functional
 * @group cp-import
 */
class CpImportFunctionalTest extends OsExistingSiteTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * AppAccess level.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $levels;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupMember = $this->createUser();
    $this->group = $this->createGroup([
      'path' => [
        'alias' => '/test-alias',
      ],
    ]);
    $this->group->addMember($this->groupMember);
    $this->drupalLogin($this->groupMember);
    $this->levels = $this->configFactory->getEditable('os_app_access.access');
    $this->levels->set('faq', AppAccessLevels::PUBLIC)->save();
  }

  /**
   * Test Faq import form and sample download.
   */
  public function testFaqImportForm() {
    $this->visitViaVsite('cp/content/import/faq', $this->group);
    $session = $this->assertSession();
    // Checks Faq import form opens.
    $session->pageTextContains('FAQ');
    // Check sample download link.
    $url = "/test-alias/cp/content/import/faq/template";
    $session->linkByHrefExists($url);

    // Test sample download link.
    $this->drupalGet('test-alias/cp/content/import/faq/template');
    $this->assertSession()->responseHeaderContains('Content-Type', 'text/csv; charset=utf-8');
    $this->assertSession()->responseHeaderContains('Content-Description', 'File Download');
  }

  /**
   * Checks access for Faq import.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFaqImportPermission() {
    $this->visitViaVsite('cp/content/import/faq', $this->group);
    $session = $this->assertSession();
    // Checks Faq import Access is allowed.
    $session->statusCodeEquals(200);

    $this->levels->set('faq', AppAccessLevels::DISABLED)->save();
    $this->visitViaVsite('cp/content/import/faq', $this->group);
    // Checks access is not allowed.
    $session = $this->assertSession();
    $session->statusCodeEquals(403);
  }

  /**
   * Set the app access to public again for other tests to follow.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function tearDown() {
    $this->levels->set('faq', AppAccessLevels::PUBLIC)->save();
    parent::tearDown();
  }

}
