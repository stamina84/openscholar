<?php

namespace Drupal\Tests\cp_import\ExistingSite;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cp_import\AppImport\os_events_import\AppImport;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class CpImportEventTest.
 *
 * @group kernel
 * @group cp-1
 */
class CpImportEventTest extends OsExistingSiteTestBase {

  /**
   * CpImport helper service.
   *
   * @var \Drupal\cp_import\Helper\CpImportHelper
   */
  protected $cpImportHelper;

  /**
   * Migration manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Cp Import access checker.
   *
   * @var \Drupal\cp_import\Access\CpImportAccessCheck
   */
  protected $cpImportAccessChecker;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Group member.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->cpImportHelper = $this->container->get('cp_import.helper');
    $this->migrationManager = $this->container->get('plugin.manager.migration');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->fileSystem = $this->container->get('file_system');
    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember);
    $this->drupalLogin($this->groupMember);
  }

  /**
   * Tests CpImport Events AppImport factory.
   */
  public function testCpImportEventsAppImportFactory() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_events_import');
    $this->assertInstanceOf(AppImport::class, $instance);
  }

  /**
   * Tests CpImport Events header validations.
   */
  public function testCpImportEventsHeaderValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_events_import');

    // Test header errors.
    $data[0] = [
      'Title' => 'Test Event',
      'Body' => 'Event Body',
      'Start date' => '2020-02-02',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message['@Title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@End date']);

    // Test No header errors.
    $data[0] = [
      'Title' => 'Test Event',
      'Body' => 'Event Body',
      'Start date' => '2020-02-02',
      'End date' => '2020-02-07',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateHeaders($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests CpImport Events row validations.
   */
  public function testCpImportEventsRowValidation() {
    /** @var \Drupal\cp_import\AppImportFactory $appImportFactory */
    $appImportFactory = $this->container->get('app_import_factory');
    $instance = $appImportFactory->create('os_events_import');

    // Test Title errors.
    $data[0] = [
      'Title' => '',
      'Body' => 'Event Body',
      'Start date' => '2020-02-02',
      'End date' => '2020-02-07',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@date']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@title']);

    // Start date error.
    $data[0] = [
      'Title' => 'Event Title',
      'Body' => 'Event Body',
      'Start date' => '2020-02-35',
      'End date' => '2020-02-07',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@start_date']);

    // End date error.
    $data[0] = [
      'Title' => 'Event Title',
      'Body' => 'Event Body',
      'Start date' => '2020-02-01',
      'End date' => '2020-23-01',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message['@title']);
    $this->assertInstanceOf(TranslatableMarkup::class, $message['@end_date']);

    // Test Y-m-d H:i A date format.
    $data[0] = [
      'Title' => 'Test Event',
      'Body' => 'Event Body',
      'Start date' => '2020-02-02 01:05 AM',
      'End date' => '2020-02-07 02:00 PM',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);

    // Test Y-m-d date format.
    $data[0] = [
      'Title' => 'Test Event',
      'Body' => 'Event Body',
      'Start date' => '2020-02-02',
      'End date' => '2020-02-07',
      'Location' => 'Boston',
      'Registration' => 'On',
      'Files' => 'https://www.harvard.edu/sites/default/files/content/Review_Committee_Report_20181113.pdf',
      'Created date' => '01/01/2020',
      'Path' => 'events-1',
    ];
    $message = $instance->validateRows($data);
    $this->assertEmpty($message);
  }

  /**
   * Tests Migration/import for os_events_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpImportMigrationEvent() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/event.csv';
    $this->cpImportHelper->csvToArray($filename, 'utf-8');
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_event.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_event.csv', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 1']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Event 6']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_events_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 1']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Event 6']);
    $this->assertCount(1, $node2);

    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/event_small.csv';
    $this->cpImportHelper->csvToArray($filename2, 'utf-8');
    $this->fileSystem->delete($path . '/os_event.csv');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_event.csv', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_events_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 1']);
    $this->assertCount(2, $node1);

    // Delete all the test data created.
    $executable->rollback();
  }

  /**
   * Tests Migration/import for os_events_ical_import.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCpIcalMigrationEvent() {
    $filename = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/event.ical';
    // Replace existing source file.
    $path = 'public://importcsv';
    $this->fileSystem->delete($path . '/os_event.ical');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename), $path . '/os_event.ical', FileSystemInterface::EXISTS_REPLACE);

    $storage = $this->entityTypeManager->getStorage('node');
    // Test Negative case.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 001']);
    $this->assertCount(0, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Event 002']);
    $this->assertCount(0, $node2);

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationManager->createInstance('os_events_ical_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    // Test positive case.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 001']);
    $this->assertCount(1, $node1);
    $node2 = $storage->loadByProperties(['title' => 'Test Event 002']);
    $this->assertCount(1, $node2);

    // Import same content again to check if adding timestamp works.
    $filename2 = drupal_get_path('module', 'cp_import_csv_test') . '/artifacts/event_small.ical';
    $this->fileSystem->delete($path . '/os_event.ical');
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
    file_save_data(file_get_contents($filename2), $path . '/os_event.ical', FileSystemInterface::EXISTS_REPLACE);
    $migration = $this->migrationManager->createInstance('os_events_ical_import');
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    // If count is now 2 it means same content is imported again.
    $node1 = $storage->loadByProperties(['title' => 'Test Event 001']);
    $this->assertCount(2, $node1);

    // Delete all the test data created.
    $executable->rollback();
  }

}
