<?php
namespace BetterLinks\Tools\Migration;

use BetterLinks\Interfaces\ImportCsvInterface;
use Error;

class PTLImportCSV extends BaseCSV implements ImportCsvInterface
{
    private $link_header = [];
    public function start_importing($csv)
    {
        $count = 0;
        $link_message = [];
        $click_message = [];
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
            if (isset($item['Browser'])) {
                $click_message[] =  $this->process_clicks_data($item);
            } else {
                $link_message[] = $this->process_links_data($item);
            }
        }
        return ['links' => $link_message, 'clicks' => $click_message];
    }

    public function process_links_data($item)
    {
        $author_id = get_current_user_id();
        $slug = \BetterLinks\Helper::make_slug($item['slug']);
        $link = apply_filters('betterlinks/tools/migration/ptl_csv_import_link_arg', [
                'link_author' => $author_id,
                'link_date' => $item['created_at'],
                'link_date_gmt' => $item['created_at'],
                'link_title' => $item['name'],
                'link_slug' => $slug,
                'link_note' => '',
                'link_status' => 'publish',
                'nofollow' => isset($item['nofollow']) && $item['nofollow'] == 1 ? $item['nofollow'] : '',
                'sponsored' => isset($item['sponsored']) && $item['sponsored'] == 1 ? $item['sponsored'] : '',
                'track_me' => isset($item['track_me']) && $item['track_me'] == 1 ? $item['track_me'] : '',
                'param_forwarding' => isset($item['param_forwarding']) && $item['param_forwarding'] == 1 ? $item['param_forwarding'] : '',
                'param_struct' => '',
                'redirect_type' => isset($item['redirect_type']) ? $item['redirect_type'] : '',
                'target_url' => isset($item['url']) ? $item['url'] : '',
                'short_url' => isset($item['slug']) ? $item['slug'] : '',
                'link_order' => 0,
                'link_modified' => isset($item['last_updated_at']) ? $item['last_updated_at'] : '',
                'link_modified_gmt' => isset($item['last_updated_at']) ? $item['last_updated_at'] : '',
                'category'  => $item['link_categories'],
            ]);
        $link_id = $this->insert_link($link);
        if ($link_id) {
            if (isset($item['keywords']) && !empty($item['keywords'])) {
                $this->insert_keywords($link_id, $item['keywords']);
            }
            return 'Imported Successfully "' . $item['name'] . '"';
        }
        return 'import failed "' . $item['name'] . '" already exists';
    }

    public function process_clicks_data($item)
    {
        $link = \BetterLinks\Helper::get_link_by_short_url(\trim($item['URI'], '/'));
        if (count($link) > 0) {
            $click = [
                    'link_id' => $link[0]['ID'],
                    'ip' => $item['IP'],
                    'browser' => $item['Browser'],
                    'os' => $item['Platform'],
                    'referer' => $item['Referrer'],
                    'host' => $item['Host'],
                    'uri' => $item['URI'],
                    'click_count' => '',
                    'visitor_id' => $item['Visitor ID'],
                    'click_order' => '',
                    'created_at' => $item['Timestamp'],
                    'created_at_gmt' => $item['Timestamp'],
                ];
            $is_insert = \BetterLinks\Helper::insert_click($click);
            if ($is_insert) {
                return 'Imported Successfully "' . $item['URI'] . '"';
            }
        }
    }
}
