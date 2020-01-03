<?php

namespace Drupal\Tests\os_media\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * MediaAdminUITest.
 *
 * @group functional
 * @group os
 *
 * @covers \Drupal\os_media\Plugin\views\filter\MediaUsageFilter
 * @covers ::os_media_views_data_alter
 * @covers ::os_media_preprocess_views_view_field
 * @covers ::os_media_media_admin_ui_filter_submit_handler
 */
class MediaAdminUITest extends OsExistingSiteTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests altered media type filter.
   *
   * @covers ::os_media_form_views_exposed_form_alter
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAlteredTypeFilter(): void {
    $this->visitViaVsite('cp/content/browse/media', $this->group);

    $this->assertSession()->elementExists('css', 'select[id="edit-bundle"] option[value="audio"]');
    $this->assertSession()->elementNotExists('css', 'select[id="edit-bundle"] option[value="remote"]');
    $this->assertSession()->elementNotExists('css', 'select[id="edit-bundle"] option[value="video"]');
  }

}
