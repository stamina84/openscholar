<?php

namespace Drupal\vsite_privacy\Plugin\VsitePrivacyLevel;

use Drupal\Component\Plugin\PluginBase;
use Drupal\vsite_privacy\Plugin\VsitePrivacyLevelInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Vsite privacy level.
 *
 * @VsitePrivacyLevel(
 *   title = @Translation("Public on the Web."),
 *   id = "public",
 *   description = @Translation("Anyone on the Internet can view your site. Your site will show in search results. No sign-in required."),
 *   weight = -1000
 * )
 */
class VsitePrivacyLevelPublic extends PluginBase implements VsitePrivacyLevelInterface {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(AccountInterface $account): bool {
    return TRUE;
  }

}
