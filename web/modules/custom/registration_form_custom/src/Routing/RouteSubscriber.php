<?php

namespace Drupal\registration_form_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_form', '\Drupal\registration_form_custom\Form\NewUserRegisterForm');
    }

  }

}
