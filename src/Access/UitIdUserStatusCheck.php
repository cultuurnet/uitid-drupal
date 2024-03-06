<?php

namespace Drupal\uitid\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\uitid\UitIdCurrentUserInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on login status of current user.
 */
class UitIdUserStatusCheck implements AccessInterface {

  /**
   * The UiTiD current user.
   *
   * @var \Drupal\uitid\UitIdCurrentUserInterface
   */
  protected $uitIdCurrentUser;

  /**
   * UitidUserStatusCheck constructor.
   *
   * @param \Drupal\uitid\UitIdCurrentUserInterface $uitIdCurrentUser
   *   The UiTiD current user.
   */
  public function __construct(UitIdCurrentUserInterface $uitIdCurrentUser) {
    $this->uitIdCurrentUser = $uitIdCurrentUser;
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route) {
    $required = filter_var($route->getRequirement('_is_uitid_user'), FILTER_VALIDATE_BOOLEAN);
    $actual = $this->uitIdCurrentUser->isUitIdUser();
    $result = AccessResult::allowedIf($required === $actual)->addCacheContexts(['user.roles:authenticated']);

    if (!$result->isAllowed()) {
      $result->setReason($required === TRUE ? 'This route can only be accessed by UiTiD users.' : 'This route can only be accessed by non-UiTiD users.');
    }

    return $result;
  }

}
