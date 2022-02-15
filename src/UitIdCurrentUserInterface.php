<?php

namespace Drupal\uitid;

/**
 * Defines an interface for fetching "UiTiD" user information.
 */
interface UitIdCurrentUserInterface {

  /**
   * Check if the current user is an "UiTID" user.
   *
   * @return bool
   *   Boolean indicating if the user is a "UiTID" user or not.
   */
  public function isUitIdUser(): bool;

  /**
   * Get the user's Id.
   *
   * @return string|null
   *   The user's id.
   */
  public function getUserId(): ?string;

  /**
   * Get the user
   *
   * @return array|null
   *   The user information or null
   */
  public function getUser(): ?array;

  /**
   * Get the name of current user.
   *
   * @return string
   *   The name of current user.
   */
  public function getName(): ?string;

}
