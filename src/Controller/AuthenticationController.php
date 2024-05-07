<?php

namespace Drupal\uitid\Controller;

use Auth0\SDK\Auth0;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\uitid\Form\UitIdSettingsForm;
use Drupal\uitid\UitIdCurrentUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an authentication controller for the uitid login.
 */
class AuthenticationController extends ControllerBase {

  /**
   * Constructs AuthenticationController.
   *
   * @param Auth0 $auth0Client
   *   The auth0 client.
   * @param ExternalAuthInterface $externalAuth
   *   The external auth service.
   * @param UitIdCurrentUserInterface $uitIdCurrentUser
   *   The UiTiD current user.
   * @param AuthmapInterface $authmap
   *   The authmap.
   * @param SessionManagerInterface $sessionManager
   *   The session manager.
   */
  public function __construct(
    protected Auth0 $auth0Client,
    protected ExternalAuthInterface $externalAuth,
    protected UitIdCurrentUserInterface $uitIdCurrentUser,
    protected AuthmapInterface $authmap,
    protected SessionManagerInterface $sessionManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uitid.auth0_client'),
      $container->get('externalauth.externalauth'),
      $container->get('uitid.current_user'),
      $container->get('externalauth.authmap'),
      $container->get('session_manager')
    );
  }

  /**
   * Initiate an uitid login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response to uitid.
   */
  public function login(Request $request) {
    $params = [
      'prompt' => 'login',
    ];

    if ($request->query->has('destination')) {
      $params['state'] = base64_encode(
        \json_encode($this->getDestinationArray()),
      );
      // Remove the destination query parameter
      // so Drupal does not interfere with our redirect response.
      $request->query->remove('destination');
    }

    if ($referrer = $this->config(UitIdSettingsForm::CONFIG_NAME)->get('referrer')) {
      $params['referrer'] = $referrer;
    }

    $redirect = new TrustedRedirectResponse($this->auth0Client->login(params: $params), 302);

    $metadata = $redirect->getCacheableMetadata();
    $metadata->setCacheMaxAge(0);

    return $redirect;
  }

  /**
   * Authorize the uitid user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function authorize(Request $request) {
    // Check for in error indication in the query params.
    $errorCode = $request->query->get('error', $request->request->get('error'));

    if ($errorCode) {
      // Errors codes that should be redirected back to Auth0.
      $redirectErrors = [
        'login_required',
        'interaction_required',
        'consent_required',
      ];
      if (in_array($errorCode, $redirectErrors)) {
        $params = [
          'prompt' => 'login',
        ];
        if ($referrer = $this->config(UitIdSettingsForm::CONFIG_NAME)->get('referrer')) {
          $params['referrer'] = $referrer;
        }
        return new TrustedRedirectResponse($this->auth0Client->login(params: $params));
      }
      else {
        $errorDescription = $request->query->get('error_description', $request->request->get('error_description', $errorCode));
        return $this->handleFailure($errorDescription, [], $request);
      }
    }

    try {
      $this->auth0Client->exchange();
      $userInfo = $this->auth0Client->getUser();
      $account = $this->externalAuth->login($userInfo['sub'], 'uitid');

      // Try with uitid v1.
      if (empty($account) && isset($userInfo['https://publiq.be/uitidv1id'])) {
        $account = $this->externalAuth->login($userInfo['https://publiq.be/uitidv1id'], 'culturefeed_uitid');

        // Replace v1 mapping with v2 mapping.
        if ($account) {
          $this->externalAuth->linkExistingAccount($userInfo['sub'], 'uitid', $account);
          $this->authmap->delete($account->id(), 'culturefeed_uitid');
        }
      }

      if (!$account) {
        $accountData = [
          'name' => $userInfo['email'],
          'mail' => $userInfo['email'],
        ];
        $account = $this->externalAuth->register($userInfo['sub'], 'uitid', $accountData);
        $this->externalAuth->userLoginFinalize($account, $userInfo['sub'], 'uitid');
      }

      $state = $this->decodeState($request);
      $destination = $state['destination'] ?? Url::fromRoute('<front>')->toString();
      if (UrlHelper::isExternal($destination)) {
        $destination = '/';
      }

      $response = new RedirectResponse($destination);
      $response->setMaxAge(0);
      $response->setPrivate();
    }
    catch (\Exception $e) {
      return $this->handleFailure('There was an error while creating / loading the drupal user: !message', [
        '!message' => $e->getMessage(),
      ], $request);
    }

    return $response;
  }

  /**
   * Handle an authentication failure.
   *
   * @param string $logMessage
   *   Log message to show.
   * @param array $context
   *   Context for the log.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   */
  private function handleFailure(string $logMessage, array $context = [], Request $request = NULL) {
    $this->messenger()->addError($this->t('There was a problem logging you in, sorry for the inconvenience.'));
    $this->getLogger('uitid')->error($logMessage, $context);

    if ($this->currentUser()->isAuthenticated()) {
      // Clear the current session, leaving the flash bag alone.
      $this->sessionManager->getBag('attributes')?->clear();

      // Trigger user_logout hooks.
      $this->moduleHandler()->invokeAll('user_logout', [$this->currentUser()]);

      // If a session is already active, destroy it.
      if (\session_status() === PHP_SESSION_ACTIVE) {
        // Destroy the current session, and reset $user to the anonymous user.
        $this->sessionManager->destroy();
      }
    }

    $request->query->remove('destination');
    $response = $this->redirect('<front>');
    $response->setMaxAge(0);
    $response->setPrivate();

    return $response;
  }

  /**
   * Decodes the state parameter from a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The state values.
   */
  private function decodeState(Request $request) {
    $state = $request->query->get('state');
    if (empty($state)) {
      return [];
    }

    $values = base64_decode($state);
    return \json_decode($values, TRUE);
  }

}
