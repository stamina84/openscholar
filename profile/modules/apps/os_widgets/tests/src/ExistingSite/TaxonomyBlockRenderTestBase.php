<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\block_content\BlockContentInterface;

/**
 * Class TaxonomyBlockRenderTestBase.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\TaxonomyWidget
 */
class TaxonomyBlockRenderTestBase extends OsWidgetsExistingSiteTestBase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\TaxonomyWidget
   */
  protected $taxonomyWidget;

  /**
   * Test vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->taxonomyWidget = $this->osWidgets->createInstance('taxonomy_widget');
    $this->vocabulary = $this->createVocabulary();
    $this->config = $this->container->get('config.factory');
    // Reset vocabulary allowed values.
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab->set('allowed_vocabulary_reference_types', [])
      ->save(TRUE);
  }

  /**
   * Creates a taxonomy block content.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The created block content entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTaxonomyBlockContent(array $values = []): BlockContentInterface {
    // Add default required fields.
    $values += [
      'field_taxonomy_behavior' => ['--all--'],
      'field_taxonomy_vocabulary' => [$this->vocabulary->id()],
      'field_taxonomy_tree_depth' => [0],
      'field_taxonomy_display_type' => ['classic'],
    ];
    return $this->createBlockContent($values);
  }

}
