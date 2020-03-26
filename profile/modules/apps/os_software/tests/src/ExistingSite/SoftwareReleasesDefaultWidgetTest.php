<?php

namespace Drupal\Tests\os_software\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * SoftwareReleasesFilterDefaultWidgetTest.
 *
 * @group os
 * @group kernel
 */
class SoftwareReleasesDefaultWidgetTest extends OsExistingSiteTestBase {

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
   * Array of paths.
   *
   * @var array
   */
  protected $paths;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->group = $this->createGroup();
    $this->vsiteContextManager->activateVsite($this->group);
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $this->paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
  }

  /**
   * Test Software Releases Default Widget is created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSoftwareReleasesDefaultWidgetCreation() {

    // Test negative, block content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Software Releases',
    ]);
    $this->assertEmpty($blockArr);

    // Retrieve file creation csv source path and call creation method.
    foreach ($this->paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test positive block content is created.
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Software Releases',
    ]);
    $this->assertNotEmpty($blockArr);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;

    // Assert correct view and display is selected.
    $blockContentEntity = $this->entityTypeManager->getStorage('block_content')->load($blockEntityId);
    $field_view_view = $blockContentEntity->field_view->target_id;
    $field_view_display = $blockContentEntity->field_view->display_id;
    $this->assertEquals('software_releases', $field_view_view);
    $this->assertEquals('block_1', $field_view_display);
  }

}