<?php

namespace Drupal\os_wysiwyg\Plugin\Filter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\os_wysiwyg\PurifyHtmlHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purify CKEDITOR HTML for xss vulnerabilities.
 *
 * @Filter(
 *   id = "purifyhtml",
 *   title = @Translation("Purify and Filter HTML"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class PurifyHtml extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Config variable.
   *
   * @var array
   */
  protected $purifierConfig;

  /**
   * Purify Html Helper.
   *
   * @var \Drupal\os_wysiwyg\PurifyHtmlHelper
   */
  protected $purifyHtmlHelper;

  /**
   * OsLinkFilter constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\os_wysiwyg\PurifyHtmlHelper $purify_html_helper
   *   Purify Html Helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurifyHtmlHelper $purify_html_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purifyHtmlHelper = $purify_html_helper;

    if (!empty($this->settings['purifyhtml_configuration'])) {
      $this->purifierConfig = $this->applyPurifierConfig($this->settings['purifyhtml_configuration']);
    }
    else {
      $this->purifierConfig = \HTMLPurifier_Config::createDefault();
    }

    // Set data-entity properties.
    $this->purifierConfig->set('HTML.DefinitionID', 'dataentity-definitions');
    $this->purifierConfig->set('HTML.DefinitionRev', 1);
    if ($def = $this->purifierConfig->maybeGetRawHTMLDefinition()) {
      $def->addElement('drupal-entity', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
        'data-entity-type' => 'Text',
        'data-caption' => 'Text',
        'data-embed-button' => 'Text',
        'data-entity-uuid' => 'Text',
        'data-entity-embed-display' => 'Text',
        'data-entity-embed-display-settings' => 'Text',
        'data-langcode' => 'Text',
        'data-view-mode' => 'Text',
        'title' => 'Text',
        'alt' => 'Text',
        'data-align' => 'Text',
        'class' => 'Text',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates this block class.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("os_wysiwyg.os_purifyhtml")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return $this->purifyHtmlHelper->getPurifyHtml($text, $this->purifierConfig);
  }

  /**
   * Applies the configuration to a HTMLPurifier_Config object.
   *
   * @param string $configuration
   *   Configuration values.
   *
   * @return \HTMLPurifier_Config
   *   Purifier config.
   */
  protected function applyPurifierConfig($configuration) {
    /** @var \HTMLPurifier_Config $purifier_config */
    $purifier_config = \HTMLPurifier_Config::createDefault();

    $settings = Yaml::decode($configuration);

    foreach ($settings as $namespace => $directives) {
      if (is_array($directives)) {
        foreach ($directives as $key => $value) {
          $purifier_config->set("$namespace.$key", $value);
        }
      }
      else {
        $this->configErrors[] = 'Invalid value for namespace $namespace, must be an array of directives.';
      }
    }

    return $purifier_config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if (empty($this->settings['purifyhtml_configuration'])) {
      $purifier_config = \HTMLPurifier_Config::createDefault();
      $default_value = Yaml::encode($purifier_config->getAll());
    }
    else {
      $default_value = $this->settings['purifyhtml_configuration'];
    }

    $form['purifyhtml_configuration'] = [
      '#type' => 'textarea',
      '#rows' => 50,
      '#title' => $this->t('Purify HTML Configuration'),
      '#description' => $this->t('These are the config directives in YAML format, according to the <a href="@url">HTML Purifier documentation</a>', ['@url' => 'http://htmlpurifier.org/live/configdoc/plain.html']),
      '#default_value' => $default_value,
      '#element_validate' => [
        [$this, 'settingsFormConfigurationValidate'],
      ],
    ];

    return $form;
  }

  /**
   * Settings form validation callback for htmlpurifier_configuration element.
   *
   * @param mixed $element
   *   Element validation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function settingsFormConfigurationValidate($element, FormStateInterface $form_state) {
    $values = $form_state->getValue('filters');
    if (isset($values['purifyhtml']['settings']['purifyhtml_configuration'])) {
      $this->configErrors = [];

      // HTMLPurifier library uses triger_error() for not valid settings.
      set_error_handler([$this, 'configErrorHandler']);
      try {
        $this->applyPurifierConfig($values['purifyhtml']['settings']['purifyhtml_configuration']);
      }
      catch (\Exception $ex) {
        // This could be a malformed YAML or any other exception.
        $form_state->setError($element, $ex->getMessage());
      }
      restore_error_handler();

      if (!empty($this->configErrors)) {
        foreach ($this->configErrors as $error) {
          $form_state->setError($element, $error);
        }
        $this->configErrors = [];
      }
    }
  }

  /**
   * Custom error handler to manage invalid purifier configuration assignments.
   *
   * @param mixed $errno
   *   Error number.
   * @param string $errstr
   *   Error String.
   */
  public function configErrorHandler($errno, $errstr) {
    // Do not set a validation error if the error is about a deprecated use.
    if ($errno < E_DEPRECATED) {
      // \HTMLPurifier_Config::triggerError() adds ' invoked on line ...' to the
      // error message. Remove that part from our validation error message.
      $needle = 'invoked on line';
      $pos = strpos($errstr, $needle);
      if ($pos !== FALSE) {
        $message = substr($errstr, 0, $pos - 1);
        $this->configErrors[] = $message;
      }
      else {
        $this->configErrors[] = 'HTMLPurifier configuration is not valid. Error: ' . $errstr;
      }
    }
  }

}
