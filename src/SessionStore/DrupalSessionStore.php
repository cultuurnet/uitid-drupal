<?php

namespace Drupal\uitid\SessionStore;

use Auth0\SDK\Contract\StoreInterface;
use Auth0\SDK\Utility\Toolkit;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Stores Auth0 session information in the Drupal session.
 */
class DrupalSessionStore implements StoreInterface {

  protected string $sessionPrefix = 'auth0';

  /**
   * Constructs a new DrupalSessionStore.
   *
   * @param SessionInterface $session
   *   The session.
   */
  public function __construct(protected SessionInterface $session) {
  }

  /**
   * {@inheritDoc}
   */
  public function defer(bool $deferring): void {
    // Do nothing.
  }

  /**
   * {@inheritDoc}
   */
  public function delete(string $key,): void {
    $keyName = $this->getSessionName($key);
    $this->session->remove($keyName);
  }

  /**
   * {@inheritDoc}
   */
  public function get(string $key, $default = null) {
    $keyName = $this->getSessionName($key);
    return $this->session->get($keyName, $default);
  }

  /**
   * {@inheritDoc}
   */
  public function purge(): void {
    $all = $this->session->all();
    $prefix = $this->sessionPrefix . '_';

    if ([] !== $all) {
      while ($sessionKey = \key($all)) {
        if (\mb_substr($sessionKey, 0, \mb_strlen($prefix)) === $prefix) {
          $this->delete($sessionKey);
        }

        \next($all);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function set(string $key, $value): void {
    $keyName = $this->getSessionName($key);
    $this->session->set($keyName, $value);
  }

  /**
   * Constructs a session key name.
   *
   * @param string $key session key name to prefix and return
   *
   * @return string
   *   The session key name.
   */
  public function getSessionName(string $key): string {
    [$key] = Toolkit::filter([$key])->string()->trim();
    return $this->sessionPrefix . '_' . ($key ?? '');
  }
}
