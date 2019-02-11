<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Minishlink\WebPush\VAPID;

/**
 * Manages public and private keys.
 */
class KeysHelper {

  const SETTINGS = 'web_push_notification.settings';

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * HelperService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get(self::SETTINGS);
  }

  /**
   * Returns a public key.
   *
   * @return string
   *   The public key.
   */
  public function getPublicKey(): string {
    return $this->config->get('public_key');
  }

  /**
   * Returns a private key.
   *
   * @return string
   *   The private key.
   */
  public function getPrivateKey(): string {
    return $this->config->get('private_key');
  }

  /**
   * Generates a public and private keys.
   *
   * @return array
   *   The list of two keys indexed by 'publicKey' and 'privateKey'.
   */
  public function generateKeys(): array {
    return VAPID::createVapidKeys();
  }

  /**
   * Returns whether keys (public and private) defined.
   *
   * @return bool
   */
  public function isKeysDefined(): bool {
    $public = $this->getPublicKey();
    $private = $this->getPublicKey();
    return $public && $private;
  }

}
