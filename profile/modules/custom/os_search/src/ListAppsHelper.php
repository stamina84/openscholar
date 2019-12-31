<?php

namespace Drupal\os_search;

use Drupal\os_app_access\AppLoader;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service class that return mapping of app title and bundle.
 */
class ListAppsHelper {

  /**
   * App Loader service.
   *
   * @var \Drupal\os_app_access\AppLoader
   */
  protected $appLoader;

  /**
   * App Loader service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ListAppsHelper constructor.
   *
   * @param \Drupal\os_app_access\AppLoader $app_loader
   *   App loader service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user account.
   */
  public function __construct(AppLoader $app_loader, AccountProxyInterface $account) {
    $this->appLoader = $app_loader;
    $this->currentUser = $account;
  }

  /**
   * Returns a bundle-label mapping expected by OS Search.
   *
   * @return array
   *   Array of apps.
   */
  public function getAppLists() {
    $apps = $this->appLoader->getAppsForUser($this->currentUser);
    $lists = [];
    foreach ($apps as $app) {
      if ($app['entityType'] === 'media') {
        continue;
      }
      $title = (string) $app['title'];
      if ($app['entityType'] == 'bibcite_reference') {
        $lists['bibcite_reference'] = $title;
      }
      else {
        foreach ($app['bundle'] as $bundle) {
          $lists[$bundle] = $title;
        }
      }
    }
    return $lists;
  }

}
