<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Tests\vsite\ExistingSite\VsiteExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * FeedsDefaultWidgetFunctionalTest.
 *
 * @group vsite
 * @group kernel
 */
class FeedsDefaultWidgetTest extends VsiteExistingSiteTestBase {

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
   * Test Feeds widget is created.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRecentFeedsValues() {
    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $uriArr = $this->vsitePresetHelper->getCreateFilePaths($preset, $this->group);
    // Test negative, block content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Subscribe',
    ]);
    $this->assertEmpty($blockArr);
    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }
    // Test positive block content is created.
    $blockArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Subscribe',
    ]);
    $blockEntity = array_values($blockArr)[0];
    $blockEntityId = $blockEntity->entity_id->target_id;
    // Assert correct field values.
    $blockContentEntity = $this->entityTypeManager->getStorage('block_content')->load($blockEntityId);
    $info = $blockContentEntity->get('info')->value;
    $showAll = $blockContentEntity->get('field_is_show_all_content')->value;
    $this->assertEquals('Subscribe', $info);
    $this->assertEquals(1, $showAll);
  }

}
