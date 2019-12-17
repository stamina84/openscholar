<?php

namespace Drupal\os_blog\Element;

use Drupal\disqus\Element\Disqus;

/**
 * Overrides Disqus render element.
 *
 * @RenderElement("vsite_disqus")
 */
class VsiteDisqusElement extends Disqus {

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
    $element = parent::displayDisqusComments($title, $url, $identifier);
    $blog_comments_config = \Drupal::config('os_blog.settings');

    if ($blog_comments_config->get('comment_type') == 'disqus_comments') {
      $element['#attached']['drupalSettings']['disqus']['domain'] = $blog_comments_config->get('disqus_shortname');
    }

    return $element;
  }

}
