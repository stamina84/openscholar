<?php

namespace Drupal\os_events\Plugin\App;

use Drupal\Core\Entity\EntityTypeManager;
use ICal\ICal;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\cp_import\AppImportFactory;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\Core\File\FileSystem;
use Drupal\vsite\Plugin\AppPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Events app.
 *
 * @App(
 *   title = @Translation("Event"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *     "events",
 *   },
 *   specialBundle = {
 *     "upcoming_events",
 *   },
 *   viewsTabs = {
 *     "calendar" = {
 *       "page_1",
 *     },
 *     "past_events_calendar" = {
 *       "page",
 *     },
 *     "upcoming_calendar" = {
 *       "page",
 *     },
 *   },
 *   id = "event",
 *   contextualRoute = "view.upcoming_calendar.page",
 *   cpImportId = "os_events_import",
 *   cpImportFilePath = "public://importcsv/os_event.csv"
 * )
 */
class EventsApp extends AppPluginBase {
  /**
   * App Import factory service.
   *
   * @var \Drupal\cp_import\AppImportFactory
   */
  protected $appImportFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManager $migrationPluginManager, CpImportHelper $cpImportHelper, Messenger $messenger, EntityTypeManager $entityTypeManager, AppImportFactory $appImportFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migrationPluginManager, $cpImportHelper, $messenger, $entityTypeManager);
    $this->appImportFactory = $appImportFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('cp_import.helper'),
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('app_import_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getImportForm(array $form, $type) : array {

    $form['format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Format'),
      '#options' => ['csv' => $this->t('CSV'), 'ical' => $this->t('iCal')],
      '#required' => TRUE,
      '#default_value' => 'csv',
    ];

    $form['template_container'] = [
      '#type' => 'container',
      '#attributes' => [],
      '#states' => [
        'visible' => [
          ':input[name="format"]' => ['value' => 'csv'],
        ],
      ],
    ];

    $form['template_container']['template'] = [
      '#type' => 'link',
      '#title' => $this->t('Download a template'),
      '#url' => Url::fromRoute('cp_import.content.download_template', ['app_name' => $type]),
    ];

    $form['ical_container'] = [
      '#type' => 'container',
      '#attributes' => [],
      '#states' => [
        'visible' => [
          ':input[name="format"]' => ['value' => 'ical'],
        ],
      ],
    ];

    $form['ical_container']['template_ical'] = [
      '#type' => 'link',
      '#title' => $this->t('Download a template'),
      '#url' => Url::fromRoute('cp_import.content.download_ical', ['app_name' => $type]),
    ];

    $form['import_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Import File'),
      '#description' => $this->t('Note: Import files with more than @rowLimit rows are not permitted. Try creating multiple import files in 100 row increments.', ['@rowLimit' => CpImportHelper::CSV_ROW_LIMIT]),
      '#upload_location' => 'public://importcsv/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv ics ical'],
      ],
      'visible' => [
        ':input[name="format"]' => ['value' => 'csv'],
      ],
    ];

    $params = [
      '@wikipedia-url' => 'http://en.wikipedia.org/wiki/Character_encoding',
    ];

    $form['app_id'] = [
      '#type' => 'hidden',
      '#value' => $type,
    ];

    $form['encoding'] = [
      '#title' => $this->t('Encoding'),
      '#type' => 'select',
      '#description' => $this->t('Select the encoding of your file. For a full list of encodings you can visit <a href="@wikipedia-url">this</a> Wikipedia page.', $params),
      '#default_value' => 'UTF-8',
      '#options' => [
        'utf-8' => $this->t('UTF-8'),
        'utf-16' => $this->t('UTF-16'),
        'utf-32' => $this->t('UTF-32'),
        'MS-Windows character sets' => [
          'Windows-1250' => $this->t('Central European languages that use Latin script'),
          'Windows-1251' => $this->t('Cyrillic alphabets'),
          'Windows-1252' => $this->t('Western languages'),
          'Windows-1253' => $this->t('Greek'),
          'Windows-1254' => $this->t('Turkish'),
          'WINDOWS-1255' => $this->t('Hebrew'),
          'Windows-1256' => $this->t('Arabic'),
          'Windows-1257' => $this->t('Baltic languages'),
          'Windows-1258' => $this->t('Vietnamese'),
        ],
        'ISO 8859' => [
          'ISO-8859-1' => $this->t('Western Europe'),
          'ISO-8859-2' => $this->t('Western and Central Europe'),
          'ISO-8859-9' => $this->t('Western Europe with amended Turkish character set'),
          'ISO-8859-14' => $this->t('Celtic languages (Irish Gaelic, Scottish, Welsh)'),
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Validates import file and shows errors row wise.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Form State.
   */
  public function validateImportSource(array $form, FormStateInterface $formState) {

    $format = $formState->getValue('format');
    if ($format == 'ical') {
      $data = $this->validateIcalSource($form, $formState);
    }
    if ($format == 'csv') {
      $data = parent::validateImportSource($form, $formState);
      if (!$data) {
        return;
      }

      $definition = $this->getPluginDefinition();
      $eventImport = $this->appImportFactory->create($definition['cpImportId']);

      // Validate Headers.
      if ($missing = $eventImport->validateHeaders($data)) {
        $headerMessage = $this->t('The following Header/columns are missing:<ul> @Title @Body @Start date @End date @Location @Registration @Files @Created date @Path</ul></br> The structure of your CSV file probably needs to be updated. Please download the template again.', $missing);
        $this->messenger->addError($headerMessage);
        $formState->setError($form['import_file']);
        return;
      }

      // Validate rows.
      if ($message = $eventImport->validateRows($data)) {
        $formState->setError($form['import_file']);
        $this->messenger->addError($this->t('@title @file @date @start_date @end_date', $message));
      }
    }
  }

  /**
   * Validates ical file and shows errors row wise.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Form State.
   */
  public function validateIcalSource(array $form, FormStateInterface $formState) {
    $triggerElement = $formState->getTriggeringElement();
    if ($triggerElement['#name'] === 'import_file_remove_button' || $triggerElement['#name'] === 'import_file_upload_button') {
      return FALSE;
    }

    $fileId = $formState->getValue('import_file');
    $fileId = array_shift($fileId);

    /** @var \Drupal\file\Entity\File|NULL $file */
    $file = $fileId ? File::load($fileId) : NULL;

    if (!$file) {
      $formState->setError($form['import_file'], $this->t('File not found'));
      return FALSE;
    }
    $errors = file_validate_extensions($file, 'ical ics');
    if ($errors) {
      $formState->setError($form['import_file'], 'Invalid File extension');
      return FALSE;
    }

    // Check if events are present.
    $uri = $file->getFileUri();
    $ical = new ICal($uri);
    $events = $ical->events();
    if (empty($events)) {
      $formState->setError($form['import_file'], $this->t('No Events found'));
      return FALSE;
    }

    // Check if all valid keys are present.
    $header = ['summary', 'description', 'dtstart', 'dtend', 'location', 'uid'];
    $hasError = FALSE;
    foreach ($header as $col) {
      $row = '';
      foreach ($events as $key => $event) {
        if (!$event->{$col}) {
          $row .= ($key + 1) . ', ';
        }
      }
      if ($row) {
        $row = rtrim($row, ', ');
        $msg['@' . $col] = $this->t('@col missing for row(s) @row </br>', ['@col' => ucfirst($col), '@row' => $row]);
        $hasError = TRUE;
      }
      else {
        $msg['@' . $col] = '';
      }
    }
    if ($hasError) {
      $formState->setError($form['import_file'], $this->t('@summary @description @dtstart @dtend @location @uid', $msg));
      return FALSE;
    }

  }

  /**
   * Submit import form and execute migration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   */
  public function submitImportForm(FormStateInterface $form_state) {
    $format = $form_state->getValue('format');

    $fileId = $form_state->getValue('import_file');
    $fileId = array_shift($fileId);
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fileId);
    if ($format == 'ical') {
      $import_file_path = 'public://importcsv/os_event.ical';
      $import_id = 'os_events_ical_import';
    }
    else {
      $definition = $this->getPluginDefinition();
      $import_file_path = $definition['cpImportFilePath'];
      $import_id = $definition['cpImportId'];
    }
    // Replace existing source file.
    file_move($file, $import_file_path, FileSystem::EXISTS_REPLACE);
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance($import_id);
    $executable = new MigrateBatchExecutable($migration, new MigrateMessage());
    $executable->batchImport();
  }

}
