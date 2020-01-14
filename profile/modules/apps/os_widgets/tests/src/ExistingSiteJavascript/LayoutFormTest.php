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
    $this->assertSession()->pageTextContains($block_info_1);
    $this->assertSession()->pageTextContains($block_info_2);

    $this->getSession()->getPage()->fillField('filter-widgets', $block_info_1);
    $this->getSession()->executeScript('document.querySelector("#block-place-widget-selector-wrapper").scrollTo(5, 5);');
    $this->assertTrue($this->getSession()->getPage()->find('xpath', "//h3[contains(.,\"{$block_info_1}\")]")->isVisible());
    $this->assertNotTrue($this->getSession()->getPage()->find('xpath', "//h3[contains(.,\"{$block_info_2}\")]")->isVisible());
  }

}
