<?php

namespace Drupal\os_app_access;

use Drupal\user\UserInterface;

/**
 * Interface for loading allowed apps for a user.
 */
interface AppLoaderInterface {

  /**
   * Returns apps allowed for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   Current user.
   *
   * @return array
   *   App definitions.
   */
  public function getAppsForUser(UserInterface $user): array;

}
