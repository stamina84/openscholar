<?php

namespace Drupal\cp_appearance\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\cp_appearance\AppearanceSettingsBuilderInterface;
use Drupal\cp_appearance\Form\ThemeForm;
use Drupal\os_theme_preview\HandlerInterface;
use Drupal\os_theme_preview\PreviewManagerInterface;
use Drupal\os_theme_preview\ThemePreviewException;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the cp_users page.
 *
 * Also invokes the modals.
 */
class CpAppearanceMainController extends ControllerBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Appearance settings builder.
   *
   * @var \Drupal\cp_appearance\AppearanceSettingsBuilderInterface
   */
  protected $appearanceSettingsBuilder;

  /**
   * Theme preview handler.
   *
   * @var \Drupal\os_theme_preview\HandlerInterface
   */
  protected $previewHandler;

  /**
   * Theme preview manager.
   *
   * @var \Drupal\os_theme_preview\PreviewManagerInterface
   */
  protected $previewManager;

  /**
   * Alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('cp_appearance.appearance_settings_builder'),
      $container->get('os_theme_preview.handler'),
      $container->get('os_theme_preview.manager'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * Creates a new CpAppearanceMainController object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\cp_appearance\AppearanceSettingsBuilderInterface $appearance_settings_builder
   *   Appearance settings builder.
   * @param \Drupal\os_theme_preview\HandlerInterface $handler
   *   Theme preview handler.
   * @param \Drupal\os_theme_preview\PreviewManagerInterface $preview_manager
   *   Theme preview manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   Alias manager.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, AppearanceSettingsBuilderInterface $appearance_settings_builder, HandlerInterface $handler, PreviewManagerInterface $preview_manager, AliasManagerInterface $alias_manager) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->appearanceSettingsBuilder = $appearance_settings_builder;
    $this->previewHandler = $handler;
    $this->previewManager = $preview_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * Entry point for cp/users.
   */
  public function main(): array {
    // Custom themes are installed in vsite config, therefore, refresh is
    // necessary. Otherwise, on cache clear, Drupal picks the theme
    // configuration from default config storage, and it fails to identify
    // installed custom themes.
    $this->themeHandler->refreshInfo();
    /** @var \Drupal\Core\Extension\Extension[] $featured_themes */
    $featured_themes = $this->appearanceSettingsBuilder->getFeaturedThemes();
    /** @var \Drupal\Core\Extension\Extension[] $custom_themes */
    $custom_themes = $this->appearanceSettingsBuilder->getCustomThemes();
    /** @var \Drupal\Core\Extension\Extension[] $one_page_themes */
    $one_page_themes = $this->appearanceSettingsBuilder->getOnePageThemes();

    $themes = array_merge($custom_themes, $featured_themes, $one_page_themes);

    // Use for simple dropdown for now.
    $basic_theme_options = [];
    foreach ($themes as $theme) {
      $basic_theme_options[$theme->getName()] = $theme->info['name'];
    }

    // There are two possible theme groups.
    $theme_groups = [
      'custom_theme' => $custom_themes,
      'featured' => $featured_themes,
      'one_page_theme' => $one_page_themes,
      'basic' => [],
    ];
    $theme_group_titles = [
      'custom_theme' => $this->formatPlural(count($theme_groups['custom_theme']), 'Custom theme', 'Custom themes'),
      'one_page_theme' => $this->formatPlural(count($theme_groups['one_page_theme']), 'One Page theme', 'One Page themes'),
      'featured' => $this->formatPlural(count($theme_groups['featured']), 'Featured theme', 'Standard themes'),
      'basic' => $this->formatPlural(count($theme_groups['basic']), 'Basic theme', 'Basic themes'),
    ];

    uasort($theme_groups['featured'], 'system_sort_themes');
    $this->moduleHandler()->alter('cp_appearance_themes_page', $theme_groups);

    $build = [];
    $build[] = [
      '#theme' => 'cp_appearance_themes_page',
      '#theme_groups' => $theme_groups,
      '#theme_group_titles' => $theme_group_titles,
    ];

    $build[] = $this->formBuilder()->getForm(ThemeForm::class, $basic_theme_options);

    return $build;
  }

  /**
   * Set a theme as default.
   *
   * @param string $theme
   *   The theme name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function setTheme($theme, Request $request): RedirectResponse {
    $config = $this->configFactory->getEditable('system.theme');
    $themes = $this->themeHandler->listInfo();

    // Check if the specified theme is one recognized by the system.
    // Or try to install the theme.
    if (isset($themes[$theme])) {
      $config->set('default', $theme)->save();

      $this->messenger()->addStatus($this->t('%theme is now your theme.', ['%theme' => $themes[$theme]->info['name']]));
    }
    else {
      $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
    }

    return $this->redirect('cp.appearance.themes', [], ['absolute' => TRUE]);
  }

  /**
   * Starts preview mode for a theme.
   *
   * @param string $theme
   *   The theme name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function previewTheme($theme, Request $request): RedirectResponse {
    try {
      $this->previewHandler->startPreviewMode($theme, $this->previewManager->getActiveVsiteId());
    }
    catch (ThemePreviewException $e) {
      $this->messenger()->addError($this->t('Could not start preview. Please check logs for details.'));
      $this->getLogger('cp_appearance')->error($e->getMessage());
    }

    return $this->redirect('<front>', [], ['absolute' => TRUE]);
  }

}
