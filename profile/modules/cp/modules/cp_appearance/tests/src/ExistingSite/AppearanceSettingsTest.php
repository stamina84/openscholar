<?php

namespace Drupal\Tests\cp_appearance\ExistingSite;

/**
 * AppearanceSettingsTest.
 *
 * @group functional
 * @group cp-appearance
 * @coversDefaultClass \Drupal\cp_appearance\Controller\CpAppearanceMainController
 */
class AppearanceSettingsTest extends TestBase {

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

    $this->group = $this->createGroup([
      'path' => [
        'alias' => '/cp-appearance',
      ],
    ]);
    $this->addGroupAdmin($this->groupAdmin, $this->group);

    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * Tests appearance change.
   *
   * @covers ::main
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSave(): void {
    $this->visit('/cp-appearance/cp/appearance/themes');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Select Theme');

    $this->getCurrentPage()->selectFieldOption('theme', 'hwpi_lamont');
    $this->getCurrentPage()->pressButton('Save Theme');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    $this->assertEquals('hwpi_lamont', $config_factory->get('system.theme')->get('default'));

    $this->visit('/');
  }

  /**
   * @covers ::setTheme
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSetDefault(): void {
    $this->visit('/cp-appearance/cp/appearance/themes/set/hwpi_college');

    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $vsite_context_manager->activateVsite($this->group);
    $this->assertEquals('hwpi_college', $config_factory->get('system.theme')->get('default'));

    $this->visit('/');
  }

  /**
   * @covers ::previewTheme
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testStartPreview(): void {
    $this->visit('/cp-appearance/cp/appearance/themes/preview/vibrant');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Previewing: Vibrant');

    $this->visit('/cp-appearance/cp/appearance/themes/preview/hwpi_sterling');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Previewing: Sterling');

    $this->visit('/');
  }

}
