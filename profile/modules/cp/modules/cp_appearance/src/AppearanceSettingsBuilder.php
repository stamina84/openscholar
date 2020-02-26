<?php

namespace Drupal\cp_appearance;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\cp_appearance\Entity\CustomTheme;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper methods for appearance settings.
 */
final class AppearanceSettingsBuilder implements AppearanceSettingsBuilderInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Theme handler.
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
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * List of installed themes made from os_base.
   *
   * @var \Drupal\Core\Extension\Extension[]|null
   */
  protected $osInstalledThemes;

  /**
   * Theme selector builder service.
   *
   * @var \Drupal\cp_appearance\ThemeSelectorBuilderInterface
   */
  protected $themeSelectorBuilder;

  /**
   * AppearanceBuilder constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder.
   * @param \Drupal\cp_appearance\ThemeSelectorBuilderInterface $theme_selector_builder
   *   Theme selector builder service.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, ThemeSelectorBuilderInterface $theme_selector_builder) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->themeSelectorBuilder = $theme_selector_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('cp_appearance.theme_selector_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFeaturedThemes(): array {
    $themes = $this->osInstalledThemes();
    $featured_themes = [];
    $sub_themes = [];
    foreach ($themes as $index => $theme) {
      $feature_theme_condition = !isset($theme->info['onepage']) && !isset($theme->info['custom theme']);
      if ($feature_theme_condition) {
        $featured_themes[$index] = $theme;
        if (isset($theme->sub_themes)) {
          $sub_themes = $this->getFlavors($themes, $theme->sub_themes);
        }
      }
    }
    $featured_themes = array_merge($featured_themes, $sub_themes);
    $this->prepareThemes($featured_themes);

    return $featured_themes;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnePageThemes(): array {
    $themes = $this->osInstalledThemes();
    $one_page_themes = [];
    $sub_themes = [];
    // Get one page themes.
    foreach ($themes as $index => $theme) {
      $one_page_condition = isset($theme->info['onepage']) && $theme->info['onepage'] == TRUE;
      if ($one_page_condition) {
        $one_page_themes[$index] = $theme;
        if (isset($theme->sub_themes)) {
          $sub_themes = $this->getFlavors($themes, $theme->sub_themes);
        }
      }
    }
    $one_page_themes = array_merge($one_page_themes, $sub_themes);
    $this->prepareThemes($one_page_themes);

    return $one_page_themes;
  }

  /**
   * Retrieve the list of the themes based on the sub theme names.
   *
   * @param \Drupal\Core\Extension\Extension[] $themes
   *   The Themes.
   * @param array $sub_theme_names
   *   Base theme names for any individual theme.
   *
   * @return array|null
   *   The Themes.
   */
  protected function getFlavors(array $themes, array $sub_theme_names): array {
    $sub_themes_list = [];
    foreach ($themes as $index => $theme) {
      if (in_array($theme->info['name'], $sub_theme_names)) {
        $sub_themes_list[$index] = $theme;
      }
    }
    return $sub_themes_list;
  }

  /**
   * {@inheritdoc}
   */
  public function themeIsDefault(Extension $theme): bool {
    /** @var \Drupal\Core\Config\Config $theme_config */
    $theme_config = $this->configFactory->get('system.theme');
    /** @var string $theme_default */
    $theme_default = $theme_config->get('default');

    if ($theme_default === $theme->getName()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adds a screenshot information to the theme.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   The theme.
   *
   * @return array|null
   *   Renderable theme_image structure. NULL if no screenshot found.
   */
  protected function addScreenshotInfo(Extension $theme): ?array {
    /** @var \Drupal\Core\Extension\Extension[] $drupal_installed_themes */
    $drupal_installed_themes = $this->themeHandler->listInfo();
    /** @var \Drupal\Core\Config\Config $theme_config */
    $theme_config = $this->configFactory->get('system.theme');
    /** @var string $theme_default */
    $theme_default = $theme_config->get('default');
    $preview = $theme;

    // Make sure that if a flavor is set as default, then its preview is being
    // showed, not its base theme's.
    if (isset($theme->sub_themes[$theme_default])) {
      $preview = $drupal_installed_themes[$theme_default];
    }

    /** @var string|null $screenshot_uri */
    $screenshot_uri = $this->themeSelectorBuilder->getScreenshotUri($preview);

    if ($screenshot_uri) {
      return [
        'uri' => $screenshot_uri,
        'alt' => $this->t('Screenshot for @theme theme', ['@theme' => $preview->info['name']]),
        'title' => $this->t('Screenshot for @theme theme', ['@theme' => $preview->info['name']]),
        'attributes' => ['class' => ['screenshot']],
      ];
    }

    return NULL;
  }

  /**
   * Adds allowed operations to a theme.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   The theme.
   *
   * @return array
   *   Renderable theme_link structure.
   */
  protected function addOperations(Extension $theme): array {
    $operations = [];

    if (!$theme->is_default) {
      $operations[] = [
        'title' => $this->t('Set as default'),
        'url' => Url::fromRoute('cp_appearance.cp_select_theme', [
          'theme' => $theme->getName(),
        ]),
        'attributes' => [
          'title' => $this->t('Set @theme as your theme', ['@theme' => $theme->info['name']]),
          'class' => [
            'btn',
            'btn-sm',
            'btn-default',
            'set-default',
          ],
        ],
      ];

      $operations[] = [
        'title' => $this->t('Preview'),
        'url' => Url::fromRoute('cp_appearance.preview', [
          'theme' => $theme->getName(),
        ]),
        'attributes' => [
          'title' => $this->t('Preview @theme', ['@theme' => $theme->info['name']]),
          'class' => [
            'btn',
            'btn-sm',
            'btn-default',
            'preview',
            'far',
            'fa-eye',
          ],
        ],
      ];
    }

    return $operations;
  }

  /**
   * Adds additional notes to a theme.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   The theme.
   *
   * @return array
   *   Renderable markup structure.
   */
  protected function addNotes(Extension $theme): array {
    $notes = [];

    if ($theme->is_default) {
      $notes[] = $this->t('current theme');
    }

    return $notes;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomThemes(): array {
    $custom_themes = [];
    $custom_theme_entities = CustomTheme::loadMultiple();
    $themes = $this->themeHandler->listInfo();
    foreach ($themes as $theme) {
      if (isset($custom_theme_entities[$theme->getName()])) {
        $custom_themes[$theme->getName()] = $theme;
      }
    }

    $this->prepareThemes($custom_themes);
    foreach ($custom_themes as &$custom_theme) {
      $operations = [];
      $operations[] = Link::createFromRoute($this->t('Edit'), 'entity.cp_custom_theme.edit_form', [
        'cp_custom_theme' => $custom_theme->getName(),
      ], [
        'attributes' =>
            [
              'class' => [
                'edit-theme',
                'far',
                'fa-edit',
              ],
            ],
      ]
      );
      $operations[] = Link::createFromRoute($this->t('Delete'), 'entity.cp_custom_theme.delete_form', [
        'cp_custom_theme' => $custom_theme->getName(),
      ], [
        'attributes' =>
            [
              'class' => [
                'delete-theme',
                'far',
                'fa-trash-alt',
              ],
            ],
      ]
      );

      $custom_theme->more_operations = $operations;
    }

    return $custom_themes;
  }

  /**
   * Make the themes ready for settings form.
   *
   * @param \Drupal\Core\Extension\Extension[] $themes
   *   The themes.
   */
  protected function prepareThemes(array &$themes): void {
    uasort($themes, 'system_sort_modules_by_info_name');

    // Attach additional information in the themes.
    foreach ($themes as $theme) {
      $theme->is_default = $this->themeIsDefault($theme);
      $theme->is_admin = FALSE;
      $theme->screenshot = $this->addScreenshotInfo($theme);
      $theme->operations = $this->addOperations($theme);
      $theme->notes = $this->addNotes($theme);
    }
  }

  /**
   * List of OS installed themes(not core themes) and flavors.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   The themes.
   */
  protected function osInstalledThemes(): array {
    if (!$this->osInstalledThemes) {
      $this->osInstalledThemes = array_filter($this->themeHandler->listInfo(), function (Extension $theme) {
        return (isset($theme->base_themes) &&  $theme->status && $theme->origin != 'core') && $theme->info['base theme'] != 'bootstrap' &&
          isset($theme->info['vsite_theme']);
      });
    }
    return $this->osInstalledThemes;
  }

}
