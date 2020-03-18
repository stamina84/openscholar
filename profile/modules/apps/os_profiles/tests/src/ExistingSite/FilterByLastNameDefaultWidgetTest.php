<?php

namespace Drupal\Tests\os_profiles\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * FilterByLastNameDefaultWidgetTest.
 *
 * @group profiles
 * @group kernel
 */
class FilterByLastNameDefaultWidgetTest extends OsExistingSiteTestBase {

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
    $this->group = $this->createGroup();
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test Filter by alphabetical grouping of Last Name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterByLastNameDefaultWidgetCreation() {

    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('minimal');
    $paths = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);

    // Test negative, block content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Filter by alphabetical grouping of Last Name',
    ]);
    $this->assertEmpty($blockArr);

    // Retrieve file creation csv source path and call creation method.
    foreach ($paths as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test positive block content is created.
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Filter by alphabetical grouping of Last Name',
    ]);
    $this->assertNotEmpty($blockArr);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;

    // Assert correct view and display is selected.
    $blockContentEntity = $this->entityTypeManager->getStorage('block_content')->load($blockEntityId);
    $field_view_view = $blockContentEntity->field_view->target_id;
    $field_view_display = $blockContentEntity->field_view->display_id;
    $info = $blockContentEntity->get('info')->value;
    $this->assertEquals('Filter by alphabetical grouping of Last Name', $info);
    $this->assertEquals('people', $field_view_view);
    $this->assertEquals('block_2', $field_view_display);
  }

}
