<?php

namespace Drupal\Tests\cp_appearance\ExistingSite;

/**
 * Tests whether custom theme cache invalidation is working as expected.
 *
 * @group kernel
 * @group cp-appearance
 */
class CustomThemeCacheTest extends TestBase {

  /**
   * @covers \Drupal\cp_appearance\EventSubscriber\ThemeConfigSubscriber::onSave
   * @covers \Drupal\cp_appearance\EventSubscriber\CpAppearanceSystemConfigSubscriber::onConfigSave
   * @covers \Drupal\cp_appearance\EventSubscriber\CpAppearanceConfigCacheTagSubscriber::onSave
   */
  public function testRenderedCacheTag(): void {
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\Config $mut_theme_config */
    $mut_theme_config = $config_factory->getEditable('system.theme');
    /** @var \Drupal\Core\Cache\CacheTagsChecksumInterface $cache_tags_invalidator_checksum */
    $cache_tags_invalidator_checksum = $this->container->get('cache_tags.invalidator.checksum');

    // Get checksums before making the changes.
    $rendered_checksum_before_custom_theme_enable = $cache_tags_invalidator_checksum->getCurrentChecksum(['rendered']);
    $vsite_rendered_checksum_before_custom_theme_enable = $cache_tags_invalidator_checksum->getCurrentChecksum(["rendered:vsite:{$this->group->id()}"]);

    // Do changes.
    $vsite_context_manager->activateVsite($this->group);
    $mut_theme_config->set('default', TestBase::TEST_CUSTOM_THEME_1_NAME)->save();

    // Tests.
    $rendered_checksum_after_custom_theme_enable = $cache_tags_invalidator_checksum->getCurrentChecksum(['rendered']);
    $vsite_rendered_checksum_after_custom_theme_enable = $cache_tags_invalidator_checksum->getCurrentChecksum(["rendered:vsite:{$this->group->id()}"]);

    $this->assertSame($rendered_checksum_before_custom_theme_enable, $rendered_checksum_after_custom_theme_enable);
    $this->assertGreaterThan($vsite_rendered_checksum_before_custom_theme_enable, $vsite_rendered_checksum_after_custom_theme_enable);
  }

}
