<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class LayoutFormTests.
 *
 * @group functional-javascript
 * @group widgets
 */
class LayoutFormTest extends OsExistingSiteJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests that certain values are different between.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testSiteIndependence(): void {
    $group2 = $this->createGroup();
    $group2Alias = $group2->get('path')->first()->getValue()['alias'];
    $group_admin_2 = $this->createUser();
    $this->addGroupAdmin($group_admin_2, $group2);
    // Event ajaxSend is global, and fires on every ajax request.
    $script = <<<JS
    jQuery(document).bind("ajaxSend", function(e, xhr, ajaxOptions) {
      window.phpunit__ajax_url = ajaxOptions.url;
    });
JS;

    $this->visitViaVsite('blog', $this->group);
    $this->getSession()->getPage()->clickLink('Layout');
    $this->assertSession()->waitForButton('Create New Widget');
    $this->getSession()->wait(5);
    $this->getSession()->executeScript($script);
    $this->getSession()->getPage()->pressButton('Save');

    $url = $this->getSession()->evaluateScript('window.phpunit__ajax_url');
    $this->assertContains($this->groupAlias . '/cp/layout/save', $url);

    $this->drupalLogin($group_admin_2);
    $this->visitViaVsite('blog', $group2);
    $this->getSession()->getPage()->clickLink('Layout');
    $this->getSession()->executeScript($script);
    $this->getSession()->getPage()->pressButton('Save');
    $url = $this->getSession()->evaluateScript('window.phpunit__ajax_url');
    $this->assertContains($group2Alias . '/cp/layout/save', $url);
  }

  /**
   * Tests that the filter widget functionality works.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterWidget(): void {
    $block_info_1 = $this->randomMachineName();
    $block_info_2 = $this->randomMachineName();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
    $block_storage = $entity_type_manager->getStorage('block');

    $blocks[] = $this->createBlockContent([
      'info' => $block_info_1,
    ]);

    $blocks[] = $this->createBlockContent([
      'info' => $block_info_2,
      'type' => 'list_of_posts',
    ]);

    foreach ($blocks as $b) {
      $plugin_id = 'block_content:' . $b->uuid();
      $block_id = 'block_content|' . $b->uuid();
      $block = $block_storage->create(['plugin' => $plugin_id, 'id' => $block_id]);
      $block->save();

      $this->group->addContent($b, 'group_entity:block_content');
    }

    $this->visitViaVsite('blog', $this->group);
    $this->getSession()->getDriver()->click('//a[contains(.,"Layout")]');

    $this->assertSession()->pageTextContains('Filter Widgets by Title');
    $this->assertSession()->pageTextContains('Filter Widgets by Type');
    $this->assertSession()->pageTextContains($block_info_1);
    $this->assertSession()->pageTextContains($block_info_2);

    $page = $this->getSession()->getPage();

    // Assert the Filter by title is working.
    $page->fillField('filter-widgets', $block_info_1);
    $this->getSession()->executeScript('document.querySelector("#block-place-widget-selector-wrapper").scrollTo(5, 5);');
    $this->assertTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_1}\")]")->isVisible());
    $this->assertNotTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_2}\")]")->isVisible());

    // Assert the Filter by type is working.
    $page->fillField('filter-widgets', '');
    $page->selectFieldOption('filter-widgets-by-type', 'list_of_posts');
    $this->assertTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_2}\")]")->isVisible());
    $this->assertNotTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_1}\")]")->isVisible());

    // Assert the Filter by type is working.
    $page->selectFieldOption('filter-widgets-by-type', 'basic');
    $this->assertTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_1}\")]")->isVisible());
    $this->assertNotTrue($page->find('xpath', "//h3[contains(.,\"{$block_info_2}\")]")->isVisible());
  }

  /**
   * Tests whether widget placement by weight is working.
   *
   * @covers \Drupal\os_widgets\Entity\LayoutContext::sortWidgets
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testWidgetPlacementByWeight(): void {
    // Setup data required by the test.
    $region = 'content';
    $context = 'all_pages';
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $widget_selector = '.region-content section.block-block-content';
    $block_title_selector = '.block-title';

    // Content setup.
    $widget1 = $this->createBlockContent([
      'info' => 'Apple Widget',
      'field_widget_title' => [
        'value' => 'Apple Widget',
      ],
    ]);
    $this->group->addContent($widget1, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget1, $region, $context, 1);
    $new_widget1_weight = 2;

    $widget2 = $this->createBlockContent([
      'info' => 'The Doors Widget',
      'field_widget_title' => [
        'value' => 'The Doors Widget',
      ],
    ]);
    $this->group->addContent($widget2, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget2, $region, $context, 2);
    $new_widget2_weight = 3;

    $widget3 = $this->createBlockContent([
      'info' => 'Zebra Widget',
      'field_widget_title' => [
        'value' => 'Zebra Widget',
      ],
    ]);
    $this->group->addContent($widget3, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget3, $region, $context, 3);
    $new_widget3_weight = 1;

    // Assert widget placement when weight explicitly not changed.
    // This replicates the behavior when a widget is dragged and drop in the
    // widget placeholders - without reordering it.
    $this->visitViaVsite('', $this->group);
    /** @var \Behat\Mink\Element\Element[] $widgets */
    $widgets = $this->getSession()->getPage()->findAll('css', $widget_selector);
    $this->assertEqual($widgets[0]->find('css', $block_title_selector)->getHtml(), 'Apple Widget');
    $this->assertEqual($widgets[1]->find('css', $block_title_selector)->getHtml(), 'The Doors Widget');
    $this->assertEqual($widgets[2]->find('css', $block_title_selector)->getHtml(), 'Zebra Widget');

    // Change weights.
    $mut_context_config = $config_factory->getEditable('os_widgets.layout_context.' . $context);
    $widget_placement_config = $mut_context_config->get('data');
    array_walk($widget_placement_config, static function (&$item) use ($widget1, $widget2, $widget3, $new_widget1_weight, $new_widget2_weight, $new_widget3_weight) {
      if ($item['id'] === "block_content|{$widget1->uuid()}") {
        $item['weight'] = $new_widget1_weight;
      }

      if ($item['id'] === "block_content|{$widget2->uuid()}") {
        $item['weight'] = $new_widget2_weight;
      }

      if ($item['id'] === "block_content|{$widget3->uuid()}") {
        $item['weight'] = $new_widget3_weight;
      }
    });
    $mut_context_config->set('data', $widget_placement_config);
    $mut_context_config->save();

    // Assert widget placement after weights are altered.
    $this->visitViaVsite('', $this->group);
    /** @var \Behat\Mink\Element\Element[] $widgets */
    $widgets = $this->getSession()->getPage()->findAll('css', $widget_selector);
    $this->assertEqual($widgets[0]->find('css', $block_title_selector)->getHtml(), 'Zebra Widget');
    $this->assertEqual($widgets[1]->find('css', $block_title_selector)->getHtml(), 'Apple Widget');
    $this->assertEqual($widgets[2]->find('css', $block_title_selector)->getHtml(), 'The Doors Widget');
  }

  /**
   * Tests widget's contextual delete links.
   */
  public function testDeleteContextualRedirect() {
    $web_assert = $this->assertSession();
    $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 1',
      ],
      'body' => [
        'Lorem Ipsum content 1',
      ],
      'field_widget_title' => ['Test 1'],
    ]);
    $this->group->addContent($block1, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block1, 'content');

    $this->visitViaVsite("blog", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Lorem Ipsum content 1');
    $web_assert->waitForElement('css', '.contextual-links .block-contentblock-delete a');
    $delete_link = $this->getSession()->getPage()->find('css', '.contextual-links .block-contentblock-delete a');
    $this->assertNotNull($delete_link);
    $this->assertEquals("{$this->groupAlias}/blog", $this->getDestinationParameterValue($delete_link));
  }

  /**
   * Test moved widgets on different pages.
   */
  public function testMovedWidgetsOnPages() {
    $web_assert = $this->assertSession();
    $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'All pages widget',
      ],
      'body' => [
        'Lorem Ipsum content 1',
      ],
      'field_widget_title' => ['All pages widget'],
    ]);
    $this->group->addContent($block1, 'group_entity:block_content');

    // Adding widget to content region on all_pages.
    $this->visitViaVsite('', $this->group);
    $this->getSession()->getDriver()->click('//a[contains(.,"Layout")]');
    $web_assert->pageTextContains('Filter Widgets by Title');
    $web_assert->pageTextContains('All pages widget');
    $page = $this->getSession()->getPage();
    $page->fillField('filter-widgets', 'All pages widget');
    $this->getSession()->executeScript('document.querySelector("#block-place-widget-selector-wrapper").scrollTo(5, 5);');
    $link = $page->find('css', '#block-list .block-active');
    // Drag widget to content region for all_pages.
    $link->dragTo($page->find('css', '.region-content'));
    $page->pressButton('Save');
    $this->getSession()->wait(5);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('All pages widget');

    // Removing block1 widget from News page.
    $this->visitViaVsite('news', $this->group);
    $web_assert->pageTextContains('All pages widget');
    $this->getSession()->getDriver()->click('//a[contains(.,"Layout")]');
    $block1_uuid = '.block-block-content' . $block1->uuid();
    $link = $page->find('css', $block1_uuid);
    // Drag widget from page to blocks list.
    $link->dragTo($page->find('css', '#block-list'));
    $page->pressButton('Save');
    $this->getSession()->wait(10);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextNotContains('All pages widget');
    // Removed widget from news page, checking other pages if widget is removed.
    $this->visitViaVsite('blog', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('All pages widget');
  }

}
