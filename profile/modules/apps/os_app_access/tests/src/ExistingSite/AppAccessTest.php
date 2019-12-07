<?php

namespace Drupal\Tests\os_app_access\ExistingSite;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\os_app_access\AppAccessLevels;

/**
 * AppAccessTest.
 *
 * @covers \Drupal\os_app_access\AppAccessLevels
 * @coversDefaultClass \Drupal\os_app_access\Access\AppAccess
 * @group kernel
 * @group os
 */
class AppAccessTest extends AppAccessTestBase {

  /**
   * Test group admin.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupAdmin;

  /**
   * Test group member.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupMember;

  /**
   * Test non group member.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $nonGroupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember);
    $this->nonGroupMember = $this->createUser();
  }

  /**
   * @covers ::access
   */
  public function testAccessPublicAccessLevel(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');

    // Public access level test.
    $mut_app_access_config->set('blog', AppAccessLevels::PUBLIC)->save();
    $this->assertInstanceOf(AccessResultNeutral::class, $os_app_access_service->access($this->nonGroupMember, 'blog'));
  }

  /**
   * @covers ::access
   */
  public function testAccessPrivateAccessLevelGroupMember(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');

    $mut_app_access_config->set('blog', AppAccessLevels::PRIVATE)->save();
    $this->assertInstanceOf(AccessResultNeutral::class, $os_app_access_service->access($this->groupMember, 'blog'));

    $vsite_context_manager->activateVsite($this->group);
    $this->assertInstanceOf(AccessResultAllowed::class, $os_app_access_service->access($this->groupMember, 'blog'));
  }

  /**
   * @covers ::access
   */
  public function testAccessPrivateAccessLevelNonGroupMember(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');

    $mut_app_access_config->set('blog', AppAccessLevels::PRIVATE)->save();
    $this->assertInstanceOf(AccessResultNeutral::class, $os_app_access_service->access($this->nonGroupMember, 'blog'));

    $vsite_context_manager->activateVsite($this->group);
    $this->assertInstanceOf(AccessResultForbidden::class, $os_app_access_service->access($this->nonGroupMember, 'blog'));
  }

  /**
   * @covers ::access
   */
  public function testAccessDisabledAccessLevel(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');

    $mut_app_access_config->set('blog', AppAccessLevels::DISABLED)->save();
    $this->assertInstanceOf(AccessResultForbidden::class, $os_app_access_service->access($this->groupAdmin, 'blog'));

  }

  /**
   * @covers ::cacheAccessResult
   */
  public function testCachedAccess(): void {
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');

    $this->assertContains('app:access_changed', $os_app_access_service->access($this->nonGroupMember, 'blog')->getCacheTags());
    $this->assertContains('vsite', $os_app_access_service->access($this->nonGroupMember, 'blog')->getCacheContexts());
  }

  /**
   * @covers ::accessFromRouteMatch
   */
  public function testAccessFromRouteMatchInactiveVsite(): void {
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');

    $this->assertInstanceOf(AccessResultNeutral::class, $os_app_access_service->accessFromRouteMatch($this->groupAdmin, 'blog'));
  }

  /**
   * @covers ::accessFromRouteMatch
   */
  public function testAccessFromRouteMatchDisabledAccessLevel(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $mut_app_access_config->set('blog', AppAccessLevels::DISABLED)->save();
    $this->assertInstanceOf(AccessResultForbidden::class, $os_app_access_service->accessFromRouteMatch($this->groupAdmin, 'blog'));
  }

  /**
   * @covers ::accessFromRouteMatch
   */
  public function testAccessFromRouteMatchPublicAccessLevel(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $mut_app_access_config->set('blog', AppAccessLevels::PUBLIC)->save();
    $this->assertInstanceOf(AccessResultAllowed::class, $os_app_access_service->accessFromRouteMatch($this->nonGroupMember, 'blog'));
  }

  /**
   * @covers ::accessFromRouteMatch
   */
  public function testAccessFromRouteMatchPrivateAccessLevelGroupMember(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $mut_app_access_config->set('blog', AppAccessLevels::PRIVATE)->save();
    $this->assertInstanceOf(AccessResultAllowed::class, $os_app_access_service->accessFromRouteMatch($this->groupMember, 'blog'));
  }

  /**
   * @covers ::accessFromRouteMatch
   */
  public function testAccessFromRouteMatchPrivateAccessLevelNonGroupMember(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_app_access_config */
    $mut_app_access_config = $config_factory->getEditable('os_app_access.access');
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $mut_app_access_config->set('blog', AppAccessLevels::PRIVATE)->save();
    $this->assertInstanceOf(AccessResultForbidden::class, $os_app_access_service->accessFromRouteMatch($this->nonGroupMember, 'blog'));
  }

  /**
   * @covers ::cacheAccessResult
   */
  public function testCachedAccessFromRouteMatch(): void {
    /** @var \Drupal\os_app_access\Access\AppAccess $os_app_access_service */
    $os_app_access_service = $this->container->get('os_app_access.app_access');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);

    $this->assertContains('app:access_changed', $os_app_access_service->accessFromRouteMatch($this->nonGroupMember, 'blog')->getCacheTags());
    $this->assertContains('vsite', $os_app_access_service->accessFromRouteMatch($this->nonGroupMember, 'blog')->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    unset($this->groupAdmin);
    unset($this->groupMember);
    unset($this->nonGroupMember);
  }

}
