<?php

namespace Drupal\uitid\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\uitid\UitIdCurrentUserInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that checks access for UitId users.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "uitid_user",
 *   title = @Translation("UitId user"),
 *   help = @Translation("Access will be granted to UitId users.")
 * )
 */
class UitIdUserStatus extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The UiTiD current user.
   *
   * @var UitIdCurrentUserInterface
   */
  protected $uitIdCurrentUser;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param UitIdCurrentUserInterface $uitIdCurrentUser
   *   The UiTiD current user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UitIdCurrentUserInterface $uitIdCurrentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->uitIdCurrentUser = $uitIdCurrentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('uitid.current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->uitIdCurrentUser->isUitIdUser() === (bool) $this->options['status'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_is_uitid_user', $this->options['status']);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->options['status'] ? $this->t('User is UitID user') : $this->t('User is not an UitId user');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['status'] = ['default' => 1];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['status'] = [
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('User is an UitId user'),
        0 => $this->t('User is not an UitId user'),
      ],
      '#title' => $this->t('Status'),
      '#default_value' => $this->options['status'],
      '#description' => $this->t('Only users with the selected status will be able to access this display.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.roles:authenticated'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
