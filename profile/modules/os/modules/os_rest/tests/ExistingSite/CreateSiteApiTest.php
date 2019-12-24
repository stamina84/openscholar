<?php

namespace Drupal\Tests\os_rest\ExistingSite;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Ensures that Create site API is indeed working.
 *
 * @covers \Drupal\os_rest\Plugin\rest\resource\OsGroupExtrasResource
 * @group functional
 * @group os
 */
class CreateSiteApiTest extends OsExistingSiteTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->account = $this->createUser();
    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);
  }

  /**
   * Calling group create API and comparing result.
   *
   * @covers \Drupal\os_rest\Plugin\rest\resource\OsGroupExtrasResource::get
   */
  public function testGroupCreateApi() {
    $url = '/api/group/validate/url/heler?_format=json';
    $request_options = $this->getAuthenticationRequestOptions('GET');
    $response = $this->httpClient->request('GET', $url, $request_options);
    $response_code = $response->getStatusCode();

    $this->assertEquals(200, $response_code);
  }

}
