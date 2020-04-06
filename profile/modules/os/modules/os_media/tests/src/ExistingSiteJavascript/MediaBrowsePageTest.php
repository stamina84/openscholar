<?php

namespace Drupal\Tests\os_media\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests browse media file page.
 *
 * @group functional
 * @group other
 */
class MediaBrowsePageTest extends OsExistingSiteJavascriptTestBase {

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
    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * Tests add file button and media file.
   */
  public function testMediaPage(): void {
    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $web_assert = $this->assertSession();
    $web_assert->pageTextContains('Files');

    // Assert Add File button.
    $page = $this->getCurrentPage();
    $link = $page->find('css', '.add_new');
    $link->click();
    $web_assert->waitForText('Select files to Add');

    $select_link = $page->find('css', '#media-browser-file-select');
    $select_link->click();

    // Check if media file is getting created.
    $pdf_media = $this->createMedia([], 'pdf');
    $this->group->addContent($pdf_media, 'group_entity:media');
    $file_name = $pdf_media->getName();

    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $web_assert->pageTextContains($file_name);
    $this->drupalLogout();
  }

  /**
   * Tests Media page permissions.
   */
  public function testMediaPagePermission(): void {
    $web_assert = $this->assertSession();
    // Test Content Editor Role Permissions.
    $content_editor = $this->createUser();
    $this->addGroupContentEditor($content_editor, $this->group);
    $this->drupalLogin($content_editor);
    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    // Assert Add File button.
    $page = $this->getCurrentPage();
    $link = $page->find('css', '.add_new');
    $link->click();
    $web_assert->waitForText('Select files to Add');

    $select_link = $page->find('css', '#media-browser-file-select');
    $select_link->click();

    // Check if media file is getting created.
    $pdf_media = $this->createMedia([], 'pdf');
    $this->group->addContent($pdf_media, 'group_entity:media');
    $file_name = $pdf_media->getName();

    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $web_assert->pageTextContains($file_name);

    $media_results_selector = '.view-id-os_media.view-display-id-page_1 table tbody tr';
    $rows = $this->getSession()->getPage()->findAll('css', $media_results_selector);
    // Check edit link exists.
    $this->assertEquals("Edit", $rows[0]->find('css', '.fa-edit')->getText());

    // Check delete link does not exists.
    $this->assertNull($rows[0]->find('css', '.fa-trash-alt'), 'Delete link is present');
    $this->visitViaVsite('media/' . $pdf_media->id() . '/delete', $this->group);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogout();

    // Test Basic Member Role Permissions.
    $basic_viewer = $this->createUser();
    $this->addGroupEnhancedMember($basic_viewer, $this->group);
    $this->drupalLogin($basic_viewer);
    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $this->assertSession()->statusCodeEquals(403);
  }

}
