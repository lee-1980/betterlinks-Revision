<?php
namespace BetterLinks;

use BetterLinks\Link\Utils;

class Link extends Utils
{
    public function __construct()
    {
        if (!is_admin() && $_SERVER['REQUEST_METHOD'] == 'GET') {
            add_action('init', [$this, 'run_redirect'], 0);
        }
    }
    public function run_redirect()
    {
        $request_uri = stripslashes(rawurldecode($_SERVER['REQUEST_URI']));
        $request_uri = substr($request_uri, strlen(parse_url(site_url('/'), PHP_URL_PATH)));
        $param = explode('?', $request_uri, 2);
        $data = $this->get_slug_raw(rtrim(current($param), '/'));

        if (!empty($data) && apply_filters('betterlinks/pre_before_redirect', $data)) {
            do_action('betterlinks/before_redirect', $data);
            $this->dispatch_redirect($data, next($param));

        }
    }
}
