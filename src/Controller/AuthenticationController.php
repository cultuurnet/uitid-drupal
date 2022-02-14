<?php

namespace Drupal\uitid\Controller;

use Auth0\SDK\Auth0;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\externalauth\ExternalAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an authentication controller for the uitid login.
 */
class AuthenticationController extends ControllerBase {

  /**
   * The auth0 client.
   *
   * @var \Auth0\SDK\Auth0
   */
  protected $auth0Client;

  /**
   * The drupal external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * @param \Auth0\SDK\Auth0 $auth0Client
   *   The auth0 client.
   */
  public function __construct(Auth0 $auth0Client, ExternalAuthInterface $externalAuth) {
    $this->auth0Client = $auth0Client;
    $this->externalAuth = $externalAuth;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uitid.auth0_client'),
      $container->get('externalauth.externalauth')
    );
  }

  /**
   * Initiate an uitid login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return TrustedRedirectResponse
   *   The redirect response to uitid.
   */
  public function login(Request $request) {
    $redirect = new TrustedRedirectResponse($this->auth0Client->getLoginUrl(), 302);
    $metadata = $redirect->getCacheableMetadata();
    $metadata->setCacheMaxAge(0);

    return $redirect;
  }

  /**
   * Authorize the uitid user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function authorize(Request  $request) {
    // Check for in error indication in the query params.
    $errorCode = $request->query->get('error', $request->request->get('error'));

    if ($errorCode) {
      // Errors codes that should be redirected back to Auth0 for authentication.
      $redirectErrors = [
        'login_required',
        'interaction_required',
        'consent_required',
      ];
      if (in_array($errorCode, $redirectErrors)) {
        return new TrustedRedirectResponse($this->auth0Client->getLoginUrl());
      }
      else {
        $errorDescription = $request->query->get('error_description', $request->request->get('error_description', $errorCode));
        return $this->handleFailure($errorDescription);
      }
    }

    try {
      $userInfo = $this->auth0Client->getUser();

      $account = NULL;

      // First try if user exist via the v1 module.
      if (isset($userInfo['https://publiq.be/uitidv1id'])) {
        $account = $this->externalAuth->login('culturefeed_uitid', $userInfo['https://publiq.be/uitidv1id']);
      }

      if (empty($account)) {
        $accountData = [
          'name' => $userInfo['nickname'],
        ];
        $this->externalAuth->loginRegister($userInfo['sub'], 'uitid', $accountData);
      }

      $destination = $request->query->has('destination') ? $request->query->get('destination') : '<front>';
      $response = $this->redirect($destination);

      $response->setMaxAge(0);
      $response->setPrivate();
    }
    catch (\Exception $e) {
      return $this->handleFailure('There was an error while creating / loading the drupal user');
    }

    return $response;
  }

  /**
   * Authenticated check.
   *
   * @return mixed
   *   Return Authorize string.
   */
  public function authenticated(Request $request) {
    return [
      '#theme' => 'culturefeed_user_authenticated_page',
    ];
  }

  /**
   * Handle an authentication failure.
   *
   * @param string $logMessage
   *   Log message to show.
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   */
  private function handleFailure(string $logMessage) {
    $this->messenger()->addError($this->t('There was a problem logging you in, sorry for the inconvenience.'));
    $this->getLogger('uitid')->error($logMessage);
    $this->auth0Client->logout();

    $response = $this->redirect('<front>');
    $response->setMaxAge(0);
    $response->setPrivate();

    return $response;
  }

}
