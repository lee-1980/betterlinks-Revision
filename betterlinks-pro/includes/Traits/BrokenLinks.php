<?php
namespace BetterLinksPro\Traits;

trait BrokenLinks
{
    public function check_broken_link($data)
    {
        $target_url = '';
        $logs = json_decode(get_option('betterlinkspro_broken_links_logs', '{}'), true);
        $target_url = \BetterLinksPro\Helper::addScheme($data['target_url']);
        $result = [];
        if (!isset($logs[$data['ID']])) {
            $postion = strpos($target_url, '/*');
            if ($postion == false && \BetterLinksPro\Helper::url_http_response_is_broken($target_url)) {
                $result = array(
                                'ID'            => $data['ID'],
                                'title'         => $data['link_title'],
                                'short_url'     => $data['short_url'],
                                'target_url'    => $target_url,
                                'status'        => true
                            );
            } else {
                $result = array(
                            'ID'            => $data['ID'],
                            'title'         => $data['link_title'],
                            'short_url'     => $data['short_url'],
                            'target_url'    => $target_url,
                            'status'        => false
                        );
            }
        } else {
            $result = $logs[$data['ID']];
        }

        $logs[$data['ID']] = $result;
        update_option('betterlinkspro_broken_links_logs', json_encode($logs), false);
        return $result;
    }

    public function send_mail()
    {
        $settings = json_decode(get_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME), true);
        if ($settings['enable_reporting'] == true) {
            $to = (isset($settings['email']) && !empty($settings['email']) ? $settings['email'] : get_option('admin_email'));
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $subject = (isset($settings['email_subject']) && !empty($settings['email_subject']) ? $settings['email_subject'] : esc_html__('Summarized Report Of Broken Links On Your Site.', 'betterlinks-pro'));
            $body = $this->get_email_body();
            return wp_mail($to, $subject, $body, $headers);
        }
        return;
    }

    public function get_email_body()
    {
        global $wpdb;
        $betterlinks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", OBJECT);
        $logs = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        $total_links = count($betterlinks);
        $scan_links = count($logs);
        $issue_found = count(array_filter($logs, function ($log) {
            return $log['status'] === true;
        }));
        $report_time = date('l, F jS, Y');
        $logo = BETTERLINKS_PRO_ASSETS_URI . 'images/betterlinks-logo.png';
        $admin_brokenLink_url = admin_url('admin.php?page=betterlinks-settings');
        $output = '';
        ob_start();
        include BETTERLINKS_PRO_ROOT_DIR_PATH . 'includes/Admin/views/email-report-template.php';
        $output = ob_get_clean();
        return (string) $output;
    }
}
