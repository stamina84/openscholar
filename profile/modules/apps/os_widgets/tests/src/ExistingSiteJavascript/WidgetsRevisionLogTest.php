<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\os_widgets\Plugin\DisplayVariant\PlaceBlockPageVariant;

/**
 * Tests os_widgets module.
 *
 * @group functional-javascript
 * @group widgets
 */
class WidgetsRevisionLogTest extends OsExistingSiteJavascriptTestBase {

  /**
   * List of all available os widgets.
   *
   * @var array
   *   Os Widgets Type.
   */
  protected $widgetTypes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);

    $bundleInfo = $this->container->get('entity_type.bundle.info');

    $entity_type_id = 'block_content';
    $this->widgetTypes = $bundleInfo->getBundleInfo($entity_type_id);
  }

  /**
   * Tests if os_widgets revision log exists.
   */
  public function testWidgetsRevisionLogForm() {
    $ignored_block_list = PlaceBlockPageVariant::IGNORE_BLOCK_TYPE_LIST;

    $web_assert = $this->assertSession();

    $this->visitViaVsite("?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    // Iterate through all of widgets to check if revision information.
    foreach ($this->widgetTypes as $key => $label) {
      if (!in_array($key, $ignored_block_list)) {
        $class = str_replace('_', '-', $key);
        $page->find('xpath', '//li[contains(@class, "' . $class . '")]/a')->press();
        $web_assert->waitForText('Add new "' . $label['label'] . '" Widget');
        $web_assert->pageTextNotContains('Revision information');
        $web_assert->pageTextNotContains('Revision log message');
        $page->find('xpath', '//button[contains(@class, "ui-dialog-titlebar-close")]')->press();
      }
    }
  }

}
