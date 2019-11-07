<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class AddSlideshowAccessCheckTest.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Access\AddSlideshowAccessCheck
 */
class AddSlideshowAccessCheckTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test the add slideshow route has this access.
   */
  public function testRouteHasProperAccessCheck() {
    $route = $this->container->get('router.route_provider')->getRouteByName('os_widgets.add_slideshow');
    $access_checks = $route->getOption('_access_checks');
    $this->assertContains('os.add_slideshow_access', $access_checks);
  }

  /**
   * Test add slideshow checker with block with group.
   */
  public function testAddSlideshowBlockContent() {
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $account = $this->createUser();
    /** @var \Drupal\os_widgets\Access\AddSlideshowAccessCheck $add_slideshow_access_checker */
    $add_slideshow_access_checker = $this->container->get('os.add_slideshow_access');
    $access_result = $add_slideshow_access_checker->access($account, $block_content);
    $this->assertTrue($access_result->isAllowed());
  }

  /**
   * Test add slideshow checker with block with different group.
   */
  public function testAddSlideshowBlockContentDifferentVsite() {
    $group = $this->createGroup();
    $this->container->get('vsite.context_manager')->activateVsite($group);
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $account = $this->createUser();
    /** @var \Drupal\os_widgets\Access\AddSlideshowAccessCheck $add_slideshow_access_checker */
    $add_slideshow_access_checker = $this->container->get('os.add_slideshow_access');
    $access_result = $add_slideshow_access_checker->access($account, $block_content);
    $this->assertTrue($access_result->isForbidden());
  }

  /**
   * Test add slideshow checker with block without group.
   */
  public function testAddSlideshowBlockContentNoGroup() {
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $account = $this->createUser();
    /** @var \Drupal\os_widgets\Access\AddSlideshowAccessCheck $add_slideshow_access_checker */
    $add_slideshow_access_checker = $this->container->get('os.add_slideshow_access');
    $access_result = $add_slideshow_access_checker->access($account, $block_content);
    $this->assertTrue($access_result->isForbidden());
  }

  /**
   * Test add slideshow checker with not-slideshow block.
   */
  public function testAddNotSlideshowBlockContent() {
    $block_content = $this->createBlockContent([
      'type' => 'not_slideshow',
    ]);
    $account = $this->createUser();
    /** @var \Drupal\os_widgets\Access\AddSlideshowAccessCheck $add_slideshow_access_checker */
    $add_slideshow_access_checker = $this->container->get('os.add_slideshow_access');
    $access_result = $add_slideshow_access_checker->access($account, $block_content);
    $this->assertTrue($access_result->isForbidden());
  }

}
