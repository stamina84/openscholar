<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Tests\vsite\ExistingSite\VsiteExistingSiteTestBase;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * VsitePresetHelperTest.
 *
 * @group vsite
 * @group kernel
 */
class VsitePresetHelperTest extends VsiteExistingSiteTestBase {

  /**
   * Apps to enable.
   *
   * @var array
   */
  protected $toEnable;

  /**
   * File uri array.
   *
   * @var array
   */
  protected $uriArr;

  /**
   * Vsite helper service.
   *
   * @var \Drupal\vsite_preset\Helper\VsitePresetHelper
   */
  protected $vsitePresetHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsitePresetHelper = $this->container->get('vsite_preset.preset_helper');
    $this->vsiteContextManager->activateVsite($this->group);
    $this->toEnable = [
      'class' => 'class',
      'publications' => 'publications',
    ];

    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $paths = $preset->getCreationFilePaths();
    $this->uriArr = array_keys($paths['personal']);

  }

  /**
   * Tests Preset Enable apps helper.
   */
  public function testVsitePresetEnableApps() {
    // Test Negative when no data exists.
    /** @var \Drupal\Core\Config\ImmutableConfig $app_access_config */
    $app_access_config = $this->configFactory->get('os_app_access.access');
    $this->assertEmpty($app_access_config->getRawData());

    // Test negative no menu links exists.
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $gid = $this->group->id();
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    $this->assertEmpty($linksArr);

    $this->vsitePresetHelper->enableApps($this->group, $this->toEnable);

    // Test positive case as helper enables apps.
    /** @var \Drupal\Core\Config\ImmutableConfig $app_access_config */
    $app_access_config = $this->configFactory->get('os_app_access.access');
    $this->assertNotEmpty($app_access_config);
    // Check enabled.
    $this->assertEquals($app_access_config->get('class'), '0');
    $this->assertEquals($app_access_config->get('publications'), '0');
    // Check disabled.
    $this->assertEquals($app_access_config->get('faq'), '2');
    $this->assertEquals($app_access_config->get('blog'), '2');

    // Tests positive menu links are created too for enabled apps.
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    // Count should be three keeping in mind Home, Class and Pubications link.
    $this->assertCount(3, $linksArr);
  }

  /**
   * Test preset default content creation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVsitePresetDefaultContentCreation() {

    // Test negative page content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $nodeArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'CV',
    ]);
    $this->assertEmpty($nodeArr);

    // Test negative menu link does not exist for the content.
    $menuStorage = $this->entityTypeManager->getStorage('menu_link_content');
    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Bio',
    ]);
    $this->assertEmpty($menuArr);

    // Retrieve file creation csv source paths.
    foreach ($this->uriArr as $uri) {
      if (strpos($uri, 'node') === FALSE) {
        continue;
      }
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test positive page content is created.
    $nodeArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'CV',
    ]);
    $this->assertNotEmpty($nodeArr);

    // Test positive menu link is created for the content.
    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Bio',
    ]);
    $this->assertNotEmpty($menuArr);
  }

  /**
   * Test preset default widget creation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVsitePresetDefaultWidgetCreation() {

    // Test negative block content does not exist already.
    $gid = $this->group->id();
    $blockStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertEmpty($blockArr);

    // Retrieve file creation csv source paths.
    foreach ($this->uriArr as $uri) {
      if (strpos($uri, 'block_content') === FALSE) {
        continue;
      }
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test positive page content is created.
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertNotEmpty($blockArr);

  }

}
