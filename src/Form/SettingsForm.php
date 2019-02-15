<?php

namespace Drupal\web_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\web_push_notification\KeysHelper $keys_helper
   *   The push keys helper service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeysHelper $keys_helper, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($config_factory);
    $this->keysHelper = $keys_helper;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('web_push_notification.keys_helper'),
      $container->get('entity_type.bundle.info')
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
   * Returns a list of node bundles.
   *
   * @return array
   *  The list of node bundles.
   */
  protected function getNodeBundles(): array {
    return $this->bundleInfo->getBundleInfo('node');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('web_push_notification.settings');

    $form['auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Auth parameters'),
    ];
    $form['auth']['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $this->keysHelper->getPublicKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];
    $form['auth']['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $this->keysHelper->getPrivateKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];
    $form['auth']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t($this->keysHelper->isKeysDefined() ? 'Regenerate keys' : 'Generate keys'),
      '#limit_validation_errors' => [], // Skip required fields validation.
      '#submit' => ['::generateKeys'],
    ];

    $form['content'] = [
      '#type' => 'fieldset',
    ];
    $form['content']['bundles'] = [
      '#type' => 'table',
      '#tableselect' => TRUE,
      '#default_value' => [],
      '#header' => [
        [
          'data' => $this->t('Content type'),
          'class' => ['bundle'],
        ],
        [
          'data' => $this->t('Settings'),
          'class' => ['operations'],
        ],
      ],
      '#empty' => $this->t('No content types available.'),
    ];
    foreach ($this->getNodeBundles() as $id => $info) {
      $form['content']['bundles'][$id] = [
        'bundle' => [
          '#markup' => $info['label'],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'configure' => [
              'title' => $this->t('Configure'),
              'url' => Url::fromRoute('web_push_notification.bundle_configure', [
                'bundle' => $id,
              ]),
              'query' => \Drupal::destination()->getAsArray(),
            ],
          ],
        ],
      ];
      $form['content']['bundles']['#default_value'][$id] = $config->get("bundles.$id");
    }

    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];
    $form['config']['queue_batch_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Queue batch size'),
      '#description' => $this->t('How many number of notifications to send during the queue process.'),
      '#default_value' => $config->get('queue_batch_size') ?? 100,
      '#required' => TRUE,
    ];
    $form['config']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
      '#default_value' => $config->get('pages') ?? '/admin/*',
    ];
    $form['config']['body_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max body length'),
      '#description' => $this->t('Before sending a notification html tags will be deleted and the body field trimmed to the specified length.'),
      '#default_value' => $config->get('body_length') ?? 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $val = $form_state->getValue('queue_batch_size');
    if (!($val >= 1 && $val <= 1000)) {
      $form_state->setErrorByName('queue_batch_size', $this->t('Queue batch size must be in range 1..1000 inclusively.'));
    }
    $val = $form_state->getValue('body_length');
    if (!($val >= 10 && $val <= 1000)) {
      $form_state->setErrorByName('body_length', $this->t('Body length must be in range 10..100 inclusively.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('web_push_notification.settings');

    // Save the keys.
    $this->saveKeys(
      $form_state->getValue('public_key'),
      $form_state->getValue('private_key')
    );

    $config
      ->set('queue_batch_size', $form_state->getValue('queue_batch_size'))
      ->set('body_length', $form_state->getValue('body_length'))
      ->set('pages', $form_state->getValue('pages'))
      ->set('bundles', $form_state->getValue('bundles'))
      ->save();

    $this->messenger()->addStatus($this->t('Web Push notification settings have been updated.'));
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
