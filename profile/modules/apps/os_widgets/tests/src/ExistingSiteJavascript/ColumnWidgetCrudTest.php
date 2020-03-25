<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests column widget CRUD customizations.
 *
 * @group functional-javascript
 * @group widgets
 */
class ColumnWidgetCrudTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Test account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vsiteContextManager = $this->container->get('vsite.context_manager');

    $this->account = $this->createUser(['administer blocks']);
    $this->group->addMember($this->account);
    $this->drupalLogin($this->account);
  }

  /**
   * @covers ::os_widgets_block_content_column_form_submit
   * @covers \Drupal\os_widgets\Plugin\Field\FieldWidget\PlaceBlockContentWidget
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCreate(): void {
    // Test setup tasks.
    $inner_widget_name = $this->randomMachineName();
    $vsite_widget = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => $inner_widget_name,
      ],
      'field_widget_title' => [
        'value' => $inner_widget_name,
      ],
    ]);
    $this->group->addContent($vsite_widget, 'group_entity:block_content');
    $inner_block = $this->createBlockForBlockContent($vsite_widget);
    $non_vsite_widget = $this->createBlockContent();
    $column_widget_name = $this->randomMachineName();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_content_storage */
    $block_content_storage = $this->container->get('entity_type.manager')->getStorage('block_content');

    $this->visitViaVsite('block/add/column', $this->group);

    $this->assertSession()->elementExists('css', "[name=\"field_widgets[widgets][]\"] option[value=\"{$vsite_widget->id()}\"]");
    $this->assertSession()->elementNotExists('css', "[name=\"field_widgets[widgets][]\"] option[value=\"{$non_vsite_widget->id()}\"]");

    // Perform widget creation.
    $this->getSession()->getPage()->fillField('info[0][value]', $column_widget_name);
    $this->getSession()->getPage()->fillField('field_widget_title[0][value]', $column_widget_name);
    $this->getSession()->getPage()->selectFieldOption('field_widgets[layouts]', 'layout_twocol_section');
    $this->getSession()->getPage()->selectFieldOption('field_widgets[widgets][]', $vsite_widget->id());
    $this->getSession()->getPage()->pressButton('Save');

    $widget_entities = $block_content_storage->loadByProperties([
      'info' => $column_widget_name,
    ]);
    $widget_entity = reset($widget_entities);
    $this->assertNotNull($widget_entity);

    // Make sure the essential data are correctly attached to the widget entity.
    /** @var \Drupal\layout_builder\Section $layout_setting */
    $layout_setting = $widget_entity->get(OverridesSectionStorage::FIELD_NAME)->first()->getValue()['section'];
    $this->assertInstanceOf(Section::class, $layout_setting);
    $this->assertEquals('layout_twocol_section', $layout_setting->getLayoutId());

    /** @var \Drupal\layout_builder\SectionComponent[] $components */
    $components = $layout_setting->getComponents();
    $this->assertTrue(isset($components[$inner_block->uuid()]));
    $this->assertEquals("block_content:{$vsite_widget->uuid()}", $components[$inner_block->uuid()]->getPluginId());

    $this->vsiteContextManager->activateVsite($this->group);
    $this->placeBlockContentToRegion($widget_entity, 'content');

    // Making sure that the inner widget indeed appears inside the layout
    // region.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextContains($column_widget_name);
    $this->assertSession()->elementExists('css', "#block-block-content{$widget_entity->uuid()} .layout--twocol-section--50-50 .layout__region--first .block-block-content{$vsite_widget->uuid()}");

    // Cleanup.
    $widget_entity->delete();
  }

  /**
   * @covers ::os_widgets_block_content_column_edit_form_submit
   * @covers \Drupal\os_widgets\Plugin\Field\FieldWidget\PlaceBlockContentWidget
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testUpdate(): void {
    // Setup tasks.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_content_storage */
    $block_content_storage = $this->container->get('entity_type.manager')->getStorage('block_content');

    $inner_widget_name_1 = $this->randomMachineName();
    $inner_widget_1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => $inner_widget_name_1,
      ],
      'field_widget_title' => [
        'value' => $inner_widget_name_1,
      ],
    ]);
    $inner_block_1 = $this->createBlockForBlockContent($inner_widget_1);
    $this->group->addContent($inner_widget_1, 'group_entity:block_content');

    $inner_widget_name_2 = $this->randomMachineName();
    $inner_widget_2 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => $inner_widget_name_2,
      ],
      'field_widget_title' => [
        'value' => $inner_widget_name_2,
      ],
    ]);
    $inner_block_2 = $this->createBlockForBlockContent($inner_widget_2);
    $this->group->addContent($inner_widget_2, 'group_entity:block_content');

    $section_component[$inner_block_1->uuid()] = new SectionComponent($inner_block_1->uuid(), 'first', [
      'id' => "block_content:{$inner_widget_1->uuid()}",
    ]);
    $column_widget_name = $this->randomMachineName();
    $column_widget = $this->createBlockContent([
      'type' => 'column',
      'field_widgets' => [
        [
          'target_id' => $inner_widget_1->id(),
        ],
      ],
      'info' => [
        'value' => $column_widget_name,
      ],
      'field_widget_title' => [
        'value' => $column_widget_name,
      ],
      OverridesSectionStorage::FIELD_NAME => new Section('layout_twocol_section', [], $section_component),
    ]);
    $this->group->addContent($column_widget, 'group_entity:block_content');

    $this->visitViaVsite("block/{$column_widget->id()}", $this->group);

    // Make sure that the desired values are selected during column edit.
    $this->assertSession()->fieldValueEquals('field_widgets[layouts]', 'layout_twocol_section');
    /** @var array $selected_widgets */
    $selected_widgets = $this->getSession()->getPage()->findField('field_widgets[widgets][]')->getValue();
    $this->assertCount(1, $selected_widgets);
    $this->assertEquals($inner_widget_1->id(), $selected_widgets[0]);
    $this->assertSession()->optionNotExists('field_widgets[widgets][]', $column_widget->id());

    // Run tests for the widget update.
    $this->submitForm([
      'field_widgets[layouts]' => 'layout_threecol_section',
      'field_widgets[widgets][]' => [
        $inner_widget_1->id(),
        $inner_widget_2->id(),
      ],
    ], 'Save');

    $updated_column_widget = $block_content_storage->load($column_widget->id());

    // Make sure that updates are reflected in the widget entity.
    /** @var \Drupal\layout_builder\Section $layout_setting */
    $layout_setting = $updated_column_widget->get(OverridesSectionStorage::FIELD_NAME)->first()->getValue()['section'];
    $this->assertInstanceOf(Section::class, $layout_setting);
    $this->assertEquals('layout_threecol_section', $layout_setting->getLayoutId());

    /** @var \Drupal\layout_builder\SectionComponent[] $components */
    $components = $layout_setting->getComponents();
    $this->assertTrue(isset($components[$inner_block_1->uuid()]));
    $this->assertTrue(isset($components[$inner_block_2->uuid()]));
    $this->assertEquals("block_content:{$inner_widget_1->uuid()}", $components[$inner_block_1->uuid()]->getPluginId());
    $this->assertEquals("block_content:{$inner_widget_2->uuid()}", $components[$inner_block_2->uuid()]->getPluginId());

    $this->vsiteContextManager->activateVsite($this->group);
    $this->placeBlockContentToRegion($column_widget, 'content');

    // Making sure that the inner widget indeed appears inside the layout
    // region.
    $this->visitViaVsite('', $this->group);
    $this->assertSession()->pageTextContains($column_widget_name);
    $this->assertSession()->elementExists('css', "#block-block-content{$column_widget->uuid()} .layout--threecol-section--25-50-25 .layout__region--first .block-block-content{$inner_widget_1->uuid()}");
    $this->assertSession()->elementExists('css', "#block-block-content{$column_widget->uuid()} .layout--threecol-section--25-50-25 .layout__region--first .block-block-content{$inner_widget_2->uuid()}");
  }

  /**
   * @covers \Drupal\os_widgets\Plugin\Field\FieldWidget\PlaceBlockContentWidget
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCreateFormOutsideVsite(): void {
    // Test setup tasks.
    $vsite_widget = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => $this->randomMachineName(),
      ],
      'field_widget_title' => [
        'value' => $this->randomMachineName(),
      ],
    ]);
    $this->group->addContent($vsite_widget, 'group_entity:block_content');
    $vsite_inner_block = $this->createBlockForBlockContent($vsite_widget);
    $non_vsite_widget = $this->createBlockContent([
      'type' => 'custom_text_html',
    ]);
    $non_vsite_inner_block = $this->createBlockForBlockContent($non_vsite_widget);
    $column_widget_name = $this->randomMachineName();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_content_storage */
    $block_content_storage = $this->container->get('entity_type.manager')->getStorage('block_content');

    $this->visit('/block/add/column');

    $this->assertSession()->optionExists('field_widgets[widgets][]', $vsite_widget->id());
    $this->assertSession()->optionExists('field_widgets[widgets][]', $non_vsite_widget->id());

    // Perform widget creation.
    $this->submitForm([
      'info[0][value]' => $column_widget_name,
      'field_widget_title[0][value]' => $column_widget_name,
      'field_widgets[layouts]' => 'layout_twocol_section',
      'field_widgets[widgets][]' => [
        $vsite_widget->id(),
        $non_vsite_widget->id(),
      ],
    ], 'Save');

    $widget_entities = $block_content_storage->loadByProperties([
      'info' => $column_widget_name,
    ]);
    $widget_entity = reset($widget_entities);
    $this->assertNotNull($widget_entity);

    // Make sure the essential data are correctly attached to the widget entity.
    /** @var \Drupal\layout_builder\Section $layout_setting */
    $layout_setting = $widget_entity->get(OverridesSectionStorage::FIELD_NAME)->first()->getValue()['section'];
    $this->assertInstanceOf(Section::class, $layout_setting);
    $this->assertEquals('layout_twocol_section', $layout_setting->getLayoutId());

    /** @var \Drupal\layout_builder\SectionComponent[] $components */
    $components = $layout_setting->getComponents();
    $this->assertTrue(isset($components[$vsite_inner_block->uuid()]));
    $this->assertTrue(isset($components[$non_vsite_inner_block->uuid()]));
    $this->assertEquals("block_content:{$vsite_widget->uuid()}", $components[$vsite_inner_block->uuid()]->getPluginId());
    $this->assertEquals("block_content:{$non_vsite_widget->uuid()}", $components[$non_vsite_inner_block->uuid()]->getPluginId());

    // Cleanup.
    $widget_entity->delete();
  }

}
