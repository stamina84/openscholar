<?php

// @codingStandardsIgnoreStart
use Robo\Exception\TaskException;

/**
 * Base tasks for setting up a module to test within a full Drupal environment.
 *
 * This file expects to be called from the root of a Drupal site.
 *
 * @class RoboFile
 * @codeCoverageIgnore
 */
class RoboFile extends \Robo\Tasks
{

    /**
     * The database URL.
     */
    const DB_URL = 'mysql://drupal:drupal@mariadb/drupal';

    /**
     * The website's URL.
     */
    const DRUPAL_URL = 'http://drupal.docker.localhost:8000';

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();
    }

    /**
     * Command to run unit tests.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunUnitTests($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->runUnitTests($groups));
        return $collection->run();
    }

    /**
     * Runs unit tests and generates code coverage report.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunUnitTestsCodeCoverage($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTaskList($this->enableXDebug());
        $collection->addTaskList($this->runUnitTests($groups));
        return $collection->run();
    }

    /**
     * Command to check coding standards.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobCheckCodingStandards()
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->buildComposer());
        $collection->addTaskList($this->runCheckCodingStandards());
        return $collection->run();
    }

    /**
     * Command to run circular module dependency check.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobCheckModuleCircularDependency()
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->buildComposer());
        $collection->addTaskList($this->installDrupal());
        $collection->addTaskList($this->runCheckModuleCircularDependency());
        $collection->addTaskList($this->installTestConfigs());
        return $collection->run();
    }

    /**
     * Command to run kernel tests.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunKernelTests($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTaskList($this->runKernelTests($groups));
        return $collection->run();
    }

    /**
     * Runs kernel tests with code coverage report.
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunKernelTestsCodeCoverage($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTaskList($this->enableXDebug());
        $collection->addTaskList($this->runKernelTests($groups));
        return $collection->run();
    }

    /**
     * Command to run functional tests.
     *
     * @param string $groups
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunFunctionalTests($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTaskList($this->runFunctionalTests($groups));
        return $collection->run();
    }

    /**
     * Command to run functional javascript tests (headless).
     *
     * @param string $groups
     *
     * @return \Robo\Result
     *   The result of the collection of tasks.
     */
    public function jobRunFunctionalJavascriptTests($groups = '')
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTaskList($this->runFunctionalJavascriptTests($groups));
        return $collection->run();
    }

    /**
     * Command to run behat tests.
     *
     * @return \Robo\Result
     *   The result tof the collection of tasks.
     */
    public function jobRunBehatTests()
    {
        $collection = $this->collectionBuilder();
        $collection->addTaskList($this->downloadDatabase());
        $collection->addTaskList($this->buildDocker());
        $collection->addTaskList($this->importDB());
        $collection->addTask($this->waitForDrupal());
        $collection->addTaskList($this->runUpdatePath());
        $collection->addTaskList($this->runBehatTests());
        return $collection->run();
    }

    /**
     * Download's database to use within a Docker environment.
     *
     * This task assumes that there is an environment variable that contains a URL
     * that contains a database dump. Ideally, you should set up drush site
     * aliases and then replace this task by a drush sql-sync one. See the
     * README at lullabot/drupal8ci for further details.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function downloadDatabase()
    {
        $tasks = [];
        $tasks[] = $this->taskFilesystemStack()
            ->mkdir('mariadb-init');
        $tasks[] = $this->taskExec('wget ' . getenv('DB_DUMP_URL'))
            ->dir('mariadb-init');
        return $tasks;
    }

  /**
   * Creates the Docker environment.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function buildDocker()
  {
    $force = true;
    $tasks = [];
    $tasks[] = $this->taskFilesystemStack()
      ->copy('.travis/docker-compose.yml', 'docker-compose.yml', $force)
      ->copy('.travis/traefik.yml', 'traefik.yml', $force)
      ->copy('.travis/.env', '.env', $force)
      ->copy('.travis/config/behat.yml', 'tests/behat.yml', $force);

    $tasks[] = $this->taskExec('echo AWS_ACCESS_KEY_ID=' . getenv('ARTIFACTS_KEY') . ' >> .env');
    $tasks[] = $this->taskExec('echo AWS_SECRET_ACCESS_KEY=' . getenv('ARTIFACTS_SECRET') . ' >> .env');
    $tasks[] = $this->taskExec('echo AWS_ES_ACCESS_ENDPOINT=' . getenv('ARTIFACTS_ES_ENDPOINT') . ' >> .env');
    $tasks[] = $this->taskExec('docker-compose --verbose pull --parallel');
    $tasks[] = $this->taskExec('docker-compose up -d');

    return $tasks;
  }

  /**
   * Imports the database.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function importDB()
  {
    $force = true;
    $tasks = [];

    // Fix import issue.
    $tasks[] = $this->taskExec('docker-compose exec -T php composer install');
    // Import sql.
    $tasks[] = $this->taskExec('docker-compose exec -T php drush sqlq --file=./travis-backup.sql');

    return $tasks;
  }

  /**
   * Create sql dump and compressed build and upload to S3.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   A collection of tasks.
   */
  public function jobUploadToAws()
  {
    return $this
      ->collectionBuilder()
      ->addTask($this->taskExec('docker-compose exec -T php drush sql-dump --result-file=./travis-backup.sql'))
      ->addTask($this->taskExec('ls -la'))
      ->addTask($this->taskExec('tar -Jcf ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-web.tar.xz web'))
      ->addTask($this->taskExec('tar -Jcf ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-vendor.tar.xz vendor'))
      ->addTask($this->taskExec('tar -Jcf ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-custom_themes.tar.xz custom_themes'))
      ->addTask($this->taskExec('aws s3 cp ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-web.tar.xz s3://$ARTIFACTS_BUCKET/build_files/$TRAVIS_BUILD_NUMBER/os-build-${TRAVIS_BUILD_NUMBER}-web.tar.xz'))
      ->addTask($this->taskExec('aws s3 cp ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-vendor.tar.xz s3://$ARTIFACTS_BUCKET/build_files/$TRAVIS_BUILD_NUMBER/os-build-${TRAVIS_BUILD_NUMBER}-vendor.tar.xz'))
      ->addTask($this->taskExec('aws s3 cp ${TRAVIS_BUILD_DIR}-${TRAVIS_BUILD_NUMBER}-custom_themes.tar.xz s3://$ARTIFACTS_BUCKET/build_files/$TRAVIS_BUILD_NUMBER/os-build-${TRAVIS_BUILD_NUMBER}-custom_themes.tar.xz'))
      ;
  }
  /**
   * Extract from S3 and fix permissions.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   A collection of tasks.
   */
  public function jobExtractFromAws()
  {
    return $this
      ->collectionBuilder()
      ->addTask($this->taskExec('aws s3 sync s3://$ARTIFACTS_BUCKET/build_files/$TRAVIS_BUILD_NUMBER .'))
      ->addTask($this->taskExec('tar -Jxf os-build-${TRAVIS_BUILD_NUMBER}-web.tar.xz'))
      ->addTask($this->taskExec('tar -Jxf os-build-${TRAVIS_BUILD_NUMBER}-vendor.tar.xz'))
      ->addTask($this->taskExec('tar -Jxf os-build-${TRAVIS_BUILD_NUMBER}-custom_themes.tar.xz'))
      ->addTask($this->taskExec('chmod +x vendor/bin/phpunit'))
      ->addTask($this->taskExec('sudo chown -R 1000:1000 web'))
      ->addTask($this->taskExec('sudo chown -R 1000:1000 vendor'))
      ->addTask($this->taskExec('sudo chown -R 1000:1000 custom_themes'))
      ;
  }

  /**
   * Builds the Code Base.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
  protected function buildComposer()
  {
    $force = true;
    $tasks = [];

    $tasks[] = $this->taskExec('docker-compose exec -T php composer global require hirak/prestissimo');
    $tasks[] = $this->taskExec('make');
    $tasks[] = $this->taskExec('docker-compose exec -T php cp .travis/config/phpunit.xml web/core/phpunit.xml');
    $tasks[] = $this->taskExec('docker-compose exec -T php cp .travis/config//bootstrap.php web/core/tests/bootstrap.php');
    $tasks[] = $this->taskExec('docker-compose exec -T php mkdir -p web/sites/simpletest');

    return $tasks;
  }

    /**
     * Enables xdebug in the Docker environment.
     *
     * @return \Robo\Task\Base\Exec[]
     *   Array of tasks.
     */
    protected function enableXDebug()
    {
        $tasks[] = $this->taskExecStack()
            ->exec('echo PHP_XDEBUG_ENABLED=1 >> .env')
            ->exec('docker-compose up -d');
        return $tasks;
    }

    /**
     * Waits for Drupal to accept requests.
     *
     * @TODO Find an efficient way to wait for Drupal.
     *
     * @return \Robo\Task\Base\Exec
     *   A task to check that Drupal is ready.
     */
    protected function waitForDrupal()
    {
        return $this->taskExec('sleep 30s');
    }

    /**
     * Updates the database.
     *
     * We can't use the drush() method because this is running within a docker-compose
     * environment.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUpdatePath()
    {
        $tasks = [];
        $tasks[] = $this->taskExec('docker-compose exec -T php vendor/bin/drush --yes updatedb');
        $tasks[] = $this->taskExec('docker-compose exec -T php vendor/bin/drush --yes config-import');
        return $tasks;
    }

    /**
     * Install Drupal.
     *
     * @return \Robo\Task\Base\Exec[]
     *   A task to install Drupal.
     */
    protected function installDrupal()
    {
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php cp .travis/config/default.settings.php web/sites/default/default.settings.php')
            ->exec('docker-compose exec -T php ./vendor/bin/drush site-install openscholar -vvv -y --db-url=' . static::DB_URL . ' --existing-config --account-pass=admin')
            ->exec('docker-compose exec -T php ./vendor/bin/drush cr');

        return $tasks;
    }

    /**
     * Install test configurations.
     *
     * @return \Robo\Task\Base\Exec[]
     *   A task to install Drupal.
     */
    protected function installTestConfigs()
    {
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php mkdir -p web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/vsite/tests/modules/vsite_module_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r web/modules/contrib/group/tests/modules/group_test_config web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/custom/os_mailchimp/tests/modules/os_mailchimp_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/apps/os_publications/tests/modules/os_publications_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/cp/modules/cp_taxonomy/tests/modules/cp_taxonomy_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/cp/modules/cp_appearance/tests/modules/cp_appearance_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/modules/cp/modules/cp_import/tests/modules/cp_import_csv_test web/modules/test')
            ->exec('docker-compose exec -T php cp -r profile/tests/modules/os_test web/modules/test')
            ->exec('docker-compose exec -T php ./vendor/bin/drush en -y vsite_module_test group_test_config os_mailchimp_test os_publications_test cp_taxonomy_test cp_appearance_test cp_import_csv_test os_test');
        return $tasks;
    }

    /**
     * Starts the web server.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function startWebServer()
    {
        $tasks = [];
        $tasks[] = $this->taskExec('vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'drush --root=' . $this->getDocroot() . '/web runserver ' . static::DRUPAL_URL . ' &')
            ->silent(true);
        $tasks[] = $this->taskExec('until curl -s ' . static::DRUPAL_URL . '; do true; done > /dev/null');
        return $tasks;
    }

    /**
     * Run coding standard checks.
     *
     * @return \Robo\Task\Base\Exec[]
     *   List of tasks.
     */
    protected function runCheckCodingStandards()
    {
        $tasks[] = $this->taskExecStack()
            ->stopOnFail()
            ->exec('docker-compose exec -T php ./vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer')
            ->exec('docker-compose exec -T php ./vendor/bin/phpcs --standard=Drupal --warning-severity=0 --ignore=themes/*/css profile')
            ->exec('docker-compose exec -T php ./vendor/bin/phpcs --standard=DrupalPractice --warning-severity=0 --ignore=themes/*/css profile');

        return $tasks;
    }

    /**
     * Run unit tests.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runUnitTests($groups)
    {
        $groups = explode(',', $groups);
        $groups = array_filter($groups, 'trim');
        $groups[] = 'unit';
        $groups = implode(',', $groups);
        $tasks[] = $this->taskExecStack()
          ->exec('docker-compose exec -T php ./vendor/bin/phpunit ' .
              '-c web/core '.
              '--debug '.
              ($groups ? '--group ' . $groups . ' ': ' ')  .
              '--exclude-group=kernel,functional,functional-javascript,wip '.
              '--verbose web/profiles/contrib/openscholar');
        return $tasks;
    }

    /**
     * Runs circular module dependency check.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runCheckModuleCircularDependency()
    {
        $tasks[] = $this->taskExecStack()
          ->exec('docker-compose exec -T php ./vendor/bin/drush validate:module-dependencies');
        return $tasks;
    }

  /**
   * Run kernel tests.
   *
   * @return \Robo\Task\Base\Exec[]
   *   An array of tasks.
   */
    protected function runKernelTests($groups)
    {
        $groups = explode(',', $groups);
        $groups = array_filter($groups, 'trim');  // strip out empty lines
        $groups = implode(',', $groups);
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php ./vendor/bin/phpunit ' .
                '-c web/core '.
                '--debug '.
                ($groups ? '--group ' . $groups . ' ': ' ')  .
                '--exclude-group=unit,functional,functional-javascript,wip '.
                '--verbose web/profiles/contrib/openscholar');
        return $tasks;
    }

    /**
     * Run functional tests.
     *
     * @param string $groups
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runFunctionalTests($groups)
    {
        $groups = explode(',', $groups);
        $groups = array_filter($groups, 'trim');
        $groups = implode(',', $groups);
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php ./vendor/bin/phpunit ' .
                '-c web/core '.
                '--debug '.
                ($groups ? '--group ' . $groups . ' ': ' ')  .
                '--exclude-group=unit,kernel,functional-javascript,wip '.
                '--verbose web/profiles/contrib/openscholar');
        return $tasks;
    }

    /**
     * Run functional javascript tests (headless).
     *
     * @param string $groups
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runFunctionalJavascriptTests($groups)
    {
        $groups = explode(',', $groups);
        $groups = array_filter($groups, 'trim');
        $groups = implode(',', $groups);
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php ./vendor/bin/phpunit ' .
                '-c web/core '.
                '--debug '.
                ($groups ? '--group ' . $groups . ' ': ' ')  .
                '--exclude-group=unit,kernel,functional,wip '.
                '--verbose web/profiles/contrib/openscholar');
        return $tasks;
    }

    /**
     * Runs Behat tests.
     *
     * @return \Robo\Task\Base\Exec[]
     *   An array of tasks.
     */
    protected function runBehatTests()
    {
        $tasks = [];
        $tasks[] = $this->taskExecStack()
            ->exec('docker-compose exec -T php vendor/bin/behat --verbose -c tests/behat.yml');
        return $tasks;
    }

    /**
     * Return drush with default arguments.
     *
     * @return \Robo\Task\Base\Exec
     *   A drush exec command.
     */
    protected function drush()
    {
        // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . DIRECTORY_SEPARATOR. 'web';
        return $this->taskExec('vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'drush')
           ->option('root', $docroot, '=');
    }

    /**
     * Get the absolute path to the docroot.
     *
     * @return string
     *   The document root.
     */
    protected function getDocroot()
    {
        return (getcwd());
    }

}
