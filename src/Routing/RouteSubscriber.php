<?php

namespace Drupal\simple_system_page_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $route) {
      if (
        $route->getDefault('_controller') != '\Drupal\system\Controller\SystemController::systemAdminMenuBlockPage' &&
        $route->getDefault('_controller') != '\Drupal\system\Controller\SystemController::overview'
      ) {
        continue;
      }

      if ($route->getRequirement('_permission') == 'access administration pages') {
        $route->addRequirements(['_simple_system_page_access' => 'TRUE']);
      }
    }
  }
}