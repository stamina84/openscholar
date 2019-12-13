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
    foreach ($apps as $app) {
      if ($app['entityType'] === 'media') {
        continue;
      }
      $title = (string) $app['title'];
      $bundle = $app['entityType'] === 'bibcite_reference' ? 'publications' : $app['bundle'][0];
      $options[$bundle] = $title;
    }

    $content_types = [];
    foreach ($options as $bundle => $title) {
      $url = Url::fromRoute('os_rss.rss_xml');
      $url->setOptions(['query' => ['type' => $bundle]]);
      $content_types[] = Link::fromTextAndUrl($title, $url)->toString();
    }

    // Load all vocalbulary from the site.
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      $vname = $vocabulary->get('name');
      $terms = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary->id());
      foreach ($terms as $term) {
        $url = Url::fromRoute('os_rss.rss_xml');
        $url->setOptions(['query' => ['term' => $term->tid]]);
        $categories[$vname][] = Link::fromTextAndUrl($term->name, $url)->toString();
      }
    }

    $build = [
      '#theme' => 'os_rss_page',
      '#apps' => $content_types,
      '#categories' => $categories,
    ];
    return $build;
  }

}
