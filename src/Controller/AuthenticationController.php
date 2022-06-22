<?php

namespace Drupal\uitid\Controller;

use Auth0\SDK\Auth0;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
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
   * The UiTiD current user.
   *
   * @var UitIdCurrentUserInterface
   */
  protected $uitIdCurrentUser;

  /**
   * @param \Auth0\SDK\Auth0 $auth0Client
   *   The auth0 client.
   */
  public function __construct(Auth0 $auth0Client, ExternalAuthInterface $externalAuth, UitIdCurrentUserInterface $uitIdCurrentUser) {
    $this->auth0Client = $auth0Client;
    $this->externalAuth = $externalAuth;
    $this->uitIdCurrentUser = $uitIdCurrentUser;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uitid.auth0_client'),
      $container->get('externalauth.externalauth'),
      $container->get('uitid.current_user')
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
    $params = [
      Auth0::TRANSIENT_STATE_KEY => base64_encode(
        \json_encode($this->getDestinationArray()),
      ),
      'prompt' => 'login'
    ];

    if ($referrer = $this->config(UitIdSettingsForm::CONFIG_NAME)->get('referrer')) {
      $params['referrer'] = $referrer;
    }

    $redirect = new TrustedRedirectResponse($this->auth0Client->getLoginUrl($params), 302);

    // Remove the destination query parameter, so Drupal does not interfere with our redirect response.
    $request->query->remove('destination');

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
        $params = [
          'prompt' => 'login'
        ];
        if ($referrer = $this->config(UitIdSettingsForm::CONFIG_NAME)->get('referrer')) {
          $params['referrer'] = $referrer;
        }
        return new TrustedRedirectResponse($this->auth0Client->getLoginUrl($params));
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
        $account = $this->externalAuth->login($userInfo['https://publiq.be/uitidv1id'], 'culturefeed_uitid');
      }

      if (empty($account)) {
        $accountData = [
          'name' => $userInfo['email'],
          'mail' => $userInfo['email'],
        ];
        $this->externalAuth->loginRegister($userInfo['sub'], 'uitid', $accountData);
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
      ]);
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
    if ($this->currentUser()->isAuthenticated() && $this->uitIdCurrentUser->isUitIdUser()) {
      if ($request->query->has('_exception_statuscode') && $request->query->get('_exception_statuscode') === 403) {
        return [
          '#markup' => $this->t('You are not authorized to access this page.'),
          '#title' => $this->t('Access denied'),
        ];
      }

      return new RedirectResponse(Url::fromRoute('<front>')->toString(), 302);
    }

    return [
      '#theme' => 'uitid_authenticated_page',
    ];
  }

  /**
   * Handle an authentication failure.
   *
   * @param string $logMessage
   *   Log message to show.
   * @param array $context
   *   Context for the log.
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   */
  private function handleFailure(string $logMessage, array $context = []) {
    $this->messenger()->addError($this->t('There was a problem logging you in, sorry for the inconvenience.'));
    $this->getLogger('uitid')->error($logMessage, $context);
    $this->auth0Client->logout();

    $response = $this->redirect('<front>');
    $response->setMaxAge(0);
    $response->setPrivate();

    return $response;
  }

  /**
   * Decodes the state parameter from a request.
   *
   * @param Request $request
   *   The request.
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
