<?php

namespace Drupal\os_pages\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Providing access to vsite_admin for removing book from outline.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.book_remove_form')) {
      $route->setRequirement('_permission', 'access content');
      $route->setRequirement('_group_permission', 'administer book outlines');
    }
  }

}
