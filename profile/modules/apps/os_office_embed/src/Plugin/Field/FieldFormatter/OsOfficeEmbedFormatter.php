<?php

namespace Drupal\os_office_embed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'os_office_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "os_office_embed",
 *   label = @Translation("Embedded Office Documents"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class OsOfficeEmbedFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a OsOfficeEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FileSystemInterface $file_system) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'width' => 500,
      'height' => 400,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
      'width' => [
        '#type' => 'textfield',
        '#title' => $this->t('Width'),
        '#size' => 20,
        '#default_value' => $this->getSetting('width'),
      ],
      'height' => [
        '#type' => 'textfield',
        '#title' => $this->t('Height'),
        '#size' => 20,
        '#default_value' => $this->getSetting('height'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $summary[] = $this->t('Width: @width px', [
      '@width' => $this->getSetting('width'),
    ]);
    $summary[] = $this->t('Height: @height px', [
      '@height' => $this->getSetting('height'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $entity = $this->fieldDefinition->getTargetEntityTypeId();
      $bundle = $this->fieldDefinition->getTargetBundle();
      $field_name = $this->fieldDefinition->getName();
      $field_type = $this->fieldDefinition->getType();
      $file_uri = $file->getFileUri();
      $filename = $file->getFileName();
      $uri_scheme = $this->fileSystem->uriScheme($file_uri);

      if ($uri_scheme == 'public') {
        $url = file_create_url($file->getFileUri());
        // Handle .ppt, .pptx, .doc, .docx, .xls, .xlsx extensions.
        $iframe_url = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $url;
        if (preg_match('/\.pdf$/i', $url)) {
          // This case the browser will be embed PDF (tested: chrome, firefox)
          $iframe_url = $url;
        }
        $elements[$delta] = [
          '#theme' => 'os_office_embed',
          '#iframe_url' => $iframe_url,
          '#filename' => $filename,
          '#width' => $this->getSetting('width'),
          '#height' => $this->getSetting('height'),
          '#delta' => $delta,
          '#entity' => $entity,
          '#bundle' => $bundle,
          '#field_name' => $field_name,
          '#field_type' => $field_type,
        ];

      }
      else {
        $message = $this->t('The file (%file) is not publicly accessible. It must be publicly available in order for the Office embed to be able to access it.',
          ['%file' => $filename]
        );
        $this->messenger()->addMessage($message, 'error', FALSE);
      }
    }

    return $elements;
  }

}
