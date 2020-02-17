<?php

namespace Drupal\vsite\Plugin\Purl\Method;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\group_purl\Plugin\Purl\Method\GroupPrefixMethod;

/**
 * Method for handling path prefixed vsites.
 *
 * @PurlMethod(
 *   id="vsite_prefix",
 *   title = @Translation("Path prefixed vsite content."),
 *   stages = {
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND,
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PRE_GENERATE
 *   }
 * )
 */
class VsitePrefixMethod extends GroupPrefixMethod implements ContainerFactoryPluginInterface {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vsite.context_manager')
    );
  }

  /**
   * Creates a new VsitePrefixMethod object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * Override to allow for showing front page instead of entity view.
   *
   * {@inheritdoc}.
   */
  public function contains(Request $request, $modifier) {
    $uri = $request->getPathInfo();

    // Always modify the 'entity.group.canonical' route so that the path will
    // be replaced and end as ''. So that <front> path is matched.
    if ($uri === '/' . $modifier) {
      return TRUE;
    }
    return $this->checkPath($modifier, $uri);
  }

  /**
   * Alters the outbound path based on whether purl is active.
   *
   * @param string $modifier
   *   The modifier to be used in alteration.
   * @param string $path
   *   The path.
   * @param array $options
   *   Purl options.
   *
   * @return string
   *   The altered path.
   */
  public function enterContext($modifier, $path, array &$options): string {
    /** @var string $purl */
    $purl = $this->vsiteContextManager->getActivePurl();

    $options['purl_exit'] = (((bool) $purl) && (strpos($path, $purl) === 1));

    if ($options['purl_exit']) {
      return $path;
    }

    return "/$modifier$path";
  }

}
