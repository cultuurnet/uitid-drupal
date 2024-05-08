<?php

namespace Drupal\uitid\Auth0;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Contract\StoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\uitid\Form\UitIdSettingsForm;

/**
 * Provides an auth0 client with the configured credentials.
 */
class DrupalAuth0ClientFactory {

  /**
   * Get a new auth0 client.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param StoreInterface $sessionStorage
   *   The session storage.
   *
   * @return Auth0
   *   The auth0 client.
   */
  public static function getClient(ConfigFactoryInterface $configFactory, StoreInterface $sessionStorage): Auth0 {
    $config = $configFactory->get(UitIdSettingsForm::CONFIG_NAME);

    return new Auth0(new SdkConfiguration(
      domain: trim($config->get('host') ?? ''),
      clientId: $config->get('client_id') ?? '',
      redirectUri: Url::fromRoute('uitid.authorize', [], ['absolute' => TRUE])->toString(),
      clientSecret: $config->get('secret') ?? '',
      sessionStorage: $sessionStorage,
      cookieSecret: $config->get('cookie_secret') ?? '',
    ));
  }

}
