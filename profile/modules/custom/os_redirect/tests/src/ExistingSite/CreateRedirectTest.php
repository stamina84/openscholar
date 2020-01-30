<?php

namespace Drupal\Tests\os_redirect\ExistingSite;

/**
 * Tests os_redirect module.
 *
 * @group redirect
 * @group functional
 *
 * @coversDefaultClass \Drupal\os_redirect\Form\OsRedirectForm
 */
class CreateRedirectTest extends OsRedirectTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $site_user = $this->createUser();
    $this->addGroupAdmin($site_user, $this->group);
    $this->drupalLogin($site_user);
  }

  /**
   * Tests add redirect.
   */
  public function testAddRedirectInVsiteSuccess() {
    $web_assert = $this->assertSession();

    // Set global maximum number.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $this->container->get('config.factory');
    $config = $configFactory->getEditable('os_redirect.settings');
    $config->set('maximum_number', 10);
    $config->save(TRUE);

    $this->visit($this->group->get('path')->getValue()[0]['alias'] . "/cp/redirects/add");
    $web_assert->statusCodeEquals(200);

    $add_values = [
      'redirect_source[0][path]' => 'lorem1-new',
      'redirect_redirect[0][uri]' => 'http://example.com',
    ];
    $this->drupalPostForm(NULL, $add_values, 'Save');
    $this->assertContains('The redirect has been saved.', $this->getCurrentPageContent());

    // Check new content on list page.
    $this->visit($this->group->get('path')->getValue()[0]['alias'] . "/cp/redirects/list");
    $web_assert->statusCodeEquals(200);
    $this->assertContains('lorem1-new', $this->getCurrentPageContent(), 'Test redirect is source not visible.');

  }

  /**
   * Tests add redirect.
   */
  public function testAddRedirectGlobal() {
    $web_assert = $this->assertSession();

    // Normal site user should not access global page.
    $this->visit("/cp/redirects/add");
    $web_assert->statusCodeEquals(403);
  }

  /**
   * Test add redirect with limit.
   */
  public function testAddRedirectInVsiteLimit() {
    $web_assert = $this->assertSession();

    // Set global maximum number.
    $configFactory = $this->container->get('config.factory');
    $config = $configFactory->getEditable('os_redirect.settings');
    $config->set('maximum_number', 0);
    $config->save(TRUE);

    $this->visit($this->group->get('path')->getValue()[0]['alias'] . "/cp/redirects/add");
    $web_assert->statusCodeEquals(200);

    $add_values = [
      'redirect_source[0][path]' => $this->randomMachineName(),
      'redirect_redirect[0][uri]' => 'http://example.com',
    ];
    $this->drupalPostForm(NULL, $add_values, 'Save');
    $web_assert->statusCodeEquals(200);
    $this->assertContains('Maximum number of redirects', $this->getCurrentPageContent());
    $this->assertNotContains('The redirect has been saved.', $this->getCurrentPageContent());

  }

}
