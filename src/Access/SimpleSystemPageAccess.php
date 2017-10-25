<?php

namespace Drupal\simple_system_page_access\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;

/**
 * Access check for database update routes.
 */
class SimpleSystemPageAccess implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu link tree manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  public function __construct(MenuLinkManagerInterface $menuLinkManager, MenuLinkTreeInterface $menu_tree) {
    $this->menuLinkManager = $menuLinkManager;
    $this->menuTree = $menu_tree;
  }

  /**
   * Checks access for update routes.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if (!$account->hasPermission('access administration pages')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    if ($route->getDefault('_controller') == '\Drupal\system\Controller\SystemController::overview') {
      $link_id = $route->getDefault('link_id');
      if (!$link_id) {
        return AccessResult::allowed()->cachePerPermissions();
      }
    } else {
      if ($links = $this->menuLinkManager->loadLinksByRoute($route_match->getRouteName(), $route_match->getParameters()->all(), 'admin')) {
        /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
        $link = reset($links);

        $link_id = $link->getPluginId();
      } else {
        return AccessResult::allowed()->cachePerPermissions();
      }
    }

    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link_id)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $this->menuTree->load(NULL, $parameters);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
    );

    $tree = $this->menuTree->transform($tree, $manipulators);

    foreach ($tree as $key => $element) {
      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::forbidden()->cachePerPermissions();
  }
}