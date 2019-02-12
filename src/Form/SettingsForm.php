<?php

namespace Drupal\web_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\web_push_notification\KeysHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Web Push Notification settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\web_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\web_push_notification\KeysHelper $keys_helper
   *   The push keys helper service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeysHelper $keys_helper) {
    parent::__construct($config_factory);
    $this->keysHelper = $keys_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('web_push_notification.keys_helper')
    );
  }

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
    return [
      'web_push_notification.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $this->keysHelper->getPublicKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $this->keysHelper->getPrivateKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t($this->keysHelper->isKeysDefined() ? 'Regenerate keys' : 'Generate keys'),
      '#limit_validation_errors' => [], // Skip required fields validation.
      '#submit' => ['::generateKeys'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->saveKeys(
      $form_state->getValue('public_key'),
      $form_state->getValue('private_key')
    );
    $this->messenger()->addStatus($this->t('Keys have been saved.'));
  }

  /**
   * Form submit callback for keys (re)generation.
   */
  public function generateKeys(array &$form, FormStateInterface $form_state) {
    $keys = $this->keysHelper->generateKeys();
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
