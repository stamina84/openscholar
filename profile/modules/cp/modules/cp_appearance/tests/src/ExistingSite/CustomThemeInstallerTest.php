<?php

namespace Drupal\Tests\cp_appearance\ExistingSite;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionNameLengthException;

/**
 * Tests custom_theme_installer service.
 *
 * @group kernel
 * @group cp-appearance
 * @coversDefaultClass \Drupal\cp_appearance\CustomThemeInstaller
 */
class CustomThemeInstallerTest extends TestBase {

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Mocked route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);
    $this->routeBuilder = $this->prophesize(RouteBuilderInterface::class);
  }

  /**
   * @covers ::install
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   */
  public function testInstall(): void {
    /** @var \Drupal\Core\Config\ImmutableConfig $extension_config */
    $extension_config = $this->configFactory->get('core.extension');
    /** @var \Drupal\Core\State\StateInterface $state_store */
    $state_store = $this->container->get('state');
    /** @var \Drupal\cp_appearance\CustomThemeInstaller $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');

    // Test.
    $custom_theme_installer->install([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);

    $this->routeBuilder->setRebuildNeeded()->shouldNotBeCalled();
    $this->assertCacheTagsNotInvalidated();

    $theme_weight = $extension_config->get('theme.' . self::TEST_CUSTOM_THEME_3_NAME);
    $this->assertNotNull($theme_weight);

    $theme_data = $state_store->get('system.theme.data');
    $this->assertTrue(isset($theme_data[self::TEST_CUSTOM_THEME_3_NAME]));

    $block = $this->configFactory->get('block.block.' . self::TEST_CUSTOM_THEME_3_NAME . '_main_menu');
    $this->assertNotNull($block->get('id'));

    $this->assertTrue($this->themeHandler->themeExists(self::TEST_CUSTOM_THEME_3_NAME));
  }

  /**
   * @covers ::uninstall
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   */
  public function testUninstall(): void {
    /** @var \Drupal\cp_appearance\CustomThemeInstaller $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');
    /** @var \Drupal\Core\Config\ImmutableConfig $extension_config */
    $extension_config = $this->configFactory->get('core.extension');
    /** @var \Drupal\Core\State\StateInterface $state_store */
    $state_store = $this->container->get('state');
    $custom_theme_installer->install([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);

    // Test.
    $custom_theme_installer->uninstall([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);

    $this->routeBuilder->setRebuildNeeded()->shouldNotBeCalled();
    $this->assertCacheTagsNotInvalidated();

    $theme_weight = $extension_config->get('theme.' . self::TEST_CUSTOM_THEME_3_NAME);
    $this->assertNull($theme_weight);

    $theme_data = $state_store->get('system.theme.data');
    $this->assertFalse(isset($theme_data[self::TEST_CUSTOM_THEME_3_NAME]));

    $this->assertFalse($this->themeHandler->themeExists(self::TEST_CUSTOM_THEME_3_NAME));
  }

  /**
   * Tests install exceptions.
   *
   * @covers ::install
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   */
  public function testInstallException(): void {
    /** @var \Drupal\cp_appearance\CustomThemeInstaller $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');

    $this->expectException(ExtensionNameLengthException::class);
    $custom_theme_installer->install([
      $this->randomMachineName(DRUPAL_EXTENSION_NAME_MAX_LENGTH + 1),
    ]);
  }

  /**
   * Tests uninstall exceptions.
   *
   * @covers ::uninstall
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   */
  public function testUninstallException(): void {
    // Setup.
    /** @var \Drupal\cp_appearance\CustomThemeInstaller $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');
    $theme_setting = $this->configFactory->getEditable('system.theme');
    $default_theme = $theme_setting->get('default');
    $default_admin_theme = $theme_setting->get('admin');
    $custom_theme_installer->install([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);

    // Test UnknownExtensionException.
    $this->expectException(UnknownExtensionException::class);
    $custom_theme_installer->uninstall([
      $this->randomMachineName(),
    ]);

    // Test InvalidArgumentException.
    $theme_setting->set('default', self::TEST_CUSTOM_THEME_3_NAME)->save();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The current default theme ' . self::TEST_CUSTOM_THEME_3_NAME . ' cannot be uninstalled.');
    $custom_theme_installer->uninstall([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);
    $theme_setting->set('default', $default_theme)->save();

    $theme_setting->set('admin', self::TEST_CUSTOM_THEME_3_NAME)->save();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The current administration theme ' . self::TEST_CUSTOM_THEME_3_NAME . ' cannot be uninstalled.');
    $custom_theme_installer->uninstall([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);
    $theme_setting->set('admin', $default_admin_theme)->save();

    // Cleanup.
    $custom_theme_installer->uninstall([
      self::TEST_CUSTOM_THEME_3_NAME,
    ]);
  }

  /**
   * @covers ::makeInstallable
   *
   * @throws \Drupal\cp_appearance\Entity\CustomThemeException
   */
  public function testMakeInstallable(): void {
    /** @var \Drupal\cp_appearance\CustomThemeInstallerInterface $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');
    $custom_theme_installer->makeInstallable();

    $link_target = readlink(DRUPAL_ROOT . '/' . CustomTheme::CUSTOM_THEMES_DRUPAL_LOCATION);
    $this->assertNotFalse($link_target);
  }

  /**
   * Asserts that the desired cache tags are not invalidated.
   */
  protected function assertCacheTagsNotInvalidated(): void {
    $this->cacheTagsInvalidator->invalidateTags(['local_task'])->shouldNotBeCalled();
    $this->cacheTagsInvalidator->invalidateTags(['theme_registry'])->shouldNotBeCalled();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    if ($this->themeHandler->themeExists(self::TEST_CUSTOM_THEME_3_NAME)) {
      /** @var \Drupal\cp_appearance\CustomThemeInstaller $custom_theme_installer */
      $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');
      $custom_theme_installer->uninstall([
        self::TEST_CUSTOM_THEME_3_NAME,
      ]);
    }

    parent::tearDown();
  }

}
