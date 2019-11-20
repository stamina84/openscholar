<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests List Of Posts widget various form functions and widget creation.
 *
 * @group functional-javascript
 * @group widgets
 * @covers \Drupal\cp_taxonomy\Plugin\Field\FieldWidget\FilterByVocabWidget
 */
class ListOfPostsWidgetTest extends OsExistingSiteJavascriptTestBase {

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
    $this->user = $this->createUser(['administer blocks', 'administer taxonomy']);
    $this->addGroupAdmin($this->user, $this->group);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests List of Posts form.
   *
   * @covers ::os_widgets_form_alter
   */
  public function testListOfPostsForm() {
    $web_assert = $this->assertSession();
    // Create a Vsite vocabulary.
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->vsiteContextManager->activateVsite($this->group);
    $this->vocabulary = $this->createVocabulary();

    $this->visitViaVsite("block/add/list_of_posts", $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();

    // Check when Events is selected then only event type field is visible.
    $show_field = $page->findField('field_show');
    $this->assertFalse($show_field->isVisible());
    $field_content_type = $page->findField('field_content_type');
    $field_content_type->selectOption('events');
    $this->assertTrue($show_field->isVisible());

    // Check when Publication is selected then only publication types field is
    // visible.
    $publication_type_fields = $page->find('css', '.field--name-field-publication-types');
    $this->assertFalse($publication_type_fields->isVisible());
    $field_content_type = $page->findField('field_content_type');
    $field_content_type->selectOption('publications');
    $this->assertTrue($publication_type_fields->isVisible());

    // Check when Show More is checked then only uri and title fields are
    // visible.
    $url_fields = $page->findField('field_url_for_the_more_link[0][uri]');
    $this->assertFalse($url_fields->isVisible());
    $page->checkField('field_show_more_link[value]');
    $this->assertTrue($url_fields->isVisible());

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
