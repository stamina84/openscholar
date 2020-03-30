<?php

namespace Drupal\Tests\vsite_privacy\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * VsitePrivacyLevelManagerTest.
 *
 * @coversDefaultClass \Drupal\vsite_privacy\Plugin\VsitePrivacyLevelManager
 *
 * @group kernel
 * @group vsite
 */
class VsitePrivacyLevelManagerTest extends OsExistingSiteTestBase {

  /**
   * @covers ::checkAccessForPlugin
   */
  public function test(): void {
    /** @var \Drupal\vsite_privacy\Plugin\VsitePrivacyLevelManagerInterface $vsite_privacy_level_manager */
    $vsite_privacy_level_manager = $this->container->get('vsite.privacy.manager');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $vsiteContextManager->activateVsite($this->group);
    $vsiteMember = $this->createUser();
    $vsiteMember->addRole('administrator');
    $this->assertTrue($vsite_privacy_level_manager->checkAccessForPlugin($vsiteMember, 'private'));
    $vsiteMember->removeRole('administrator');
    $current_user = $this->container->get('current_user');
    $this->assertTrue($vsite_privacy_level_manager->checkAccessForPlugin($current_user, 'public'));
    $this->assertFalse($vsite_privacy_level_manager->checkAccessForPlugin($current_user, 'private'));
  }

}
