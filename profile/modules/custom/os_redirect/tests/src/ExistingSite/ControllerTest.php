<?php

namespace Drupal\Tests\os_redirect\ExistingSite;

use Drupal\os_redirect\Controller\RedirectListController;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Tests os_redirect module.
 *
 * @group redirect
 * @group functional
 *
 * @coversDefaultClass \Drupal\os_redirect\Controller\RedirectListController
 */
class ControllerTest extends OsExistingSiteTestBase {

  protected $siteUser;
  /**
   * Redirect List Controller.
   *
   * @var \Drupal\os_redirect\Controller\RedirectListController
   */
  protected $controller;
  /**
   * Vsite Context Manager Interface.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->siteUser = $this->createUser();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    // Set the current user so group creation can rely on it.
    $this->container->get('current_user')->setAccount($this->createUser());

    // Enable the user_as_content plugin on the default group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $entity_type_manager->getStorage('group_content_type');
    /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $plugin */
    $plugins = $storage->loadByContentPluginId('group_entity:redirect');
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $plugin */
    $plugin = reset($plugins);

    $redirect = $this->createRedirect([
      'redirect_source' => [
        'path' => 'lorem1',
      ],
      'redirect_redirect' => [
        'uri' => 'http://example.com',
      ],
    ]);
    $this->group->addContent($redirect, $plugin->getContentPluginId());
    $container = \Drupal::getContainer();
    $this->controller = RedirectListController::create($container);
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $this->vsiteContextManager = $container->get('vsite.context_manager');
  }

  /**
   * Tests cp redirects listing.
   */
  public function testCpRedirectListing() {
    $this->drupalLogin($this->siteUser);

    // Check global list visibility.
    $build = $this->controller->listing();
    $this->assertEmpty($build['#rows']);

    $this->vsiteContextManager->activateVsite($this->group);
    $build = $this->controller->listing();

    $this->assertNotEmpty($build['#rows']);
    $this->assertCount(1, $build['#rows']);
    $this->assertSame('lorem1', $build['#rows'][0]['data'][0], 'Test redirect is source not visible.');
    $this->assertSame('http://example.com', $build['#rows'][0]['data'][1], 'Test redirect uri is not visible.');
  }

}
