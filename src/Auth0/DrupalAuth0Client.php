<?php

namespace Drupal\uitid\Auth0;

use Auth0\SDK\Auth0;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\uitid\Form\UitIdSettingsForm;

/**
 * Provides an auth0 client with the configured credentials.
 */
class DrupalAuth0Client extends Auth0 {

  use StringTranslationTrait;

  /**
   * The Auth0 config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Construct the auth0 client.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->config = $configFactory->get(UitIdSettingsForm::CONFIG_NAME);

    if (empty($this->getHost()) || empty($this->getClientId()) || empty($this->getClientSecret())) {
      $messenger->addError($this->t('The Auth0 client has not been correctly configured.'));
      return;
    }

    parent::__construct([
      'domain' => $this->getHost(),
      'client_id' => $this->getClientId(),
      'client_secret' => $this->getClientSecret(),
      'redirect_uri' => Url::fromRoute('uitid.authorize', [], ['absolute' => TRUE])->toString(),
    ]);
  }

  /**
   * Gets the host.
   *
   * @return string
   *   The host.
   */
  protected function getHost(): string {
    $host = $this->config->get('host') ?? '';
    if (empty($host)) {
      return $host;
    }

    return trim($host, '/');
  }

  /**
   * Gets the client Id.
   *
   * @return string
   *   The client Id.
   */
  protected function getClientId(): string {
    return $this->config->get('client_id') ?? '';
  }

  /**
   * Gets the secret.
   *
   * @return string
   *   The secret.
   */
  protected function getClientSecret(): string {
    return $this->config->get('secret') ?? '';
  }

}
