<?php

namespace Drupal\os_widgets\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ListWidgetsPaginationController for ajax pagination for widgets.
 *
 * @package Drupal\os_widgets\Controller
 */
class WidgetsPaginationController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * ListWidgetsPaginationController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack instance.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Changes widgets page via Ajax.
   *
   * @param string $id
   *   Id of the Block in context.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The respective page of the block.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function changePage($id = NULL) {
    $response = new AjaxResponse();
    /** @var \Symfony\Component\HttpFoundation\Request $current_request */
    $current_request = $this->request->getCurrentRequest();
    $selector = $current_request->query->get('selector');
    $pageId = $current_request->query->get('pagerid');
    $moreId = $current_request->query->get('moreid');
    $block = $this->entityTypeManager()->getStorage('block_content')->load($id);
    $render = $this->entityTypeManager()->getViewBuilder('block_content')->view($block);
    $response->addCommand(new ReplaceCommand('#' . $selector, $render));
    $response->addCommand(new ReplaceCommand('#' . $pageId, ''));
    $response->addCommand(new ReplaceCommand('#' . $moreId, ''));
    return $response;
  }

}
