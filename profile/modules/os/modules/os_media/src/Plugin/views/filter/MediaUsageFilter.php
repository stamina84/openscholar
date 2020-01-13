<?php

namespace Drupal\os_media\Plugin\views\filter;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\os_media\MediaAdminUiHelper;
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
   * @var \Drupal\os_media\MediaAdminUiHelper
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
   * @param \Drupal\os_media\MediaAdminUiHelper $media_admin_ui_helper
   *   Media admin UI helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaAdminUiHelper $media_admin_ui_helper) {
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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['operator']['default'] = 'LIKE';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorValidate($form, FormStateInterface $form_state) {
    /** @var array $submitted_data */
    $submitted_data = $form_state->getValues();
    $is_operator_exposed = (bool) $submitted_data['options']['expose']['use_operator'];

    if ($is_operator_exposed) {
      $form_state->setError($form['expose']['use_operator'], $this->t('Exposing the operator is not allowed. It is restricted to `LIKE`.'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions() {
    return [
      'LIKE' => $this->t('LIKE'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    return $this->operator;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->value)) {
      return;
    }

    $value = $this->value;
    $value = reset($value);

    if (!empty($value)) {
      /** @var \Drupal\node\NodeInterface[] $nodes_using_media_by_title */
      $nodes_using_media_by_title = $this->mediaAdminUiHelper->filterNodesUsingMediaByTitle($value);
      /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface[] $publications_using_media_by_title */
      $publications_using_media_by_title = $this->mediaAdminUiHelper->filterPublicationsUsingMediaByTitle($value);

      // Create a collection of media ids being used. Later the collection will
      // be used in filtering the result.
      $media_ids_in_nodes = array_map(static function (NodeInterface $node) {
        if ($node->hasField('field_attached_media')) {
          return array_column($node->get('field_attached_media')->getValue(), 'target_id');
        }

        if ($node->hasField('field_presentation_slides')) {
          return array_column($node->get('field_presentation_slides')->getValue(), 'target_id');
        }

        if ($node->hasField('field_software_package')) {
          return array_column($node->get('field_software_package')->getValue(), 'target_id');
        }

        return [];
      }, $nodes_using_media_by_title);

      $media_ids_in_publications = array_map(static function (ReferenceInterface $reference) {
        return array_column($reference->get('field_attach_files')->getValue(), 'target_id');
      }, $publications_using_media_by_title);

      $media_ids = array_merge([], ...$media_ids_in_nodes, ...$media_ids_in_publications);

      if ($media_ids) {
        $this->query->addWhere('AND', 'media_field_data.mid', $media_ids, 'IN');
      }
    }
  }

}
