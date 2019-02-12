<?php

namespace Drupal\web_push_notification;

/**
 * Class WebPushSenderInterface
 *
 * @package Drupal\web_push_notification
 */
interface WebPushSenderInterface {

  /**
   * Sends a notification item.
   *
   * @param \Drupal\web_push_notification\NotificationItem $item
   */
  public function send(NotificationItem $item);

}