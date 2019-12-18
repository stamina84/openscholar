<?php

namespace Drupal\cp_users\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class CpUserRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter compact mode route to use 'use compact mode' permission.
    if ($route = $collection->get('system.admin_compact_page')) {
      $route->setRequirement('_permission', 'use compact mode');
    }
  }

}
