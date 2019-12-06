<?php

namespace Drupal\os_pages\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {
  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * PageController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Generates printer-friendly HTML for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that will be output.
   *
   * @return array
   *   A render array for the exported HTML of a given node.
   */
  public function pageExport(NodeInterface $node) {
    $build = $this->entityTypeManager->getViewBuilder('node')->view($node, 'print', NULL);
    unset($build['#theme']);

    $build = [
      '#theme' => 'page_export_html',
      '#content' => $build,
    ];

    $response = new Response();
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    $response->setContent($this->renderer->renderRoot($build));

    return $response;
  }

}
