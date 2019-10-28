<?php

namespace Drupal\os_widgets\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\os_widgets\OsWidgetsContextInterface;

/**
 * OsWidgetsCacheContext.
 */
class OsWidgetsCacheContext implements CacheContextInterface {

  /**
   * Os Widgets Context.
   *
   * @var \Drupal\os_widgets\OsWidgetsContextInterface
   */
  protected $osWidgetsContext;

  /**
   * Constructs a new OsWidgetsCacheContext class.
   *
   * @param \Drupal\os_widgets\OsWidgetsContextInterface $os_widgets_context
   *   Os Widgets Context.
   */
  public function __construct(OsWidgetsContextInterface $os_widgets_context) {
    $this->osWidgetsContext = $os_widgets_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('OS widgets cache context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $bundles = $this->osWidgetsContext->getBundles();
    asort($bundles);
    return implode($bundles, '_');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
