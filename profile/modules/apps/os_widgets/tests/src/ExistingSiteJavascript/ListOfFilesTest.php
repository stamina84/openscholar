<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests List Of Files widget various form functions and widget creation.
 *
 * @group functional-javascript
 * @group widgets
 */
class ListOfFilesTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User with required permissions.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $user;

  /**
   * Vsite context manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteContextManager;

  /**
   * Widget name.
   *
   * @var string
   */
  protected $widgetName;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->user = $this->createUser(['administer blocks']);
    $this->addGroupAdmin($this->user, $this->group);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests List of Files form.
   *
   * @covers ::os_widgets_form_alter
   */
  public function testListOfFilesForm() {
    $web_assert = $this->assertSession();

    $this->visitViaVsite("block/add/list_of_files", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();

    // Check when Image/Embeds is selected then layout field is visible.
    $layout_field = $page->findField('field_layout');
    $field_file_type = $page->findField('field_file_type');
    $this->assertFalse($layout_field->isVisible());
    $field_file_type->selectOption('image');
    $this->assertTrue($layout_field->isVisible());

    // Check thumbnail option appears for only image in list mode.
    $web_assert->assertWaitOnAjaxRequest();
    $this->assertContains('thumbnail', $page->findField('field_display_style_lof')->getHtml());

    // Check when grid is selected then columns field is visible.
    $this->assertFalse($page->findField('field_columns')->isVisible());
    $page->findField('field_file_type')->selectOption('oembed');
    $web_assert->assertWaitOnAjaxRequest();
    $page->findField('field_layout')->selectOption('grid');
    $web_assert->assertWaitOnAjaxRequest();
    $this->assertTrue($page->findField('field_columns')->isVisible());

    // Check thumbnail option appears for embeds only in grid mode.
    $this->assertContains('thumbnail', $page->findField('field_display_style_lof')->getHtml());
    $page->findField('field_layout')->selectOption('list');
    $web_assert->assertWaitOnAjaxRequest();
    $this->assertNotContains('thumbnail', $page->findField('field_display_style_lof')->getHtml());

    // Test if widget/block creation works.
    $this->widgetName = $this->randomString();
    $edit = [
      'info[0][value]' => $this->widgetName,
      'field_widget_title[0][value]' => $this->widgetName,
    ];
    $this->submitForm($edit, 'edit-submit');
    $web_assert->statusCodeEquals(200);

    $block = $this->entityTypeManager->getStorage('block_content')->loadByProperties(['field_widget_title' => $this->widgetName]);
    $this->assertNotEmpty($block, 'A match was not found which means block was created successfully.');
  }

  /**
   * Delete the widget created during testing.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function tearDown() {
    $blocks = $this->entityTypeManager->getStorage('block_content')->loadByProperties(['field_widget_title' => $this->widgetName]);
    /** @var \Drupal\block_content\Entity\BlockContent $block */
    foreach ($blocks as $block) {
      $block->delete();
    }
    parent::tearDown();
  }

}
