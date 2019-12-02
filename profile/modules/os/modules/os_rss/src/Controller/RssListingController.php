<?php

namespace Drupal\os_rss\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os_app_access\AppLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Controller that renders rss listing page.
 */
class RssListingController extends ControllerBase {

  /**
   * App Loader service.
   *
   * @var \Drupal\os_app_access\AppLoader
   */
  protected $appLoader;

  /**
   * App Loader service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os_app_access.app_loader'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Constructor to get this object.
   */
  public function __construct(AppLoader $app_loader, EntityTypeBundleInfo $bundle_info) {
    $this->appLoader = $app_loader;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    // Get all apps accessible to user.
    $apps = $this->appLoader->getAppsForUser($this->currentUser());

    $content_types = [];
    foreach ($apps as $app) {
      $title = (string) $app['title'];
      $url = Url::fromRoute('os_rss.rss_xml');
      $url->setOptions(['query' => ['type' => $app['id']]]);
      $content_types[] = Link::fromTextAndUrl($title, $url)->toString();
    }

    // Load all bibcite_reference on the site.
    $bundles = $this->bundleInfo->getBundleInfo('bibcite_reference');

    $bibcite_reference = [];
    foreach ($bundles as $bundle => $label) {
      $url = Url::fromRoute('os_rss.rss_xml');
      $url->setOptions(['query' => ['type' => $bundle]]);
      $bibcite_reference[] = Link::fromTextAndUrl($label['label'], $url)->toString();
    }

    // Load all vocalbulary from the site.
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vocablary) {
      $url = Url::fromRoute('os_rss.rss_xml');
      $url->setOptions(['query' => ['term' => $vocablary->id()]]);
      $categories[] = Link::fromTextAndUrl($vocablary->get('name'), $url)->toString();
    }

    $build = [
      '#theme' => 'os_rss_page',
      '#apps' => $content_types,
      '#publications' => $bibcite_reference,
      '#categories' => $categories,
    ];
    return $build;
  }

}
