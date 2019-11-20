<?php

namespace Drupal\Tests\cp_appearance\ExistingSite;

use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Tests custom_theme_installer service.
 *
 * @coversDefaultClass \Drupal\cp_appearance\CustomThemeInstaller
 */
class CustomThemeInstallerTest extends OsExistingSiteTestBase {

  /**
   * @covers ::makeInstallable
   *
   * @throws \Drupal\cp_appearance\Entity\CustomThemeException
   */
  public function testMakeInstallable(): void {
    /** @var \Drupal\cp_appearance\CustomThemeInstallerInterface $custom_theme_installer */
    $custom_theme_installer = $this->container->get('cp_appearance.custom_theme_installer');
    $custom_theme_installer->makeInstallable();

    $link_target = readlink(DRUPAL_ROOT . '/' . CustomTheme::CUSTOM_THEMES_DRUPAL_LOCATION);
    $this->assertNotFalse($link_target);
  }

}
