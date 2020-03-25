<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class OsDataverseSettingsFormTest.
 *
 * @group cp
 * @group functional
 */
class OsDataverseSettingsFormTest extends OsExistingSiteJavascriptTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupAdmin;

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * Support User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $supportAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->adminUser = $this->createUser([], '', TRUE);
    // Create support user.
    $this->supportAdmin = $this->createUser([
      'support vsite',
    ]);
    $this->group->addMember($this->supportAdmin);
  }

  /**
   * Test group admin settings form.
   */
  public function testOsDataverseSettingsFormSave() {
    $web_assert = $this->assertSession();
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/global-settings/dataverse_urls', $this->group);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    $this->drupalLogin($this->supportAdmin);
    $this->visitViaVsite('cp/settings/global-settings/dataverse_urls', $this->group);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    $this->drupalLoginWithID(1);
    $this->visitViaVsite('cp/settings/global-settings/dataverse_urls', $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();

    $this->drupalLogin($this->adminUser);
    $this->visitViaVsite('cp/settings/global-settings/dataverse_urls', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    // Assert default values.
    $web_assert->fieldExists('base_url');
    $web_assert->fieldValueEquals('base_url', 'https://dataverse.harvard.edu/');
    $web_assert->fieldExists('listing_base_url');
    $web_assert->fieldValueEquals('listing_base_url', 'https://dataverse.harvard.edu/dataverse/');
    $web_assert->fieldExists('search_base_url');
    $web_assert->fieldValueEquals('search_base_url', 'https://dataverse.harvard.edu/dataverse.xhtml');

    $page = $this->getCurrentPage();
    $new_url_base_url = 'http://' . $this->randomMachineName() . '.com/';
    $page->findField('base_url')->setValue($new_url_base_url);
    $new_url_listing_base_url = 'http://' . $this->randomMachineName() . '.com/';
    $page->findField('listing_base_url')->setValue($new_url_listing_base_url);
    $new_url_search_base_url = 'http://' . $this->randomMachineName() . '.com/';
    $page->findField('search_base_url')->setValue($new_url_search_base_url);

    $page->pressButton('Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $web_assert->fieldValueEquals('base_url', $new_url_base_url);
    $web_assert->fieldValueEquals('listing_base_url', $new_url_listing_base_url);
    $web_assert->fieldValueEquals('search_base_url', $new_url_search_base_url);
  }

}
