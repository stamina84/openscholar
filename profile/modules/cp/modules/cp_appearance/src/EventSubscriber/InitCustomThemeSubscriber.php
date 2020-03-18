<?php

namespace Drupal\cp_appearance\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\cp_appearance\Entity\CustomTheme;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\os_theme_preview\Handler;

/**
 * Initializes custom theme as active theme for a vsite.
 */
class InitCustomThemeSubscriber implements EventSubscriberInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Creates a new InitCustomThemeSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   Theme initialization service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Initializes a custom theme if it is not loaded at runtime.
   *
   * Kernel request event subscriber is used because I found this is the only
   * low level hook which loads the vsite config settings.
   * hook_rebuild() was unable to load the vsite settings.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   *
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   *
   * @see \Drupal\cp\Theme\CpThemeNegotiator::determineActiveTheme()
   * @see \Drupal\Core\Theme\ThemeManager::initTheme()
   */
  public function initCustomTheme(GetResponseEvent $event): void {
    /** @var \Drupal\Core\Config\ImmutableConfig $system_theme_config */
    $system_theme_config = $this->configFactory->get('system.theme');
    $default_theme = $system_theme_config->get('default');
    $custom_theme = CustomTheme::load($default_theme);
    /** @var \Drupal\Core\Theme\ActiveTheme $current_active_theme */
    $current_active_theme = $this->themeManager->getActiveTheme();
    /** @var string $current_active_theme_name */
    $current_active_theme_name = $current_active_theme->getName();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface|null $session */
    $session = $this->request->getSession();
    /** @var \Drupal\os_theme_preview\ThemePreview|null $current_preview_theme */
    $current_preview_theme = $session->get(Handler::SESSION_KEY);

    // Only activate the theme if it is certain that this is a custom theme we
    // are dealing with, whose runtime info is missing.
    if ($custom_theme &&
      $current_active_theme_name !== 'os_admin' &&
      $current_active_theme_name !== $default_theme &&
      !$current_preview_theme) {
      $vsite_active_theme = $this->themeInitialization->getActiveThemeByName($default_theme);
      $this->themeManager->setActiveTheme($vsite_active_theme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['initCustomTheme'];
    return $events;
  }

}
