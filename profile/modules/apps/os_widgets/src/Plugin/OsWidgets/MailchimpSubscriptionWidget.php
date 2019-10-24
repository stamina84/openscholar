<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class MailchimpSubscriptionWidget.
 *
 * @OsWidget(
 *   id = "mailchimp_subscription_widget",
 *   title = @Translation("Mailchimp Subscription")
 * )
 */
class MailchimpSubscriptionWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactoryInterface $config_factory, FormBuilder $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->configFactory = $config_factory;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('entity_type.manager'),
    $container->get('database'),
    $container->get('config.factory'),
    $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $field_display = $block_content->get('field_display')->getValue();
    $display_style = $field_display[0]['value'] ?? '';

    $mailing_list = $block_content->get('field_mailing_list')->getValue();
    $list_id = $mailing_list[0]['value'] ?? '';

    $title = $block_content->get('field_widget_title')->getValue();
    $widget_title = $title[0]['value'] ?? '';

    $text = $block_content->get('field_subscribe_text')->getValue();
    $subscribe_text = $text[0]['value'] ?? '';

    switch ($display_style) {
      case 'link':
        $link_url = (!empty($list_id)) ? Url::fromRoute('os_mailchimp.modal.subscribe', ['list_id' => $list_id]) : '';
        $build['mailchimp_subscription'] = [
          '#theme' => 'os_widgets_mailchimp_link',
          '#widget_title' => $widget_title,
          '#subscribe_text' => $subscribe_text,
          '#link_url' => $link_url,
        ];
        $build['mailchimp_subscription']['#attached']['library'][] = 'core/drupal.dialog.ajax';
        break;

      case 'basic_form':
        $builtForm = [];
        if (!empty($list_id)) {
          $list = mailchimp_get_list($list_id);
          $builtForm = $this->formBuilder->getForm('\Drupal\os_mailchimp\Form\OsMailChimpSignupForm', $list);
        }
        $build['form'] = $builtForm;
        $build['form']['#attached']['library'][] = 'os_widgets/mailchimpWidget';
        break;

      case 'mailchimp_form':
        $mailchimp_config = $this->configFactory->get('mailchimp.settings');
        $mailchimp_code = $mailchimp_config->get('mailchimp_code');
        $build['mailchimp'] = [
          '#children' => $mailchimp_code,
        ];
        break;

    }

  }

}
