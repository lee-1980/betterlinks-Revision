<?php
namespace BetterLinks\Tools\Migration;

use BetterLinks\Interfaces\ImportCsvInterface;

class TAImportCSV extends BaseCSV implements ImportCsvInterface
{
    public function start_importing($data)
    {
        $message = [];
        $data = $this->prepare_csv_data_to_import($data);
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $item) {
                if (!empty($item['link_title']) && !empty($item['short_url'])) {
                    $link_id = $this->insert_link($item);
                    if (!empty($item['keywords'])) {
                        $this->insert_keywords($link_id, $item['keywords'], [
                            'limit' => $item['keyword_limit']
                        ]);
                    }
                    if ($link_id) {
                        $message[] = 'Imported Successfully "' . $item['short_url'] . '"';
                    } else {
                        $message[] = 'Imported Failed "' . $item['short_url'] . '" already exists.';
                    }
                }
            }
        }
        return [
            'links' => $message
        ];
    }

    public function prepare_csv_data_to_import($csv)
    {
        $results = [];
        $count = 0;
        $betterlinks_links = json_decode(get_option('betterlinks_links', '{}'), true);
        while (($item = fgetcsv($csv)) !== false) {
            if ($count === 0) {
                $count++;
                continue;
            }
            $item = \BetterLinks\Helper::sanitize_text_or_array_field($item);
            // link status
            $link_status = 'publish';
            if (isset($item[14]) && !empty($item[14])) {
                $now = time();
                if ($now < strtotime($item[14])) {
                    $link_status = 'scheduled';
                }
                if ($now > strtotime($item[13])) {
                    $link_status = 'draft';
                }
            }
            
            // expire
            $expire = [];
            if (!empty($item[13])) {
                $expire = [
                    'status' => 1,
                    'type'   => 'date',
                    'date'  => $item[13],
                ];
            }
            if (!empty($item[13])) {
                $expire['redirect_status'] = 1;
                $expire['redirect_url'] = $item[7];
            }

            // geolocation
            $dynamic_redirect = [];
            if (isset($item[5]) && !empty($item[5])) {
                $dynamic_redirect = $this->prepare_dynamic_redirect_by_string($item[5]);
            }
            $results[] = [
                'link_title'    =>  $item[0],
                'link_slug'     =>  $item[2],
                'link_status'   => $link_status,
                'nofollow'  => ($item[16] == 'global' ? $betterlinks_links['nofollow'] : $item[16]),
                'sponsored'  => $betterlinks_links['sponsored'],
                'track_me'  => $betterlinks_links['track_me'],
                'param_forwarding'  => ($item[17] == 'global' ? $betterlinks_links['param_forwarding'] : $item[17]),
                'redirect_type'  => ($item[18] == 'global' ? $betterlinks_links['redirect_type'] : $item[18]),
                'target_url'  => $item[1],
                'short_url'  => trim((isset($betterlinks_link['prefix']) && !empty($betterlinks_link['prefix']) ? $betterlinks_link['prefix'] . '/' . $item[2] : $item[2]), '/'),
                'expire'  => json_encode($expire),
                'dynamic_redirect'  => json_encode($dynamic_redirect),
                'category'  => $item[3],
                'keywords' => !empty($item[6]) ? str_replace(';', ',', $item[6]) : '',
                'keyword_limit' => !empty($item[8]) ? $item[8] : 100
            ];
        }
        return $results;
    }

    public function prepare_dynamic_redirect_by_string($data)
    {
        $dynamic_redirect = [];
        $geo_locations = explode(';', $data);
        $country = [];
        foreach ($geo_locations as $geo_location) {
            if (strlen($geo_location) === 5) {
                $geo_location = explode(':', $geo_location);
                $geo_location = implode(':', array_reverse($geo_location));
            }
            $geo_location = explode(':', $geo_location, 2);
            if (strlen($geo_location[1]) === 2) {
                $country[$geo_location[1]] = $country[$geo_location[0]];
            } else {
                $country[$geo_location[0]] = $geo_location[1];
            }
        }
        $results = array();
        foreach ($country as $key => $element) {
            $results[$element][] = $key;
        }
        $dynamic_redirect_value = [];
        foreach ($results as $key => $country) {
            $dynamic_redirect_value[] = [
                        'link'      => $key,
                        'country'   => $country,
                    ];
        }
        $dynamic_redirect = [
                    'type'	    =>	'geographic',
                    'value'     => $dynamic_redirect_value,
                    'extra' => []
                ];
        
        return $dynamic_redirect;
    }
}
