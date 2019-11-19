<?php

namespace Drupal\cp_appearance;

/**
 * Custom theme installer.
 */
interface CustomThemeInstallerInterface {

  /**
   * Makes sure custom themes are available for installation.
   *
   * @throws \Drupal\cp_appearance\Entity\CustomThemeException
   */
  public function makeInstallable(): void;

}
