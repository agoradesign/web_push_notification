<?php

namespace Drupal\web_push_notification\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Notification subscription entities.
 *
 * @ingroup web_push_notification
 */
interface SubscriptionEntityInterface extends ContentEntityInterface {

  /**
   * Gets the subscription key.
   *
   * @return string
   */
  public function getKey();

  /**
   * Sets the subscription key.
   *
   * @param string $key
   *   The subscription key.
   *
   * @return $this
   */
  public function setKey($key);

  /**
   * Gets the subscription token.
   *
   * @return string
   */
  public function getToken();

  /**
   * Sets the subscription token.
   *
   * @param string $token
   *   The subscription token.
   *
   * @return $this
   */
  public function setToken($token);

  /**
   * Gets the subscription endpoint.
   *
   * @return string
   */
  public function getEndpoint();

  /**
   * Sets the subscription endpoint.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   *
   * @return $this
   */
  public function setEndpoint($endpoint);

  /**
   * Gets the Notification subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Notification subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Notification subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Notification subscription creation timestamp.
   *
   * @return \Drupal\web_push_notification\Entity\SubscriptionEntityInterface
   *   The called Notification subscription entity.
   */
  public function setCreatedTime($timestamp);

}
