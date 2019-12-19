<?php

namespace Drupal\cp_appearance\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\cp_appearance\Entity\CustomTheme;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when system config objects are saved.
 *
 * This has overridden the system event subscriber `system.config_cache_tag`, to
 * make sure default cache tags are not invalidated when a custom theme is set
 * as default.
 *
 * @see \Drupal\cp_appearance\CpAppearanceServiceProvider
 * @see \Drupal\system\EventSubscriber\ConfigCacheTag
 */
class CpAppearanceConfigCacheTagSubscriber implements EventSubscriberInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a ConfigCacheTag object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->themeHandler = $theme_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidate cache tags when particular system config objects are saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event): void {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $event->getConfig();
    /** @var string $config_name */
    $config_name = $config->getName();
    // Changing the site settings may mean a different route is selected for the
    // front page. Additionally a change to the site name or similar must
    // invalidate the render cache since this could be used anywhere.
    if ($config_name === 'system.site') {
      $this->cacheTagsInvalidator->invalidateTags(['route_match', 'rendered']);
    }

    // Theme configuration and global theme settings.
    if (\in_array($config_name, ['system.theme', 'system.theme.global'], TRUE) &&
      strpos($config->get('default'), CustomTheme::CUSTOM_THEME_ID_PREFIX) === FALSE) {
      $this->cacheTagsInvalidator->invalidateTags(['rendered']);
    }

    // Theme-specific settings, check if this matches a theme settings
    // configuration object (THEME_NAME.settings), in that case, clear the
    // rendered cache tag.
    if (preg_match('/^([^\.]*)\.settings$/', $config_name, $matches) && $this->themeHandler->themeExists($matches[1])) {
      $this->cacheTagsInvalidator->invalidateTags(['rendered']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
