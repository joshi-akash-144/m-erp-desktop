<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

/**
 * Class MenuHelper
 *
 * Helper class for managing menu visibility and active state in an ERP system.
 * Provides methods to check if a user has permission to view a menu item
 * and whether a menu item is active based on the current route.
 */
class MenuHelper
{
    /**
     * Determine if the authenticated user can view a menu item.
     * 
     * This method checks the user's permissions for the current menu item.
     * If the menu has submenus, it recursively checks all child items.
     *
     * @param object $menu Menu object. Expected to have:
     *                     - permission (string|null): The permission required to view this menu.
     *                     - submenu (array|null): Array of child menu objects.
     *
     * @return bool True if the user can view this menu or any of its submenus; false otherwise.
     */
    public static function canView($menu): bool
    {

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (isset($menu->permission) && $user && $user->can($menu->permission)) {
            return true;
        }

        // Check submenu permission recursively
        if (isset($menu->submenu)) {
            foreach ($menu->submenu as $child) {
                if (self::canView($child)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if a menu item (or any of its submenus) is active.
     *
     * A menu is considered active if its slug matches the current route name,
     * or if any of its submenus are active.
     *
     * @param object $menu Menu object. Expected to have:
     *                     - slug (string|null): The route name associated with this menu.
     *                     - submenu (array|null): Array of child menu objects.
     * @param string $currentRouteName The current route name from the application.
     *
     * @return bool True if this menu or any of its submenus is active; false otherwise.
     */
    public static function isActive($menu, string $currentRouteName): bool
    {
        if (isset($menu->slug) && $menu->slug === $currentRouteName) {
            return true;
        }

        if (isset($menu->submenu)) {
            foreach ($menu->submenu as $child) {
                if (self::isActive($child, $currentRouteName)) {
                    return true;
                }
            }
        }

        return false;
    }
}
