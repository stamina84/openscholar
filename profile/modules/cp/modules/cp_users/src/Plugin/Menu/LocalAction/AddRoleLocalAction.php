<?php

namespace Drupal\cp_users\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a local action plugin with a dynamic title.
 */
class AddRoleLocalAction extends LocalActionDefault {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, VsiteContextManagerInterface $vsite_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
    $this->vsiteManager = $vsite_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $route_parameters = parent::getRouteParameters($route_match);

    // Check if in vsite context.
    if ($this->vsiteManager->getActiveVsite()) {
      // Dynamically send `group_type` parameter based on vsite type.
      $route_parameters['group_type'] = $this->vsiteManager->getActiveVsite()->getGroupType()->id();
    }

    return $route_parameters;
  }

}
