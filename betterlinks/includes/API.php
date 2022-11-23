<?php
namespace BetterLinks;

class API
{
    public static function init()
    {
        new API\Settings();
        new API\Links();
        new API\Terms();
        new API\Clicks();
    }
    public static function dispatch_hook()
    {
        $self = new self();
        add_filter('jwt_auth_whitelist', [$self, 'whitelist_API']);
        add_filter('rest_url', array($self, 'rest_url_ssl'));
    }
    public function whitelist_API($endpoints)
    {
        $endpoints[] = '/wp-json/' . BETTERLINKS_PLUGIN_SLUG . '/v1/*';
        $endpoints[] = '/index.php?rest_route=/' . BETTERLINKS_PLUGIN_SLUG . '/v1/*';
        return $endpoints;
    }
    public function rest_url_ssl($url)
    {
        if (is_ssl() || (is_admin() && force_ssl_admin())) {
            $url = set_url_scheme($url, 'https');
            return $url;
        }
        return $url;
    }
}
