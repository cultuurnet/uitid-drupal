<?php

namespace Drupal\uitid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a settings form for UitId settings.
 */
class UitIdSettingsForm extends ConfigFormBase {

  public const CONFIG_NAME = 'uitid.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uitid_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API host'),
      '#required' => TRUE,
      '#default_value' => $config->get('host') ?: '',
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id') ?: '',
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('secret') ?: '',
    ];

    $form['cookie_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Secret (Generate using shell: openssl rand -hex 32)'),
      '#required' => TRUE,
      '#default_value' => $config->get('cookie_secret') ?: '',
    ];

    $form['referrer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Referrer'),
      '#default_value' => $config->get('referrer') ?: '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $config->set('host', trim($form_state->getValue('host'), '/'));
    $config->set('client_id', $form_state->getValue('client_id'));
    $config->set('secret', $form_state->getValue('secret'));
    $config->set('referrer', $form_state->getValue('referrer'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
