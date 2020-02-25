<?php

namespace Drupal\Tests\cp_appearance\ExistingSiteJavascript;

use Drupal\cp_appearance\Entity\CustomTheme;
use Drupal\cp_appearance\Entity\CustomThemeInterface;

/**
 * Checks custom theme browser cache invalidations.
 *
 * @group functional-javascript
 * @group cp-appearance
 */
class CustomThemeBrowserCacheTest extends CpAppearanceExistingSiteJavascriptTestBase {

  /**
   * Test group admin.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * @covers \Drupal\cp_appearance\CssCollectionRenderer
   * @covers \Drupal\cp_appearance\JsCollectionRenderer
   * @covers \Drupal\vsite\Plugin\VsiteContextManager::vsiteFlushCssJs
   * @covers ::cp_appearance_cp_custom_theme_update
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function test(): void {
    // Setup.
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');

    $this->visitViaVsite('cp/appearance/themes/custom-themes/add', $this->group);
    $this->getSession()->getPage()->fillField('Custom Theme Name', 'The Doors');
    $this->assertSession()->waitForElementVisible('css', '.machine-name-value');
    $this->getSession()->getPage()->selectFieldOption('Parent Theme', 'clean');
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: gray; }');
    $this->getSession()->getPage()->findField('scripts')->setValue("console.log('People Are Strange')");
    $this->getSession()->getPage()->pressButton('Save and set as default theme');
    $this->getSession()->getPage()->pressButton('Confirm');

    $vsite_context_manager->activateVsite($this->group);
    /** @var \Drupal\cp_appearance\Entity\CustomThemeInterface $custom_theme */
    $custom_theme = CustomTheme::load(CustomTheme::CUSTOM_THEME_ID_PREFIX . $this->group->id() . '_' . 'the_doors');
    $this->assertNotNull($custom_theme);

    // Record the original cache busters.
    $this->drupalLogout();
    $this->visitViaVsite('', $this->group);
    $original_style_cache = $this->getCustomThemeStyleCacheBuster($custom_theme);
    $original_script_cache = $this->getCustomThemeScriptCacheBuster($custom_theme);

    // Make modifications.
    $default_css_js_query_string_before_update = $state->get('system.css_js_query_string');
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite("cp/appearance/themes/custom-themes/{$custom_theme->id()}/edit", $this->group);
    $this->getSession()->getPage()->findField('styles')->setValue('body { color: black; font-family: Sans-Serif; };');
    $this->getSession()->getPage()->findField('scripts')->setValue("console.log('Riders On The Storm')");
    $this->getSession()->getPage()->pressButton('Save');
    $default_css_js_query_string_after_update = $state->get('system.css_js_query_string');

    // Test.
    $this->drupalLogout();
    $this->visitViaVsite('', $this->group);
    $updated_style_cache = $this->getCustomThemeStyleCacheBuster($custom_theme);
    $updated_script_cache = $this->getCustomThemeScriptCacheBuster($custom_theme);

    $this->assertNotEquals($original_style_cache, $updated_style_cache);
    $this->assertNotEquals($original_script_cache, $updated_script_cache);

    $vsite_css_js_query_string = $state->get("vsite.css_js_query_string.{$this->group->id()}");
    $this->assertEquals($vsite_css_js_query_string, $updated_style_cache);
    $this->assertEquals($vsite_css_js_query_string, $updated_script_cache);
    $this->assertEquals($default_css_js_query_string_before_update, $default_css_js_query_string_after_update);

    // Cleanup.
    $custom_theme->delete();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Returns the style cache buster for a custom theme.
   *
   * @param \Drupal\cp_appearance\Entity\CustomThemeInterface $custom_theme
   *   The custom theme.
   *
   * @return string
   *   The cache buster.
   */
  protected function getCustomThemeStyleCacheBuster(CustomThemeInterface $custom_theme): string {
    /** @var \Behat\Mink\Element\NodeElement $style_selector */
    $style_selector = $this->getSession()->getPage()->find('css', "[href*=\"/themes/custom_themes/{$custom_theme->id()}/style.css\"]");

    /** @var string $style_path */
    $style_path = $style_selector->getAttribute('href');
    list(, $cache_id) = explode('?', $style_path);

    return $cache_id;
  }

  /**
   * Returns the script cache buster for a custom theme.
   *
   * @param \Drupal\cp_appearance\Entity\CustomThemeInterface $custom_theme
   *   The custom theme.
   *
   * @return string
   *   The cache buster.
   */
  protected function getCustomThemeScriptCacheBuster(CustomThemeInterface $custom_theme): string {
    /** @var \Behat\Mink\Element\NodeElement $script_selector */
    $script_selector = $this->getSession()->getPage()->find('css', "[src*=\"/themes/custom_themes/{$custom_theme->id()}/script.js\"]");

    /** @var string $script_path */
    $script_path = $script_selector->getAttribute('src');
    list(, $cache_id) = explode('?', $script_path);

    return $cache_id;
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->cleanUpProperties(\self::class);
  }

}
