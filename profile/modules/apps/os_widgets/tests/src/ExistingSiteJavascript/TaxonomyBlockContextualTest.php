<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Tests os_widgets module Taxonomy widget with contextual.
 *
 * @group functional-javascript
 * @group widgets
 */
class TaxonomyBlockContextualTest extends OsExistingSiteJavascriptTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $vsiteContextManager;

  /**
   * Test vocabulary id.
   *
   * @var string
   */
  protected $vid;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');

    $this->vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $this->vid);
    // Reset vocabulary allowed values.
    $config_vocab = $this->configFactory->getEditable('taxonomy.vocabulary.' . $this->vid);
    $config_vocab->set('allowed_vocabulary_reference_types', [
      'node:blog',
      'node:news',
    ])->save(TRUE);

    // Block content without empty terms.
    $block_content = $this->createBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
      'field_taxonomy_vocabulary' => [$this->vid],
      'field_taxonomy_tree_depth' => [0],
      'field_taxonomy_display_type' => ['classic'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');

    $group_admin = $this->createUser();
    $this->addGroupEnhancedMember($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests contextual on global page.
   */
  public function testTaxonomyContextualOnGlobalPage() {
    $web_assert = $this->assertSession();

    $term_blog = $this->createGroupTerm($this->group, $this->vid, []);
    $term_news = $this->createGroupTerm($this->group, $this->vid, []);
    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $node = $this->createNode([
      'type' => 'news',
      'field_taxonomy_terms' => [
        $term_news,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $this->visitViaVsite('', $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextContains($term_news->label());
  }

  /**
   * Tests contextual on blog pages.
   */
  public function testTaxonomyContextualBlogPage() {
    $web_assert = $this->assertSession();

    $term_blog = $this->createGroupTerm($this->group, $this->vid, []);
    $term_other = $this->createGroupTerm($this->group, $this->vid, []);
    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $this->visitViaVsite('blog', $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextNotContains($term_other->label());
    $this->visitViaVsite('node/' . $node->id(), $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextNotContains($term_other->label());
  }

  /**
   * Tests contextual on publication pages.
   */
  public function testTaxonomyContextualPublicationsPage() {
    $web_assert = $this->assertSession();

    $term_pub = $this->createGroupTerm($this->group, $this->vid, []);
    $term_other = $this->createGroupTerm($this->group, $this->vid, []);
    $reference = $this->createReference([
      'field_taxonomy_terms' => [
        $term_pub,
      ],
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');
    $this->visitViaVsite('publications', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($term_pub->label());
    $web_assert->pageTextNotContains($term_other->label());
    $this->visitViaVsite('bibcite/reference/' . $reference->id(), $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($term_pub->label());
    $web_assert->pageTextNotContains($term_other->label());
  }

}
