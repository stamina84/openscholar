<?php

namespace Drupal\os_blog\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Overrides Disqus render element.
 *
 * @RenderElement("disqus")
 */
class DisqusOverride extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#title' => '',
      '#url' => '',
      '#identifier' => '',
      '#callbacks' => '',
      '#attributes' => ['id' => 'disqus_thread'],
      '#pre_render' => [
        get_class() . '::generatePlaceholder',
      ],
    ];
  }

  /**
   * Pre_render callback to generate a placeholder.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function generatePlaceholder(array $element) {
    if (\Drupal::currentUser()->hasPermission('view disqus comments')) {
      $element[] = [
        '#lazy_builder' => [
          get_class() . '::displayDisqusComments',
          [
            $element['#title'],
            $element['#url'],
            $element['#identifier'],
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * Rendering Disqus element after overriding disqus_domain with Vsite domain.
   */
  public static function displayDisqusComments($title, $url, $identifier) {
    $vSite_domain = '';
    $disqus_settings = \Drupal::config('disqus.settings');
    $blog_comments_config = \Drupal::config('os_blog.settings');
    if ($blog_comments_config->get('comment_type') == 'disqus_comments') {
      $vSite_domain = $blog_comments_config->get('disqus_shortname');
    }
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $element = [
      '#theme_wrappers' => ['disqus_noscript', 'container'],
      '#attributes' => ['id' => 'disqus_thread'],
    ];
    $renderer->addCacheableDependency($element, $disqus_settings);

    $disqus = [
      'domain' => $vSite_domain,
      'url' => $url,
      'title' => $title,
      'identifier' => $identifier,
      'disable_mobile' => $disqus_settings->get('behavior.disqus_disable_mobile'),
    ];

    // If the user is logged in, we can inject the username and email for
    // Disqus.
    $account = \Drupal::currentUser();
    if ($disqus_settings->get('behavior.disqus_inherit_login') && !$account->isAnonymous()) {
      $renderer->addCacheableDependency($element, $account);
      $disqus['name'] = $account->getUsername();
      $disqus['email'] = $account->getEmail();
    }

    // Provide alternate language support if desired.
    if ($disqus_settings->get('behavior.disqus_localization')) {
      $disqus['language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    // Check if we are to provide Single Sign-On access.
    if ($disqus_settings->get('advanced.sso.disqus_sso')) {
      $disqus += \Drupal::service('disqus.manager')->ssoSettings();
    }

    // Check if we want to track new comments in Google Analytics.
    if ($disqus_settings->get('behavior.disqus_track_newcomment_ga')) {
      // Add a callback when a new comment is posted.
      $disqus['callbacks']['onNewComment'][] = 'Drupal.disqus.disqusTrackNewComment';
      // Attach the js with the callback implementation.
      $element['#attached']['library'][] = 'disqus/ga';
    }

    if (!empty($element['#callbacks'])) {
      $disqus['callbacks'] = $element['#callbacks'];
    }

    // Add the disqus.js and all the settings to process the JavaScript and load
    // Disqus.
    $element['#attached']['library'][] = 'disqus/disqus';
    $element['#attached']['drupalSettings']['disqus'] = $disqus;

    return $element;
  }

}
