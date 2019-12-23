<?php

namespace Drupal\cp_appearance\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_appearance\Entity\CustomTheme;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System Config subscriber.
 *
 * This has overridden the system event subscriber `system.config_subscriber`,
 * to make sure routes are not rebuilt when a custom theme is set as default.
 *
 * @see \Drupal\cp_appearance\CpAppearanceServiceProvider
 * @see \Drupal\system\SystemConfigSubscriber
 */
class CpAppearanceSystemConfigSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs the SystemConfigSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(RouteBuilderInterface $router_builder) {
    $this->routerBuilder = $router_builder;
  }

  /**
   * Rebuilds the router when the default or admin theme is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $saved_config = $event->getConfig();
    // Rebuild routes only if the default theme is not a custom theme.
    if (($saved_config->getName() === 'system.theme' && strpos($saved_config->get('default'), CustomTheme::CUSTOM_THEME_ID_PREFIX) === FALSE) &&
      ($event->isChanged('admin') || $event->isChanged('default'))) {
      $this->routerBuilder->setRebuildNeeded();
    }
  }

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener prevents deleting all configuration. If there is
   * nothing to import then event propagation is stopped because there is no
   * config import to validate.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateNotEmpty(ConfigImporterEvent $event): void {
    $importList = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importList)) {
      $event->getConfigImporter()->logError($this->t('This import is empty and if applied would delete all of your configuration, so has been rejected.'));
      $event->stopPropagation();
    }
  }

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener checks that the system.site:uuid's in the source and
   * target match.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateSiteUuid(ConfigImporterEvent $event): void {
    if (!$event->getConfigImporter()->getStorageComparer()->getSourceStorage()->exists('system.site')) {
      $event->getConfigImporter()->logError($this->t('This import does not contain system.site configuration, so has been rejected.'));
    }
    if (!$event->getConfigImporter()->getStorageComparer()->validateSiteUuid()) {
      $event->getConfigImporter()->logError($this->t('Site UUID in source storage does not match the target storage.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    // The empty check has a high priority so that it can stop propagation if
    // there is no configuration to import.
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidateNotEmpty', 512];
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidateSiteUuid', 256];
    return $events;
  }

}
