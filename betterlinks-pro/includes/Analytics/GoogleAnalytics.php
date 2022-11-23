<?php
namespace BetterLinksPro\Analytics;

class GoogleAnalytics {
        //Parse the GA Cookie
        public function gaParseCookie() {
            if (isset($_COOKIE['_ga'])) {
                list($version, $domainDepth, $cid1, $cid2) = explode('.', $_COOKIE["_ga"], 4);
                $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1 . '.' . $cid2);
                $cid = $contents['cid'];
            } else {
                $cid = $this->gaGenerateUUID();
            }
            return $cid;
        }

        //Generate UUID
        //Special thanks to stumiller.me for this formula.
        public function gaGenerateUUID() {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }

        //Send Data to Google Analytics
        //https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide#event
        public function gaSendData($data) {
            $getString = 'https://ssl.google-analytics.com/collect';
            $getString .= '?payload_data&';
            $getString .= http_build_query($data);
            $result = wp_remote_get($getString);
            return $result;
        }

        //Send Pageview Function for Server-Side Google Analytics
        public function ga_send_pageview($hostname=null, $page=null, $title=null, $tid) {
            if(empty($tid)) return;
            $data = array(
                'v' => 1,
                'tid' => $tid, //@TODO: Change this to your Google Analytics Tracking ID.
                'cid' => $this->gaParseCookie(),
                't' => 'pageview',
                'dh' => $hostname, //Document Hostname "gearside.com"
                'dp' => $page, //Page "/something"
                'dt' => $title //Title
            );
            $this->gaSendData($data);
        }

        //Send Event Function for Server-Side Google Analytics
        public function ga_send_event($category=null, $action=null, $label=null) {
            $data = array(
                'v' => 1,
                'tid' => 'UA-194308682-1', //@TODO: Change this to your Google Analytics Tracking ID.
                'cid' => $this->gaParseCookie(),
                't' => 'event',
                'ec' => $category, //Category (Required)
                'ea' => $action, //Action (Required)
                'el' => $label //Label
            );
            $this->gaSendData($data);
        }
}