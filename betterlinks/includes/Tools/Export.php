<?php
namespace BetterLinks\Tools;

class Export
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'export_data']);
    }
    public function export_data()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $export = isset($_GET['export']) ? $_GET['export'] : false;
        if ($page === 'betterlinks-settings' && $export == true) {
            $type = isset($_POST['content']) ? $_POST['content'] : '';
            $this->download_files($type);
            exit();
        }
    }

    public function download_files($type)
    {
        $data = [];
        $filename = 'betterlinks';
        if ($type === 'links') {
            $links = $this->get_links();
            $data = $this->prepare_csv_file_data($links);
        } elseif ($type === 'clicks') {
            $clicks = $this->get_clicks();
            $data = $this->prepare_csv_file_data($clicks);
            $filename .= '-clicks';
        } else {
            $filename = 'Sample-file';
            $data = $this->simple_file_download();
        }
        $filename .= '.' . date('Y-m-d') . '.csv';
        $this->array_to_csv_download(
            $data,
            $filename
        );
    }

    public function array_to_csv_download($array, $filename = "export.csv", $delimiter=";")
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        $f = fopen('php://output', 'w');
        foreach ($array as $line) {
            fputcsv($f, $line);
        }
    }

    public function prepare_csv_file_data($data)
    {
        if (is_array($data) && count($data) > 0) {
            return array_merge([array_keys($data[0])], $data);
        }
        return [];
    }

    public function get_links()
    {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", ARRAY_A);
        $results = [];
        if (is_array($links) && count($links) > 0) {
            foreach ($links as $link) {
                $terms = $this->get_terms_from_link_id($link['ID']);
                $results[] = array_merge($link, $terms);
            }
        }
        return $results;
    }

    public function simple_file_download()
    {
        global $wpdb;
        $links = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}betterlinks", ARRAY_A);
        if (is_array($links) && count($links) > 0) {
            $links['tags'] = '';
            $links['category'] = '';
            return [array_keys($links)];
        }
        return [];
    }

    public function get_terms_from_link_id($link_id = 0)
    {
        global $wpdb;
        $category = [];
        $tags = [];
        $terms = $wpdb->get_results("SELECT *  FROM {$wpdb->prefix}betterlinks_terms  LEFT JOIN  {$wpdb->prefix}betterlinks_terms_relationships ON {$wpdb->prefix}betterlinks_terms.ID = {$wpdb->prefix}betterlinks_terms_relationships.term_id WHERE {$wpdb->prefix}betterlinks_terms_relationships.link_id = {$link_id}", ARRAY_A);
        if (is_array($terms) && count($terms) > 0) {
            foreach ($terms as $term) {
                if ($term['term_type'] == 'category') {
                    $category[] = $term['term_slug'];
                } elseif ($term['term_type'] == 'tags') {
                    $tags[] = $term['term_slug'];
                }
            }
        }
        return [
            'tags' => (count($tags) > 0 ? implode(',', $tags) : ''),
            'category' => (count($category) > 0 ? implode(',', $category) : ''),
        ];
    }


    public function get_clicks()
    {
        global $wpdb;
        $clicks = $wpdb->get_results("SELECT 
            {$wpdb->prefix}betterlinks.short_url,
            {$wpdb->prefix}betterlinks_clicks.ip, 
            {$wpdb->prefix}betterlinks_clicks.browser, 
            {$wpdb->prefix}betterlinks_clicks.os, 
            {$wpdb->prefix}betterlinks_clicks.referer, 
            {$wpdb->prefix}betterlinks_clicks.host, 
            {$wpdb->prefix}betterlinks_clicks.uri, 
            {$wpdb->prefix}betterlinks_clicks.click_count, 
            {$wpdb->prefix}betterlinks_clicks.visitor_id, 
            {$wpdb->prefix}betterlinks_clicks.click_order, 
            {$wpdb->prefix}betterlinks_clicks.created_at, 
            {$wpdb->prefix}betterlinks_clicks.created_at_gmt
        FROM {$wpdb->prefix}betterlinks_clicks LEFT JOIN {$wpdb->prefix}betterlinks ON {$wpdb->prefix}betterlinks_clicks.link_id = {$wpdb->prefix}betterlinks.ID", ARRAY_A);
        return $clicks;
    }
}
