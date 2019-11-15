<?php

namespace Drupal\os_app_access;

use Drupal\os_app_access\Access\AppAccess;
use Drupal\user\UserInterface;
use Drupal\vsite\Plugin\AppManangerInterface;

/**
 * Class AppLoader.
 *
 * @package Drupal\os_app_access
 */
class AppLoader implements AppLoaderInterface {

  /**
   * App manager.
   *
   * @var \Drupal\vsite\Plugin\AppManangerInterface
   */
  protected $appManager;

  /**
   * App access.
   *
   * @var \Drupal\os_app_access\Access\AppAccess
   */
  protected $appAccess;

  /**
   * Creates new AppAccessForm object.
   */
  public function __construct(AppManangerInterface $app_manager, AppAccess $app_access) {
    $this->appManager = $app_manager;
    $this->appAccess = $app_access;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppsForUser(UserInterface $account): array {
    $allowed_apps = [];

    /** @var \Drupal\vsite\AppInterface[] $apps */
    $apps = $this->appManager->getDefinitions();

    // Get app plugin definitions.
    foreach ($apps as $app_definition) {
      $access = $this->appAccess->access($account, $app_definition['id']);
      if ($access->isAllowed() || $access->isNeutral()) {
        $allowed_apps[] = $app_definition;
      }
    }
    return $allowed_apps;
  }

}
