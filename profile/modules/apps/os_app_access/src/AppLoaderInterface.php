<?php

namespace Drupal\os_app_access;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for loading allowed apps for a user.
 */
interface AppLoaderInterface {

  /**
   * Returns apps allowed for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   A user account.
   *
   * @return array
   *   App definitions.
   */
  public function getAppsForUser(AccountInterface $user): array;

}
