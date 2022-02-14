<?php

namespace Drupal\uitid\Auth0;

use Auth0\SDK\Auth0;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides an auth0 client with the configured credentials.
 */
class DrupalAuth0Client extends Auth0 {

  /**
   * Construct the auth0 client.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    parent::__construct([
      'domain' => 'account-acc.uitid.be',
      'client_id' => '',
      'client_secret' => '',
      'redirect_uri' => 'https://cultuurkuur.dev.intracto.com/uitid/authorize'
    ]);
  }

}
