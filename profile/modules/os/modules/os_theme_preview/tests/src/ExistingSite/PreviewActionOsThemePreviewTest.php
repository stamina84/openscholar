<?php

namespace Drupal\Tests\os_theme_preview\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\os_theme_preview\Traits\ThemePreviewTestTrait;

/**
 * Tests preview action form.
 *
 * @group functional
 * @group os-theme-preview
 * @coversDefaultClass \Drupal\os_theme_preview\Form\PreviewAction
 */
class PreviewActionOsThemePreviewTest extends OsExistingSiteTestBase {

  use ThemePreviewTestTrait;

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Theme configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $themeConfig;

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->configFactory = $this->container->get('config.factory');
    $this->themeConfig = $this->configFactory->get('system.theme');
    $this->group = $this->createGroup([
      'path' => [
        'alias' => '/os-theme-preview',
      ],
    ]);
    $this->addGroupAdmin($this->groupAdmin, $this->group);

    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * Test form visibility.
   *
   * @covers ::buildForm
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testVisibility(): void {
    $this->visit('/os-theme-preview/cp/appearance/themes/preview/documental');

    $this->assertSession()->pageTextContains('Previewing: Documental');
  }

  /**
   * Test save action.
   *
   * @covers ::buildForm
   * @covers ::submitForm
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSave(): void {
    $this->visit('/os-theme-preview/cp/appearance/themes/preview/documental');
    $this->getCurrentPage()->pressButton('Save');

    $this->visit('/os-theme-preview');
    $this->assertSession()->responseContains('/profiles/contrib/openscholar/themes/documental/css/style.css');

    // This is part of the cleanup.
    // If this is not done, then it leads to deadlock errors in Travis
    // https://travis-ci.org/openscholar/openscholar/jobs/540605242.
    // My understanding, big_pipe initiates some sort of request in background,
    // which puts a lock in the database. That lock hinders the test cleanup.
    // Putting this to sleep for arbitrary amount of time seems to fix
    // the problem.
    \sleep(5);
    $this->visit('/');
  }

  /**
   * Test cancel action.
   *
   * @covers ::buildForm
   * @covers ::cancelPreview
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testCancel(): void {
    $this->visit('/os-theme-preview/cp/appearance/themes/preview/documental');
    $this->getCurrentPage()->pressButton('Cancel');

    /** @var \Drupal\Core\Config\ImmutableConfig $actual_theme_config */
    $actual_theme_config = $this->configFactory->get('system.theme');

    $this->assertSame($this->themeConfig->get('default'), $actual_theme_config->get('default'));
  }

  /**
   * Tests back button.
   *
   * @covers ::buildForm
   * @covers ::cancelPreview
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testBack(): void {
    $this->visit('/os-theme-preview/cp/appearance/themes/preview/documental');
    $this->getCurrentPage()->pressButton('Back to themes');

    /** @var \Drupal\Core\Config\ImmutableConfig $actual_theme_config */
    $actual_theme_config = $this->configFactory->get('system.theme');

    $this->assertSame($this->themeConfig->get('default'), $actual_theme_config->get('default'));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    /** @var \Drupal\Core\Config\Config $theme_config_mut */
    $theme_config_mut = $this->configFactory->getEditable('system.theme');
    $theme_config_mut->set('default', $this->themeConfig->get('default'))->save();
    parent::tearDown();
  }

}
