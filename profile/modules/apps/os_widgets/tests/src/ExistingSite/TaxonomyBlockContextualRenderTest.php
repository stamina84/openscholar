<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\views\Entity\View;
use DateInterval;
use DateTime;

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
    // Should not link to filtered listing.
    $this->assertNotContains('/blog/' . $term_blog->id(), $markup->__toString());
    $this->assertContains($term_other->label(), $markup->__toString());

    $view_blog_exec->preExecute();
    $view_blog_exec->execute();
    $view_blog_exec->render('page_1');

    // Check with active apps.
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term_blog->label(), $markup->__toString());
    // Check contextual link to filtered listing.
    $this->assertContains('/blog/' . $term_blog->id(), $markup->__toString());
    $this->assertNotContains($term_other->label(), $markup->__toString());
  }

  /**
   * Test proper term is rendered with multiple bundles contextual at views.
   */
  public function testBuildContextualViewMultipleBundles() {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'node:blog',
        'node:software_project',
        'node:software_release',
      ])
      ->save(TRUE);
    $term = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    $this->createNode([
      'type' => 'software_project',
      'field_taxonomy_terms' => [
        $term,
      ],
    ]);
    $this->createNode([
      'type' => 'software_release',
      'field_taxonomy_terms' => [
        $term,
      ],
    ]);
    // Create a blog, that should not display in count.
    $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $renderer = $this->container->get('renderer');

    $view_projects = View::load('os_software_projects');
    $view_projects_exec = $view_projects->getExecutable();
    $view_projects_exec->setDisplay('page_1');
    $view_projects_exec->preExecute();
    $view_projects_exec->execute();
    $view_projects_exec->render('page_1');

    // Check with active apps.
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term->label() . ' (2)', $markup->__toString());
    // Check contextual link to filtered listing.
    $this->assertContains('/software/' . $term->id(), $markup->__toString());
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
      $view_exec->preExecute();
      $view_exec->execute();
      $view_exec->render($display);
      // Check with active apps.
      $render = $view_builder->view($block_content);
      /** @var \Drupal\Core\Render\Markup $markup */
      $markup = $renderer->renderRoot($render);
      // Checking rendered term.
      $this->assertContains($term_pub->label(), $markup->__toString());
      $this->assertNotContains($term_other->label(), $markup->__toString());
    }
  }

  /**
   * Dataprovider function for test.
   *
   * @see testBuildContextualViewsEvents
   */
  public function eventContextViews() {
    return [
      [
        'name' => 'calendar',
        'display' => 'page_1',
      ],
      [
        'name' => 'past_events_calendar',
        'display' => 'page',
      ],
      [
        'name' => 'upcoming_calendar',
        'display' => 'page',
      ],
    ];
  }

  /**
   * Test proper term is rendered on events pages.
   *
   * Always show the count for and filter on upcoming events.
   *
   * @dataProvider eventContextViews
   */
  public function testBuildContextualViewsEvents($view_name, $display_name) {
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab
      ->set('allowed_vocabulary_reference_types', [
        'node:events',
      ])
      ->save(TRUE);
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    // Block content without empty terms.
    $block_content = $this->createTaxonomyBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
    ]);
    // Create both upcoming and past events.
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $new_datetime->add($date_interval);
    $date1 = $new_datetime->format("Y-m-d\TH:i:s");
    $new_datetime->add($date_interval);
    $date2 = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNodeUpcoming = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date1,
        'end_value' => $date2,
        'timezone' => 'America/New_York',
        'infinite' => 0,
      ],
      'field_taxonomy_terms' => [
        $term1,
      ],
    ]);
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date1 = $new_datetime->format("Y-m-d\TH:i:s");
    $new_datetime->add($date_interval);
    $date2 = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNodePast = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date2,
        'end_value' => $date1,
        'timezone' => 'America/New_York',
        'infinite' => 0,
      ],
      'field_taxonomy_terms' => [
        $term1,
        $term2,
      ],
    ]);
    $this->group->addContent($eventNodeUpcoming, 'group_node:events');
    $this->group->addContent($eventNodePast, 'group_node:events');

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $renderer = $this->container->get('renderer');

    $view_projects = View::load($view_name);
    $view_projects_exec = $view_projects->getExecutable();
    $view_projects_exec->setDisplay($display_name);
    $view_projects_exec->preExecute();
    $view_projects_exec->execute();
    $view_projects_exec->render($display_name);

    // Check with active apps.
    $render = $view_builder->view($block_content);
    /** @var \Drupal\Core\Render\Markup $markup */
    $markup = $renderer->renderRoot($render);
    // Checking rendered term.
    $this->assertContains($term1->label() . ' (1)', $markup->__toString());
    // Check contextual link to filtered listing.
    $this->assertContains('/calendar/upcoming/' . $term1->id(), $markup->__toString());
  }

}
