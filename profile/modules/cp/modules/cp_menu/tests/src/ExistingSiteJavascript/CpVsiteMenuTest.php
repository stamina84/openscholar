<?php

namespace Drupal\Tests\cp_menu\ExistingSiteJavaScript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class CpVsiteMenuTest.
 *
 * @group functional-javascript
 * @group cp-menu
 *
 * @package Drupal\Tests\cp_menu\ExistingSiteJavaScript
 */
class CpVsiteMenuTest extends OsExistingSiteJavascriptTestBase {
  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group Id.
   *
   * @var string
   */
  protected $id;

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * Request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Group alias.
   *
   * @var string
   */
  protected $groupAlias;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->request = $this->container->get('request_stack')->getCurrentRequest();

    $this->groupAlias = '/' . $this->randomMachineName();

    $this->group = $this->createGroup([
      'path' => [
        'alias' => $this->groupAlias,
      ],
    ]);
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->id = $this->group->id();
    // Test as groupAdmin.
    $this->drupalLogin($this->groupAdmin);
  }

  /**
   * Tests Vsite link creation.
   */
  public function test(): void {

    $host = $this->request->getSchemeAndHttpHost();

    $this->visit($this->groupAlias . '/cp/build/menu');
    $session = $this->assertSession();
    $page = $this->getCurrentPage();

    $blog1 = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog1, $this->group);
    $url = $blog1->toUrl()->toString();

    // Test adding absolute link.
    $link = $page->find('css', '#add_new_link');
    $link->click();
    $session->waitForElementVisible('css', '.cp-menu-link-add-form');
    $edit = [
      'link_type' => 'url',
    ];
    $this->submitForm($edit, 'Continue');
    $session->assertWaitOnAjaxRequest();
    $edit = [
      'title' => $blog1->getTitle(),
      'url' => $host . $this->groupAlias . $url,
    ];
    $this->submitForm($edit, 'Finish');

    $session->assertWaitOnAjaxRequest();
    $session->linkExists($blog1->getTitle());
    $session->linkByHrefExists($host . $this->group->get('path')->getValue()[0]['alias'] . $url);
    // Test adding internal link.
    $blog2 = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog2, $this->group);
    $url = $blog2->toUrl()->toString();

    $link = $page->find('css', '#add_new_link');
    $link->click();
    $session->waitForElementVisible('css', '.cp-menu-link-add-form');
    $edit = [
      'link_type' => 'url',
    ];
    $this->submitForm($edit, 'Continue');
    $session->assertWaitOnAjaxRequest();
    $edit = [
      'title' => $blog2->getTitle(),
      'url' => $this->groupAlias . $url,
    ];
    $this->submitForm($edit, 'Finish');

    $session->assertWaitOnAjaxRequest();
    $session->linkExists($blog2->getTitle());
    $session->linkByHrefExists($this->group->get('path')->getValue()[0]['alias'] . $url);

  }

}
