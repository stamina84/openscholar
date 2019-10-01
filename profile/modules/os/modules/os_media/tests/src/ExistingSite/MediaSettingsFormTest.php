<?php

namespace Drupal\Tests\os_media\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * MediaSettingsFormTest.
 *
 * @group functional
 * @group other
 */
class MediaSettingsFormTest extends ExistingSiteBase {

  /**
   * Administrator user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  protected $defaultUrl;
  protected $defaultKey;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->admin = $this->createUser([], [], TRUE);
    $this->drupalLogin($this->admin);
    $this->configFactory = $this->container->get('config.factory');
    $this->defaultUrl = $this->configFactory->get('os_media.settings')->get('embedly_url');
    $this->defaultKey = $this->configFactory->get('os_media.settings')->get('embedly_key');
  }

  /**
   * Tests Embedly Api config form.
   */
  public function testMediaSettingsForm(): void {
    $this->visit('/admin/media/embedly/settings');
    $this->assertSession()->statusCodeEquals(200);
    // Test negative case.
    $edit = [
      'embedly_url' => $this->randomString(),
      'embedly_key' => $this->randomString(),
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->assertSession()->elementExists('css', '.form-item--error-message');
    $this->assertSession()->pageTextContains('Please enter a valid url');

    // Test positive case.
    $edit = [
      'embedly_url' => 'https://api.embedly.com/1/oembed',
      'embedly_key' => $this->randomString(),
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    /** @var \Drupal\Core\Config\Config $embedly_config_mut */
    $embedly_config_mut = $this->configFactory->getEditable('os_media.settings');
    $embedly_config_mut->set('embedly_url', $this->defaultUrl)->save();
    $embedly_config_mut->set('embedly_key', $this->defaultKey)->save();
  }

}
