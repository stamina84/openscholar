<?php

namespace Drupal\cp_appearance;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\cp_appearance\EventSubscriber\CpAppearanceConfigCacheTagSubscriber;
use Drupal\cp_appearance\EventSubscriber\CpAppearanceSystemConfigSubscriber;

/**
 * Modifies core event subscribers.
 *
 * This prevents unnecessary system resets when a custom theme would be
 * installed or set as default.
 */
class CpAppearanceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /** @var \Symfony\Component\DependencyInjection\Definition $system_config_subscriber_definition */
    $system_config_subscriber_definition = $container->getDefinition('system.config_subscriber');
    /** @var \Symfony\Component\DependencyInjection\Definition $system_config_cache_tag_definition */
    $system_config_cache_tag_definition = $container->getDefinition('system.config_cache_tag');

    $system_config_cache_tag_definition->setClass(CpAppearanceConfigCacheTagSubscriber::class);
    $system_config_subscriber_definition->setClass(CpAppearanceSystemConfigSubscriber::class);
  }

}
