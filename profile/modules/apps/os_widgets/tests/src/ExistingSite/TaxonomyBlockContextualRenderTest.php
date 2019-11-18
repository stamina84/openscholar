<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\views\Entity\View;

/**
 * Class TaxonomyBlockContextualRenderTest.
 *
 * @group kernel
 * @group widgets-1
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\TaxonomyWidget
 */
class TaxonomyBlockContextualRenderTest extends TaxonomyBlockRenderTestBase {

  /**
   * Test proper term is rendered with blog contextual.
   */
  public function testBuildContextualNodeBundle() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'node:blog',
        'node:news',
      ])
      ->save(TRUE);
    $term_blog = $this->createTerm($this->vocabulary);
    $term_other = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_contents[] = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 0,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    // Block content with empty terms.
    $block_contents[] = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $os_widgets_context = $this->container->get('os_widgets_context.context');
    $os_widgets_context->addApp('blog');
    $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $renderer = $this->container->get('renderer');
    // All should work as same.
    foreach ($block_contents as $block_content) {
      $render = $view_builder->view($block_content);
      /** @var \Drupal\Core\Render\Markup $markup */
      $markup = $renderer->renderRoot($render);
      // Checking rendered term.
      $this->assertContains($term_blog->label(), $markup->__toString());
      $this->assertNotContains($term_other->label(), $markup->__toString());
    }
  }

  /**
   * Test proper term is rendered contextual on global page.
   */
  public function testBuildContextualGlobalPage() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'node:blog',
        'node:news',
      ])
      ->save(TRUE);
    $renderer = $this->container->get('renderer');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $term_blog = $this->createTerm($this->vocabulary);
    $term_news = $this->createTerm($this->vocabulary);
    $term_empty = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $this->createNode([
      'type' => 'news',
      'field_taxonomy_terms' => [
        $term_news,
      ],
    ]);
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Test on global page, without app.
    $this->assertContains($term_blog->label(), $markup->__toString());
    $this->assertContains($term_news->label(), $markup->__toString());
    $this->assertContains($term_empty->label(), $markup->__toString());
  }

  /**
   * Test proper term is rendered with media contextual.
   */
  public function testBuildContextualMedia() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'media:*',
      ])
      ->save(TRUE);
    $term_media = $this->createTerm($this->vocabulary);
    $term_other = $this->createTerm($this->vocabulary);
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $os_widgets_context = $this->container->get('os_widgets_context.context');
    $os_widgets_context->addApp('media');
    $this->createMedia([
      'field_taxonomy_terms' => [
        $term_media,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term_media->label(), $markup->__toString());
    $this->assertNotContains($term_other->label(), $markup->__toString());
  }

  /**
   * Test proper term is rendered with publication contextual.
   */
  public function testBuildContextualPublication() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'bibcite_reference:*',
      ])
      ->save(TRUE);
    $term_pub = $this->createTerm($this->vocabulary);
    $term_other = $this->createTerm($this->vocabulary);
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $os_widgets_context = $this->container->get('os_widgets_context.context');
    $os_widgets_context->addApp('publications');
    $this->createReference([
      'field_taxonomy_terms' => [
        $term_pub,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term_pub->label(), $markup->__toString());
    $this->assertNotContains($term_other->label(), $markup->__toString());
  }

  /**
   * Test proper term is rendered with blog contextual at views.
   */
  public function testBuildContextualViewNodeBundle() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'node:blog',
        'node:news',
      ])
      ->save(TRUE);
    $term_blog = $this->createTerm($this->vocabulary);
    $term_other = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $renderer = $this->container->get('renderer');

    $view_blog = View::load('blog');
    $view_blog_exec = $view_blog->getExecutable();
    $view_blog_exec->setDisplay('page_1');

    // Check without active apps.
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term_blog->label(), $markup->__toString());
    $this->assertContains($term_other->label(), $markup->__toString());

    os_widgets_views_pre_render($view_blog_exec);

    // Check with active apps.
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term_blog->label(), $markup->__toString());
    $this->assertNotContains($term_other->label(), $markup->__toString());
  }

  /**
   * Test proper term is rendered with publications contextual at views.
   */
  public function testBuildContextualViewPublications() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'bibcite_reference:*',
        'node:news',
      ])
      ->save(TRUE);
    $term_pub = $this->createTerm($this->vocabulary);
    $term_other = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $this->createReference([
      'field_taxonomy_terms' => [
        $term_pub,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $renderer = $this->container->get('renderer');

    $allowed_publication_views_displays = [
      'page_1',
      'page_2',
      'page_3',
      'page_4',
    ];
    $view = View::load('publications');
    $view_exec = $view->getExecutable();
    foreach ($allowed_publication_views_displays as $display) {
      $this->container->get('os_widgets_context.context')->resetApps();
      $view_exec->setDisplay($display);
      os_widgets_views_pre_render($view_exec);
      // Check with active apps.
      $render = $view_builder->view($block_content);
      /** @var \Drupal\Core\Render\Markup $markup */
      $markup = $renderer->renderRoot($render);
      // Checking rendered term.
      $this->assertContains($term_pub->label(), $markup->__toString());
      $this->assertNotContains($term_other->label(), $markup->__toString());
    }
  }

}
