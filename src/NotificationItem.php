<?php

namespace Drupal\web_push_notification;

/**
 * Notification queue item.
 */
class NotificationItem {

  /**
   * @var array
   */
  public $ids = [];

  /**
   * @var string
   */
  public $title = '';

  /**
   * @var string
   */
  public $message = '';

  public function __construct(string $title = '', string $message = '') {
    $this->title = $title;
    $this->message = $message;
  }

  /**
   * @return string
   */
  public function payload(): string {
    return json_encode([
      'title' => $this->title,
      'message' => $this->message,
    ]);
  }
}
