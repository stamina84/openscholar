<?php

namespace Drupal\os_faq\Plugin\App;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\cp_import\AppImportFactory;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\file\Entity\File;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\vsite\Plugin\AppPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FAQ app.
 *
 * @App(
 *   title = @Translation("FAQ"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *    "faq"
 *   },
 *   viewsTabs = {
 *     "os_faq" = {
 *       "page_1",
 *     },
 *   },
 *   id = "faq",
 *   contextualRoute = "view.os_faq.page_1",
 *   cpImportId = "os_faq_import",
 *   cpImportFilePath = "public://importcsv/os_faq.csv"
 * )
 */
class FAQApp extends AppPluginBase {

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * App Import factory service.
   *
   * @var \Drupal\cp_import\AppImportFactory
   */
  protected $appImportFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManager $migrationPluginManager, CpImportHelper $cpImportHelper, Messenger $messenger, AppImportFactory $appImportFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migrationPluginManager, $cpImportHelper);
    $this->messenger = $messenger;
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
      $container->get('app_import_factory')
    );
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
    if (!parent::validateImportSource($form, $formState)) {
      return;
    }

    $fileId = $formState->getValue('import_file');
    $fileId = array_shift($fileId);

    $encoding = $formState->getValue('encoding');

    /** @var \Drupal\file\Entity\File|NULL $file */
    $file = $fileId ? File::load($fileId) : NULL;

    if (!$file) {
      $formState->setError($form['import_file'], $this->t('File not found'));
      return;
    }

    $data = $this->cpImportHelper->csvToArray($file->getFileUri(), $encoding);

    if (!$data) {
      $formState->setError($form['import_file'], $this->t('Data could not be read from the csv , The structure of your CSV file probably needs to be updated. Please download the template again.'));
      return;
    }

    $definition = $this->getPluginDefinition();
    $faqImport = $this->appImportFactory->create($definition['cpImportId']);

    // Validate Headers.
    if ($missing = $faqImport->validateHeaders($data)) {
      $headerMessage = $this->t('The following Header/columns are missing:<ul> @Title @Body @Files @Created date @Path</ul></br> The structure of your CSV file probably needs to be updated. Please download the template again.', $missing);
      $this->messenger->addError($headerMessage);
      $formState->setError($form['import_file']);
      return;
    }

    // Validate rows.
    if ($message = $faqImport->validateRows($data)) {
      $formState->setError($form['import_file']);
      $this->messenger->addError($this->t('@title @file @date', $message));
    }
  }

}
