<?php

namespace Drupal\os_mailchimp\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\os_mailchimp\OsMailchimpLists;

/**
 * Provides a block with mailchimp subscribe.
 *
 * @Block(
 *   id = "os_mailchimp_subscribe",
 *   admin_label = @Translation("Mailchimp subscribe"),
 * )
 */
class OsMailChimpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mailchimp Lists service.
   *
   * @var \Drupal\os_mailchimp\OsMailchimpLists
   */
  protected $osMailchimpLists;

  /**
   * Constructs OsMailChimpBlock.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\os_mailchimp\OsMailchimpLists $osMailchimpLists
   *   Service to get mailchimp Lists.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, OsMailchimpLists $osMailchimpLists) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->osMailchimpLists = $osMailchimpLists;
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
      $container->get('config.factory'),
      $container->get('os_mailchimp.lists')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (empty($config['list_id'])) {
      // Debug info for html markup.
      return [
        '#type' => 'markup',
        '#markup' => 'List ID is not configured!',
      ];
    }

    $link_url = Url::fromRoute('os_mailchimp.modal.subscribe', ['list_id' => $config['list_id']]);
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
      ],
    ]);

    return [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Subscribe to list!'), $link_url)->toString(),
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $lists = [];

    $mailchimp_config = $this->configFactory->get('mailchimp.settings');
    $api_key = $mailchimp_config->get('api_key');
    if (!empty($api_key)) {
      $lists = $this->osMailchimpLists->osMailchimpGetLists($api_key);
    }
    else {
      $form['empty_list_markup'] = [
        '#markup' => '<h3>' . $this->t('Please configure Mailchimp API key to configure this block.') . '</h3>',
      ];
    }

    $form['list_id'] = [
      '#type' => 'select',
      '#title' => $this->t('List to subscribe'),
      '#options' => $this->osMailchimpLists->mailChimpListsToOptions($lists),
      '#default_value' => $config['list_id'] ?? '',
      '#required' => TRUE,
    ];

    $form['subscribe_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subscribe Text'),
      '#description' => $this->t('Text for subscribe link. Default: Subscribe to our mailing list.'),
      '#default_value' => $config['subscribe_text'] ?? $this->t('Subscribe to our mailing list'),
    ];

    $form['display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display'),
      '#options' => [
        'link' => $this->t('Link'),
        'basic_form' => $this->t('Basic form'),
        'mailchimp_form' => $this->t('Mailchimp form'),
      ],
      '#description' => $this->t('Show a link to subscription popup, simple form, or an advanced form customizable at mailchimp.com'),
      '#default_value' => $config['display'] ?? 'link',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $configuration['list_id'] = $form_state->getValue('list_id');
    $configuration['subscribe_text'] = $form_state->getValue('subscribe_text');
    $configuration['display'] = $form_state->getValue('display');

    $this->setConfiguration($configuration);
  }

}
