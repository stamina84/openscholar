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
    $this->group = $this->createGroup();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->toEnable = [
      'class' => 'class',
      'publications' => 'publications',
    ];
  }

  /**
   * Tests Personal Preset Enable apps helper.
   */
  public function testVsitePersonalPresetEnableApps() {

    // Call helper to enable apps.
    $this->vsitePresetHelper->enableApps($this->group, $this->toEnable, []);

    // Test helper enables apps.
    /** @var \Drupal\Core\Config\ImmutableConfig $app_access_config */
    $app_access_config = $this->configFactory->get('os_app_access.access');
    $this->assertNotEmpty($app_access_config);
    // Check enabled.
    $this->assertEquals($app_access_config->get('class'), '0');
    $this->assertEquals($app_access_config->get('publications'), '0');
    // Check disabled.
    $this->assertEquals($app_access_config->get('faq'), '2');
    $this->assertEquals($app_access_config->get('blog'), '2');

  }

  /**
   * Test personal preset default content creation with links.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVsitePersonalPresetDefaultContentCreation() {

    /** @var \Drupal\vsite_preset\Entity\GroupPreset $preset */
    $preset = GroupPreset::load('personal');
    $paths = $preset->getCreationFilePaths();
    $uriArr = array_keys($paths['personal']);

    // Test negative page content does not exist already.
    $gid = $this->group->id();
    $groupStorage = $this->entityTypeManager->getStorage('group_content');
    $nodeArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'CV',
    ]);
    $this->assertEmpty($nodeArr);

    // Test negative no menu links exists.
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $gid = $this->group->id();
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    $this->assertEmpty($linksArr);

    // Test negative menu link does not exist for the content.
    $menuStorage = $this->entityTypeManager->getStorage('menu_link_content');
    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Bio',
    ]);
    $this->assertEmpty($menuArr);

    // Test negative block content does not exist already.
    $gid = $this->group->id();
    $blockStorage = $this->entityTypeManager->getStorage('group_content');
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertEmpty($blockArr);

    // Retrieve file creation csv source path.
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($this->group, $uri);
    }

    // Test positive page content is created.
    $nodeArr = $groupStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'CV',
    ]);
    $this->assertNotEmpty($nodeArr);

    // Tests positive menu links are created too for enabled apps and Home Link.
    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Home',
    ]);
    $this->assertNotEmpty($menuArr);

    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Class',
    ]);
    $this->assertNotEmpty($menuArr);

    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Publication',
    ]);
    $this->assertNotEmpty($menuArr);
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);

    // Test positive menu link is created for the content.
    $menuArr = $menuStorage->loadByProperties([
      'menu_name' => "menu-primary-$gid",
      'title' => 'Bio',
    ]);
    $this->assertNotEmpty($menuArr);

    // Test positive page content is created.
    $blockArr = $blockStorage->loadByProperties([
      'gid' => $gid,
      'label' => 'Contact Widget',
    ]);
    $this->assertNotEmpty($blockArr);
  }

  /**
   * Tests Minimal Preset Enable apps helper and link creator.
   */
  public function testVsiteMinimalPresetEnableApps() {

    $group = $this->createGroup();
    $this->vsiteContextManager->activateVsite($group);

    $toEnable = [
      'class' => 'class',
      'page' => 'page',
      'publications' => 'publications',
    ];

    $preset = GroupPreset::load('minimal');
    $paths = $preset->getCreationFilePaths();
    $uriArr = array_keys($paths['personal']);

    // Test negative no menu links exists.
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $gid = $group->id();
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    $this->assertEmpty($linksArr);

    $this->vsitePresetHelper->enableApps($group, $toEnable, []);
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($group, $uri);
    }

    // Test positive case as helper enables apps.
    /** @var \Drupal\Core\Config\ImmutableConfig $app_access_config */
    $app_access_config = $this->configFactory->get('os_app_access.access');
    $this->assertNotEmpty($app_access_config);
    // Check enabled.
    $this->assertEquals($app_access_config->get('class'), '0');
    $this->assertEquals($app_access_config->get('publications'), '0');
    $this->assertEquals($app_access_config->get('page'), '0');
    // Check disabled.
    $this->assertEquals($app_access_config->get('faq'), '2');
    $this->assertEquals($app_access_config->get('blog'), '2');

    // Tests positive menu link is created too for the preset.
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    // Count should be one for Home link.
    $this->assertCount(1, $linksArr);
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $link */
    $link = array_values($linksArr)[0];
    $this->assertEquals('Home', $link->getTitle());
  }

  /**
   * Tests Minimal Department Preset Enable apps helper and link creator.
   */
  public function testVsiteMinimalDepartmentPresetEnableApps() {

    $group = $this->createGroup();
    $this->vsiteContextManager->activateVsite($group);

    $toEnable = [
      'page' => 'page',
    ];

    $preset = GroupPreset::load('os_department');
    $paths = $preset->getCreationFilePaths();
    $uriArr = array_keys($paths['department']);

    // Test negative no menu links exists.
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $gid = $group->id();
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    $this->assertEmpty($linksArr);

    $this->vsitePresetHelper->enableApps($group, $toEnable, []);
    foreach ($uriArr as $uri) {
      $this->vsitePresetHelper->createDefaultContent($group, $uri);
    }

    // Test positive case as helper enables apps.
    /** @var \Drupal\Core\Config\ImmutableConfig $app_access_config */
    $app_access_config = $this->configFactory->get('os_app_access.access');
    $this->assertNotEmpty($app_access_config);
    // Check enabled.
    $this->assertEquals($app_access_config->get('page'), '0');

    // Tests positive menu link is created too for the preset.
    $linksArr = $storage->loadByProperties(['menu_name' => "menu-primary-$gid"]);
    // Count should be one for Home link.
    $this->assertCount(1, $linksArr);
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $link */
    $link = array_values($linksArr)[0];
    $this->assertEquals('Home', $link->getTitle());
  }

}
