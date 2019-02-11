<?php

namespace Drupal\web_push_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\web_push_notification\KeysHelper;

/**
 * Allows to send a test notification to subscribed users.
 */
class TestNotification extends FormBase {

  /**
   * @var \Drupal\web_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * Constructs a new TestNotification object.
   */
  public function __construct(KeysHelper $keys_helper) {
    $this->keysHelper = $keys_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('web_push_notification.keys_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_notification';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // TODO: check that the subscribed list isn't empty!

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test notification'),
    ];
    $form['test']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['test']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['test']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Enter the icon URL which will show in the notification.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['test']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Enter the URL on which user will redirect after clicking on the notification.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
