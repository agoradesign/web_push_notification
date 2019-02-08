<?php

namespace Drupal\web_push_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class WebPushNotificationController.
 */
class WebPushNotificationController extends ControllerBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * WebPushNotificationController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   */
  public function __construct(ModuleHandler $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Gets the service worker javascript handler.
   *
   * @return BinaryFileResponse
   *  The service worker content.
   */
  public function serviceWorker() {
    $module_path = $this->moduleHandler->getModule('web_push_notification')->getPath();
    $uri = "{$module_path}/js/service_worker.js";

    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    return BinaryFileResponse::create($uri, 200, [
      'Content-Type' => 'text/javascript',
      'Content-Length' => filesize($uri),
    ]);
  }

  /**
   * Accepts a user confirmation for notifications subscribe.
   */
  public function subscribe(Request $request) {
    return new Response();
  }

}
