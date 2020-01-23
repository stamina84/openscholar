<?php

namespace Drupal\Tests\vsite_preset\ExistingSite;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\group\Entity\Group;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use GuzzleHttp\RequestOptions;

/**
 * Class VsitePresetGroupApiTest.
 *
 * @group vsite
 * @group functional
 *
 * @package Drupal\Tests\vsite_preset\ExistingSite
 */
class VsitePresetGroupApiTest extends OsExistingSiteTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * Client for http requests.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * User account for authentication.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')->fromOptions(['base_uri' => $this->baseUrl]);
    $this->account = $this->createUser();
  }

  /**
   * Tests Group creation works and batch gets set for default content creation.
   */
  public function testPersonalGroupCreationAndBatchSet() {

    $fields = [
      'owner' => $this->account->id(),
      'label' => 'test-alias',
      'type' => 'personal',
      'purl' => 'test-alias',
      'preset' => 'personal',
      'theme' => 'default',
      'privacy' => 'public',
    ];

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
    $request_options[RequestOptions::BODY] = Json::encode($fields);;
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('POST'));

    $url = '/api/group?_format=json';
    $response = $this->httpClient->request('POST', $url, $request_options);
    $group_data = Json::decode($response->getBody()->getContents());
    $id = $group_data['id'][0]['value'];

    // Assert a group with the id in response is actually created.
    $this->assertNotNull(Group::load($id));

    // Assert that batch url is set for personal group personal preset.
    $headers = $response->getHeaders();
    $this->assertNotEmpty($headers['X-Drupal-Batch-Url'][0]);

  }

}
