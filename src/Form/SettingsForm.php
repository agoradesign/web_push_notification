<?php

namespace Drupal\web_push_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Minishlink\WebPush\VAPID;

/**
 * Web Push Notification settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_push_notification_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['web_push_notification.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('web_push_notification.settings');

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $config->get('public_key'),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $config->get('private_key'),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t($config->get('public_key') ? 'Regenerate keys' : 'Generate keys'),
      '#limit_validation_errors' => [], // Skip required fields validation.
      '#submit' => ['::generateKeys'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->saveKeys($form_state->getValue('public_key'), $form_state->getValue('private_key'));
    $this->messenger()->addStatus($this->t('Keys have been saved.'));
  }

  /**
   * Form submit callback for keys (re)generation.
   */
  public function generateKeys(array &$form, FormStateInterface $form_state) {
    $keys = VAPID::createVapidKeys();
    $this->saveKeys($keys['publicKey'], $keys['privateKey']);
    $this->messenger()->addStatus($this->t('Public and private keys have been generated.'));
  }

  /**
   * Saves public and private keys to the module settings.
   *
   * @param string $publicKey
   *   The public key.
   * @param string $privateKey
   *   The private key.
   */
  protected function saveKeys(string $publicKey, string $privateKey) {
    $this->config('web_push_notification.settings')
      ->set('public_key', $publicKey)
      ->set('private_key', $privateKey)
      ->save();
  }

}
