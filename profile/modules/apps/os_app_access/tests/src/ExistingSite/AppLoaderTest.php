<?php

namespace Drupal\Tests\os_app_access\ExistingSite;

use Drupal\os_app_access\AppAccessLevels;

/**
 * AppLoaderTest.
 *
 * @group kernel
 * @group os
 */
class AppLoaderTest extends AppAccessTestBase {

  /**
   * Test group member.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember);
  }

  /**
   * Tests if app definitions are returned correctly.
   */
  public function testAppLoader(): void {
    /** @var \Drupal\os_app_access\AppLoaderInterface $app_loader */
    $app_loader = $this->container->get('os_app_access.app_loader');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $definitions = $app_loader->getAppsForUser($this->groupMember);
    $this->assertNotEmpty($definitions);
    foreach ($definitions as $def) {
      $ids[] = $def['id'];
    }
    $this->assertContains('blog', $ids);

    // Disable blog app and check blog is not returned.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    $mut_app_access_config->set('blog', AppAccessLevels::DISABLED)->save();

    $newDefinitions = $app_loader->getAppsForUser($this->groupMember);
    $this->assertNotEmpty($newDefinitions);
    foreach ($newDefinitions as $def) {
      $newIds[] = $def['id'];
    }
    $this->assertNotContains('blog', $newIds);
  }

}
