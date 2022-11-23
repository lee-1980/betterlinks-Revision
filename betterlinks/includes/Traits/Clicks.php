<?php
namespace BetterLinks\Traits;

trait Clicks
{
    public function get_clicks_data($from, $to)
    {
        $results = \BetterLinks\Helper::get_clicks_by_date($from, $to);
        return $results;
    }
}
