<?php

namespace Drupal\Tests\os_media\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * MediaAdminUITest.
 *
 * @group functional
 * @group os
 */
class MediaAdminUiTest extends OsExistingSiteTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // User setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);

    // Media setup.
    $pdf_media = $this->createMedia([], 'pdf');
    $this->group->addContent($pdf_media, 'group_entity:media');
    $tar_media = $this->createMedia([
      'bundle' => [
        'target_id' => 'executable',
      ],
    ], 'tar');
    $this->group->addContent($tar_media, 'group_entity:media');

    // Node setup.
    $faq = $this->createNode([
      'type' => 'faq',
      'title' => "Witch's Cauldron",
      'field_attached_media' => [
        'target_id' => $pdf_media->id(),
      ],
    ]);
    $this->group->addContent($faq, 'group_node:faq');
    $presentation = $this->createNode([
      'type' => 'presentation',
      'title' => "Witch's Cauldron",
      'field_presentation_slides' => [
        'target_id' => $pdf_media->id(),
      ],
    ]);
    $this->group->addContent($presentation, 'group_node:presentation');
    $software_project = $this->createNode([
      'type' => 'software_project',
      'title' => 'Horseshoe Overlook',
    ]);
    $this->group->addContent($software_project, 'group_node:software_project');
    $software_release = $this->createNode([
      'type' => 'software_release',
      'field_software_package' => [
        'target_id' => $tar_media->id(),
      ],
      'field_software_project' => [
        'target_id' => $software_project->id(),
      ],
      'field_software_version' => 'Chapter 1',
    ]);
    $this->group->addContent($software_release, 'group_node:software_release');

    // Publication setup.
    $reference = $this->createReference([
      'html_title' => "Reverend Swanson's Bible",
      'field_attach_files' => [
        'target_id' => $pdf_media->id(),
      ],
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

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

  /**
   * Tests "used in" filter.
   *
   * @covers \Drupal\os_media\Plugin\views\filter\MediaUsageFilter
   * @covers ::os_media_views_data_alter
   * @covers ::os_media_preprocess_views_view_field
   * @covers ::os_media_media_admin_ui_filter_submit_handler
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testMediaUsage(): void {
    $this->visitViaVsite('cp/content/browse/media', $this->group);
    $media_results_selector = '.view-id-os_media.view-display-id-page_1 table tbody tr';

    // Check if all media usages appear if not filtered by "used in".
    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()->getPage()->findAll('css', $media_results_selector);
    $this->assertCount(2, $rows);

    $this->assertEquals("Witch's Cauldron, Witch's Cauldron, Reverend Swanson's Bible", $rows[0]->find('css', 'td.views-field-nothing-4')->getText());
    $this->assertEquals('Horseshoe Overlook Chapter 1', $rows[1]->find('css', 'td.views-field-nothing-4')->getText());

    // Check if "used in" filter is working.
    // Check if only the content matching the "used in" value are only shown.
    // Assert node usages.
    $this->getSession()->getPage()->fillField('os_media_media_usage_filter', 'itch');
    $this->getSession()->getPage()->pressButton('Apply');

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()->getPage()->findAll('css', $media_results_selector);
    $this->assertCount(1, $rows);

    $this->assertEquals("Witch's Cauldron, Witch's Cauldron", $rows[0]->find('css', 'td.views-field-nothing-4')->getText());

    $this->getSession()->getPage()->fillField('os_media_media_usage_filter', 'Horse');
    $this->getSession()->getPage()->pressButton('Apply');

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()->getPage()->findAll('css', $media_results_selector);
    $this->assertCount(1, $rows);

    $this->assertEquals('Horseshoe Overlook Chapter 1', $rows[0]->find('css', 'td.views-field-nothing-4')->getText());

    // Assert publication usage.
    $this->getSession()->getPage()->fillField('os_media_media_usage_filter', 'Swan');
    $this->getSession()->getPage()->pressButton('Apply');

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()->getPage()->findAll('css', $media_results_selector);
    $this->assertCount(1, $rows);

    $this->assertEquals("Reverend Swanson's Bible", $rows[0]->find('css', 'td.views-field-nothing-4')->getText());
  }

}
