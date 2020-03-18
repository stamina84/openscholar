<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Tests\vsite\ExistingSite\VsiteExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * PublicationsDefaultWidgetFunctionalTest.
 *
 * @group vsite
 * @group kernel
 */
class PublicationsDefaultWidgetTest extends VsiteExistingSiteTestBase {

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Vsite helper service.
   *
   * @var \Drupal\vsite_preset\Helper\VsitePresetHelper
   */
  protected $vsitePresetHelper;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->group = $this->createGroup([
      'type' => 'personal',
      'field_preset' => 'personal',
    ]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test Faq widget is created.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRecentPublicationsValues() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $uriArr = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Test negative, block content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Recent Publications',
    ]);
    $this->assertEmpty($blockArr);
    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }
    // Test positive block content is created.
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Recent Publications',
    ]);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;
    // Assert correct field values.
    $blockContentEntity = $this->entityTypeManager->getStorage('block_content')->load($blockEntityId);
    $type = $blockContentEntity->get('field_content_type')->value;
    $displayStyle = $blockContentEntity->get('field_display_style')->value;
    $sortedBy = $blockContentEntity->get('field_sorted_by')->value;
    $widgeTitle = $blockContentEntity->get('field_widget_title')->value;
    $publicationWidget = $blockContentEntity->get('field_publication_types')->getValue();
    $info = $blockContentEntity->get('info')->value;
    $this->assertEquals('publications', $type);
    $this->assertEquals('title', $displayStyle);
    $this->assertEquals('year_of_publication', $sortedBy);
    $this->assertEquals('Recent Publications', $widgeTitle);
    $this->assertEquals('Recent Publications', $info);
    // Random value check for publication_type.
    $this->assertEquals('all', $publicationWidget[0]['value']);
    $this->assertEquals('artwork', $publicationWidget[1]['value']);
    $this->assertEquals('classical', $publicationWidget[9]['value']);
    $this->assertEquals('data', $publicationWidget[12]['value']);
    $this->assertEquals('working_paper', $publicationWidget[38]['value']);
  }

}
