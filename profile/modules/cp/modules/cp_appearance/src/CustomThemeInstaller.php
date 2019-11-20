<?php

namespace Drupal\cp_appearance;

use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\cp_appearance\Entity\CustomThemeException;

/**
 * Custom theme installer.
 */
final class CustomThemeInstaller implements CustomThemeInstallerInterface {

  /**
   * {@inheritdoc}
   */
  public function makeInstallable(): void {
    $absolute_installable_path = DRUPAL_ROOT . '/' . CustomTheme::CUSTOM_THEMES_DRUPAL_LOCATION;
    // Warning is intentionally suppressed, as it is known that the symlink will
    // not exist during initiation.
    $link_target = @readlink($absolute_installable_path);

    if (!$link_target) {
      $status = symlink(CustomTheme::ABSOLUTE_CUSTOM_THEMES_LOCATION, $absolute_installable_path);

      if (!$status) {
        throw new CustomThemeException('Unable to make the custom theme installable. Please contact the site administrator for support.');
      }
    }
  }

}
