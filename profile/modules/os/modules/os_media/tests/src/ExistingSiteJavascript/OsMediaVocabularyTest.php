<?php

namespace Drupal\Tests\os_media\ExistingSiteJavascript;

use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Drupal\media\Entity\Media;
use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Tests media edit with Taxonomy widget.
 *
 * @group functional-javascript
 * @group os
 */
class OsMediaVocabularyTest extends OsExistingSiteJavascriptTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user we're logging in as.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->createUser();
    $this->addGroupAdmin($this->user, $this->group);
    $this->drupalLogin($this->user);
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Edit media vocabulary (select options).
   */
  public function testEditMediaVocabularySelectOptions() {
    $vid = strtolower($this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);
    $term1 = $this->createGroupTerm($this->group, $vid);
    $this->createGroupTerm($this->group, $vid);

    $media = $this->createMedia([], 'image');
    $this->group->addContent($media, 'group_entity:media');
    $this->visitFilesPageAndEditFirstFile($vid);
    $page = $this->getCurrentPage();
    $wrapper_class = '.tw-widget--options-select';
    $this->selectOptionWithSelect2($wrapper_class, $term1->label());
    $page->pressButton('Save');
    $this->waitForDialogClose();

    $media = Media::load($media->id());
    $values = $media->get('field_taxonomy_terms')->getValue();
    $this->assertEquals(1, count($values));
    $this->assertEquals($term1->id(), $values[0]['target_id']);
  }

  /**
   * Edit media vocabulary (autocomplete).
   */
  public function testEditMediaVocabularyAutocomplete() {
    $web_assert = $this->assertSession();
    $vid = strtolower($this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_AUTOCOMPLETE);
    $term1 = $this->createGroupTerm($this->group, $vid, ['name' => 'Autocomplete term 1']);
    $this->createGroupTerm($this->group, $vid, ['name' => 'Autocomplete term 2']);

    $media = $this->createMedia([], 'image');
    $this->group->addContent($media, 'group_entity:media');
    $this->visitFilesPageAndEditFirstFile($vid);
    $page = $this->getCurrentPage();
    $input_autocomplete = $page->find('css', '.tw-widget--autocomplete input');
    $input_autocomplete->setValue('Autocom');
    $web_assert->waitForElement('css', 'ul.dropdown-menu');
    // Select first element.
    $page->find('css', 'ul.dropdown-menu li')->press();
    $web_assert->pageTextContains($term1->label());
    $page->pressButton('Save');
    $this->waitForDialogClose();

    $media = Media::load($media->id());
    $values = $media->get('field_taxonomy_terms')->getValue();
    $this->assertEquals(1, count($values));
    $this->assertEquals($term1->id(), $values[0]['target_id']);
  }

  /**
   * Edit media vocabulary (checkboxes).
   */
  public function testEditMediaVocabularyCheckboxes() {
    $vid = strtolower($this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_BUTTONS);
    $term1 = $this->createGroupTerm($this->group, $vid);
    $this->createGroupTerm($this->group, $vid);

    $media = $this->createMedia([], 'image');
    $this->group->addContent($media, 'group_entity:media');
    $this->visitFilesPageAndEditFirstFile($vid);
    $page = $this->getCurrentPage();
    // Check first checkbox.
    $page->findById('tw-taxonomy-term-' . $term1->id())->check();
    $page->pressButton('Save');
    $this->waitForDialogClose();

    $media = Media::load($media->id());
    $values = $media->get('field_taxonomy_terms')->getValue();
    $this->assertEquals(1, count($values));
    $this->assertEquals($term1->id(), $values[0]['target_id']);
  }

  /**
   * Edit media vocabulary (tree).
   */
  public function testEditMediaVocabularyTree() {
    $web_assert = $this->assertSession();
    $vid = strtolower($this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_TREE);
    $term1 = $this->createGroupTerm($this->group, $vid);
    $this->createGroupTerm($this->group, $vid, ['parent' => $term1->id()]);

    $media = $this->createMedia([], 'image');
    $this->group->addContent($media, 'group_entity:media');
    $this->visitFilesPageAndEditFirstFile($vid);
    $page = $this->getCurrentPage();
    // Check first checkbox.
    $page->find('css', '.tw-widget--tree input[type=checkbox]')->check();
    $web_assert->elementExists('css', '.tw-widget--tree span.expander');
    $page->pressButton('Save');
    $this->waitForDialogClose();

    $media = Media::load($media->id());
    $values = $media->get('field_taxonomy_terms')->getValue();
    $this->assertEquals(1, count($values));
    $this->assertEquals($term1->id(), $values[0]['target_id']);
  }

  /**
   * Helper function that will navigate to files list and edit first file.
   *
   * @param string $vid
   *   Vocabulary id that should appear.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function visitFilesPageAndEditFirstFile(string $vid): void {
    $web_assert = $this->assertSession();
    $this->visitViaVsite('cp/content/browse/files', $this->group);
    $page = $this->getCurrentPage();
    $web_assert->waitForElementVisible('css', 'a.fa-edit');
    $page->find('css', 'a.fa-edit')->click();
    $page->find('named', ['fieldset', 'Advanced'])->click();
    $web_assert->waitForText($vid);
    $web_assert->pageTextContains($vid);
  }

}
