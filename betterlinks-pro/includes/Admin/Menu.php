<?php
namespace BetterLinksPro\Admin;


class Menu { 
    public static $user;
    public static $user_permission;
    public static function init()
    {
        $self = new self();
        self::$user = wp_get_current_user();
        self::$user_permission = json_decode(get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, '{}'), true);
        add_filter('betterlinks/admin/menu_capability', [$self, 'manage_links_menu_capability']);
        add_filter("betterlinks/admin/".BETTERLINKS_PLUGIN_SLUG."_menu_capability", [$self, 'manage_links_menu_capability']);
        add_filter("betterlinks/admin/".BETTERLINKS_PLUGIN_SLUG."-analytics_menu_capability", [$self, 'analytics_menu_capability']);
        add_filter("betterlinks/admin/".BETTERLINKS_PLUGIN_SLUG."-settings_menu_capability", [$self, 'settings_menu_capability']);
    }

    public function manage_links_menu_capability($capabiliy)
    {
        if(current_user_can('manage_options')) $capabiliy;
        $current_user_roles = current(self::$user->roles);
        if( isset(self::$user_permission['writelinks']) && in_array($current_user_roles, self::$user_permission['writelinks'])) {
            return 'read';
        } else if( isset(self::$user_permission['editlinks']) && in_array($current_user_roles, self::$user_permission['editlinks'])){
            return 'read';
        }else if( isset(self::$user_permission['viewlinks']) && in_array($current_user_roles, self::$user_permission['viewlinks'])){
            return 'read';
        }
        return $capabiliy;
    }
    public function analytics_menu_capability($capabiliy)
    {
        if(current_user_can('manage_options')) $capabiliy;
        $current_user_roles = current(self::$user->roles);
        if(isset(self::$user_permission['checkanalytics']) && in_array($current_user_roles, self::$user_permission['checkanalytics'])){
            return 'read';
        }
        return $capabiliy;
    }
    public function settings_menu_capability($capabiliy)
    {
        if(current_user_can('manage_options')) $capabiliy;
        $current_user_roles = current(self::$user->roles);
        if(isset(self::$user_permission['editsettings']) && in_array($current_user_roles, self::$user_permission['editsettings'])){
            return 'read';
        }
        return $capabiliy;
    }
}