<?php

namespace Drupal\os_media\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\os_media\MediaAdminUIHelper;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by entity title using a media entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("os_media_media_usage_filter")
 */
class MediaUsageFilter extends FilterPluginBase {

  /**
   * Media admin UI helper.
   *
   * @var \Drupal\os_media\MediaAdminUIHelper
   */
  protected $mediaAdminUiHelper;

  /**
   * Creates a new MediaUsageFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\os_media\MediaAdminUIHelper $media_admin_ui_helper
   *   Media admin UI helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaAdminUIHelper $media_admin_ui_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaAdminUiHelper = $media_admin_ui_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os_media.media_admin_ui_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // TODO: An empty textfield appears in "used in" filter setting.
    $form['value'] = [
      '#type' => 'textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // TODO: Restrict the operator to `LIKE`.
    if (empty($this->value)) {
      return;
    }

    $value = $this->value;
    $value = reset($value);

    if (!empty($value)) {
      /** @var \Drupal\node\NodeInterface[] $field_attached_media_usages */
      $field_attached_media_usages = $this->mediaAdminUiHelper->filterNodesUsingMediaByTitle($value);

      $media_entity_ids = array_map(static function (NodeInterface $node) {
        return $node->get('field_attached_media')->first()->getValue()['target_id'];
      }, $field_attached_media_usages);

      // TODO: Do the same for reference+media field.
      $this->query->addWhere('AND', 'media_field_data.mid', $media_entity_ids, 'IN');
    }
  }

}
