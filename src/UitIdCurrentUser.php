<?php

namespace Drupal\uitid;

use Auth0\SDK\Auth0;

/**
 * Intermediate service for fetching UiTiD user information.
 */
class UitIdCurrentUser implements UitIdCurrentUserInterface {

  protected $auth0Client;

  public function __construct(Auth0 $auth0Client) {
    $this->auth0Client = $auth0Client;
  }

  /**
   * {@inheritdoc}
   */
  public function isUitIdUser(): bool {
    return !\is_null($this->getUserId());
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId(): ?string {
    $userInfo = $this->auth0Client->getUser();
    return \is_array($userInfo) ? $userInfo['sub'] ?? null : $userInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(): ?array {
    return $this->auth0Client->getUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): ?string {
    $user = $this->getUser();
    return $user['nickname'] ?? null;
  }

}
