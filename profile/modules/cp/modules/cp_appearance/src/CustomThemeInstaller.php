<?php

namespace Drupal\cp_appearance;

use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\cp_appearance\Entity\CustomThemeException;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages custom theme installation/uninstallation.
 */
class CustomThemeInstaller implements CustomThemeInstallerInterface {

  /**
   * Theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ThemeInstaller.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to fire themes_installed/themes_uninstalled hooks.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state store.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AssetCollectionOptimizerInterface $css_collection_optimizer, LoggerInterface $logger, StateInterface $state) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->logger = $logger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $theme_list): bool {
    $extension_config = $this->configFactory->getEditable('core.extension');
    $theme_data = $this->themeHandler->rebuildThemeData();
    $themes_installed = [];

    foreach ($theme_list as $key) {
      // Only process themes that are not already installed.
      $installed = $extension_config->get("theme.$key") !== NULL;
      if ($installed) {
        continue;
      }

      // Throw an exception if the theme name is too long.
      if (\strlen($key) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
        throw new ExtensionNameLengthException("Theme name $key is over the maximum allowed length of " . DRUPAL_EXTENSION_NAME_MAX_LENGTH . ' characters.');
      }

      // The value is not used; the weight is ignored for themes currently. Do
      // not check schema when saving the configuration.
      $extension_config
        ->set("theme.$key", 0)
        ->save(TRUE);

      // Add the theme to the current list.
      $theme_data[$key]->status = 1;
      $this->themeHandler->addTheme($theme_data[$key]);

      // Update the current theme data accordingly.
      $current_theme_data = $this->state->get('system.theme.data', []);
      $current_theme_data[$key] = $theme_data[$key];
      $this->state->set('system.theme.data', $current_theme_data);

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);

      $themes_installed[] = $key;

      // Record the fact that it was installed.
      $this->logger->info('%theme theme installed.', ['%theme' => $key]);
    }

    $this->cssCollectionOptimizer->deleteAll();

    // Invoke hook_themes_installed() after the themes have been installed.
    $this->moduleHandler->invokeAll('themes_installed', [$themes_installed]);

    return !empty($themes_installed);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $theme_list): void {
    $extension_config = $this->configFactory->getEditable('core.extension');
    $theme_config = $this->configFactory->getEditable('system.theme');
    $list = $this->themeHandler->listInfo();
    foreach ($theme_list as $key) {
      if (!isset($list[$key])) {
        throw new UnknownExtensionException("Unknown theme: $key.");
      }
      if ($key === $theme_config->get('default')) {
        throw new \InvalidArgumentException("The current default theme $key cannot be uninstalled.");
      }
      if ($key === $theme_config->get('admin')) {
        throw new \InvalidArgumentException("The current administration theme $key cannot be uninstalled.");
      }
    }

    $this->cssCollectionOptimizer->deleteAll();
    $current_theme_data = $this->state->get('system.theme.data', []);
    foreach ($theme_list as $key) {
      // The value is not used; the weight is ignored for themes currently.
      $extension_config->clear("theme.$key");

      // Update the current theme data accordingly.
      unset($current_theme_data[$key]);

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);
    }
    // Don't check schema when uninstalling a theme since we are only clearing
    // keys.
    $extension_config->save(TRUE);
    $this->state->set('system.theme.data', $current_theme_data);

    $this->themeHandler->refreshInfo();

    $this->moduleHandler->invokeAll('themes_uninstalled', [$theme_list]);
  }

  /**
   * {@inheritdoc}
   */
  public function makeInstallable(): void {
    $absolute_installable_path = DRUPAL_ROOT . '/' . CustomTheme::CUSTOM_THEMES_DRUPAL_LOCATION;
    // Warning is intentionally suppressed, as it is known that the symlink will
    // not exist during initiation.
    $link_target = @readlink($absolute_installable_path);

    if (!$link_target) {
      $status = symlink(CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION, $absolute_installable_path);

      if (!$status) {
        throw new CustomThemeException('Unable to make the custom theme installable. Please contact the site administrator for support.');
      }
    }
  }

}