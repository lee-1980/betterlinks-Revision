<?php
namespace BetterLinks\Tools\Migration;

use BetterLinks\Interfaces\ImportCsvInterface;

class S30RImportCSV extends BaseCSV implements ImportCsvInterface
{
    public function start_importing($csv)
    {
        $message = [];
        $count = 0;
        while (($item = fgetcsv($csv)) !== false) {
            if ($count === 0) {
                $this->link_header = $item;
                $count++;
                continue;
            }
            $item = array_combine($this->link_header, $item);
            if (isset($item['short_url'])) {
                $item['short_url'] = rtrim($item['short_url'], '/');
            }
            $item = \BetterLinks\Helper::sanitize_text_or_array_field($item);
            $item = $this->prepare_csv_data_to_import($item);
            $link_id = $this->insert_link($item);
            if ($link_id) {
                $message[] = 'Imported Successfully "' . $item['short_url'] . '"';
            } else {
                $message[] = 'Imported Failed "' . $item['short_url'] . '" already exists.';
            }
        }
        return [
            'links' => $message
        ];
    }
    public function prepare_csv_data_to_import($item)
    {
        $author_id = get_current_user_id();
        $now = current_time('mysql');
        $now_gmt = current_time('mysql', 1);
        $betterlinks_links = json_decode(get_option('betterlinks_links'));
        $args = [
            'link_author' => $author_id,
            'link_date' => $now,
            'link_date_gmt' => $now_gmt,
            'link_title' => 'Simple 301 Redirects - ' . ltrim($item['request'], '/'),
            'link_slug' => \BetterLinks\Helper::make_slug('Simple 301 Redirects - ' . $item['destination']),
            'link_note' => '',
            'link_status' => 'publish',
            'nofollow' => $betterlinks_links->nofollow,
            'sponsored' => $betterlinks_links->sponsored,
            'track_me' => $betterlinks_links->track_me,
            'param_forwarding' => $betterlinks_links->param_forwarding,
            'param_struct' => '',
            'redirect_type' => '301',
            'target_url' => $this->url_schema_parse($item['destination']),
            'short_url' => ltrim($item['request'], '/'),
            'link_order' => 0,
            'link_modified' => $now,
            'link_modified_gmt' => $now_gmt,
            'wildcards' 		=> (strpos($item['request'], '/*') !== false ? 1 : 0),
            'category'          => 'simple-301-redirects'
        ];
        return $args;
    }
    public function url_schema_parse($url)
    {
        if (strpos($url, "/") === 0) {
            return $url;
        }
        return parse_url($url, PHP_URL_SCHEME) === null ? '/' . $url : $url;
    }
}
