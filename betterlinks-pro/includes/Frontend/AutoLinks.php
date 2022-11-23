<?php

namespace BetterLinksPro\Frontend;

class AutoLinks
{
    private $protected_tags_content_lists = [];
    private $unique_number;
    private $hyperlink_icon_svg = "";
    private $autolink_options = [];
    public static function init()
    {
        $self = new self();
        $self->autolink_options = get_option(BETTERLINKS_PRO_AUTOLINK_OPTION_NAME, []);
        if (isset($self->autolink_options["is_show_icon"]) && $self->autolink_options["is_show_icon"]) {
            $self->hyperlink_icon_svg = ' <svg class="btl_autolink_icon_svg" enable-background="new 0 0 64 64"  viewBox="0 0 64 64"  xmlns="http://www.w3.org/2000/svg"><g><g ><g><path d="m36.243 29.758c-.16 0-1.024-.195-1.414-.586-3.119-3.119-8.194-3.12-11.314 0-.78.781-2.048.781-2.828 0-.781-.781-.781-2.047 0-2.828 4.679-4.68 12.292-4.679 16.97 0 .781.781.781 2.047 0 2.828-.39.391-.903.586-1.414.586z"/></g></g><g ><g><path d="m34.829 41.167c-3.073 0-6.146-1.17-8.485-3.509-.781-.781-.781-2.047 0-2.828.78-.781 2.048-.781 2.828 0 3.119 3.119 8.194 3.12 11.314 0 .78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-2.34 2.339-5.413 3.509-8.485 3.509z"/></g></g><g ><g><path d="m41.899 38.243c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l11.172-11.172c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-11.172 11.172c-.39.391-.902.586-1.414.586z"/></g></g><g ><g><path d="m25.071 55.071c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l6.245-6.245c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-6.245 6.245c-.39.391-.902.586-1.414.586z"/></g></g><g ><g><path d="m10.929 40.929c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828l11.172-11.171c.781-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828l-11.172 11.171c-.391.39-.903.586-1.414.586z"/></g></g><g ><g><path d="m32.684 19.175c-.16 0-1.023-.195-1.414-.585-.781-.781-.781-2.047 0-2.829l6.245-6.246c.781-.781 2.047-.781 2.829 0 .781.781.781 2.047 0 2.829l-6.245 6.246c-.391.389-.904.585-1.415.585z"/></g></g><g ><g><path d="m18 57.935c-3.093 0-6.186-1.15-8.485-3.45-4.6-4.6-4.6-12.371 0-16.971.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-3.066 3.066-3.066 8.248 0 11.314s8.248 3.066 11.314 0c.78-.781 2.048-.781 2.828 0 .781.781.781 2.047 0 2.828-2.299 2.301-5.392 3.451-8.485 3.451z"/></g></g><g ><g><path d="m53.071 27.071c-.16 0-1.024-.195-1.414-.586-.781-.781-.781-2.047 0-2.828 3.066-3.066 3.066-8.248 0-11.314s-8.248-3.066-11.314 0c-.78.781-2.048.781-2.828 0-.781-.781-.781-2.047 0-2.828 4.6-4.6 12.371-4.6 16.971 0s4.6 12.371 0 16.971c-.391.39-.903.585-1.415.585z"/></g></g></g></svg>';
            add_action('wp_head', [$self, 'autolink_css']);
        }
        add_filter('the_content', [$self, 'add_autolinks']);
        add_filter('get_the_excerpt', [$self, 'add_autolinks'], 20);
    }

    public function add_autolinks($content)
    {
        if (is_attachment() || is_feed()) {
            return $content;
        }
        $ID = get_the_ID();
        // skip if disable
        if (get_post_meta($ID, 'betterlinks_is_disable_auto_keyword', true)) {
            return $content;
        }
        $this->unique_number = wp_rand(0, 99999);
        // placeholder variables
        $btl_plc_space = '_spt_' . $this->unique_number . '_s#pt_' . $this->unique_number;
        $btl_plc_slash = '_slsh_' . $this->unique_number . '_sl#sh_' . $this->unique_number;
        $btl_plc_colon = '_slcln_' . $this->unique_number . '_slc#ln_' . $this->unique_number;
        $btl_plc_svg = '_svgln_' . $this->unique_number . '_svg#ln_' . $this->unique_number;
        $current_permalink = get_the_permalink();
        if (isset($this->autolink_options['is_autolink_in_heading']) && !$this->autolink_options['is_autolink_in_heading']) {
            $content = preg_replace_callback("/\<h[1-6].*\<\/h[1-6]\>/isU", function ($matches) use ($btl_plc_space) {
                return str_replace(' ', $btl_plc_space, $matches[0]);
            }, $content);
        }
        // post type
        $post_type = get_post_type($ID);
        $post_category = get_the_category($ID);
        $post_category = (!empty($post_category) ? wp_list_pluck($post_category, 'slug') : []);
        $post_tags = get_the_tags($ID);
        $post_tags = (!empty($post_tags) ? wp_list_pluck($post_tags, 'slug') : []);
        $keywords = $this->get_keywords();
        $content = $this->apply_protected_tags($content);
        $content = $this->apply_protected_tags_and_attribute_only($content);
        foreach ($keywords as $item) {
            if (
                // check keyword and link id not empty
                (empty($item['keywords']) && empty($item['link_id']))
                // check post type
                || (!empty($item['post_type']) && !in_array($post_type, $item['post_type']))
                // check category
                || (!empty($item['category']) && count(array_intersect($post_category, $item['category'])) === 0)
                // check tags
                || (!empty($item['tags']) && count(array_intersect($post_tags, $item['tags'])) === 0)
            ) {
                continue;
            }
            $link = current(\BetterLinks\Helper::get_link_by_ID($item['link_id']));
            if (
                // check if shortlink exist
                !isset($link['short_url']) ||
                // check if target_url exist
                !isset($link['target_url']) ||
                // check if in the same page as the target url
                $this->make_url_string_comparable($link['target_url']) == $this->make_url_string_comparable($current_permalink)
            ) {
                continue;
            }
            $tags = $this->fix_for_apostophie($item['keywords']);
            $short_url = \BetterLinks\Helper::generate_short_url($link['short_url']);
            $short_url = str_replace(["/", ":"], [$btl_plc_slash, $btl_plc_colon], $short_url);
            $search_mode = 'iu';
            if ($item['case_sensitive'] == true) {
                $search_mode = 'u';
            }
            $attribute = $this->get_link_attributes($item);
            $keyword_before = (!empty($item['keyword_before']) ? $this->fix_for_apostophie($item['keyword_before']) : '');
            $keyword_after = (!empty($item['keyword_after']) ? $this->fix_for_apostophie($item['keyword_after']) : '');
            $left_boundary = (!empty($item['left_boundary']) ? $this->get_boundary($item['left_boundary']) : '');
            $right_boundary = (!empty($item['right_boundary']) ? $this->get_boundary($item['right_boundary']) : '');
            $limit = (int) (!empty($item['limit']) ? $item['limit'] : 100);
            // step 1: added placeholder
            $content = preg_replace_callback(
                '/\b(' . $keyword_before . ')(' . $left_boundary . ')(' . $tags . ')(' . $right_boundary . ')(' . $keyword_after . ')\b/' . $search_mode,
                array($this, 'replace_keyword_by_placeholder'),
                $content,
                $limit
            );
            // step 2: replace placeholer to link
            $content = preg_replace(
                '/(\[alk\])/iu',
                '<a ' .
                    $btl_plc_space .
                    'class="btl_autolink_hyperlink" ' .
                    $btl_plc_space .
                    'href=' . $short_url .
                    $btl_plc_space .
                    $attribute . '>' .
                    $btl_plc_svg,
                $content
            );

            $content = preg_replace('/(\[\/alk\])/iu', "</a>", $content);
        }
        $content = $this->remove_protected_tags($content);
        $content = $this->remove_protected_tags_and_attribute_only($content);
        // step 3: remove unnecessary strings
        $content = str_replace([
            '#rnd5btl#' . $this->unique_number . '#/rnd5btl#',
            $btl_plc_space,
            $btl_plc_colon,
            $btl_plc_slash,
            $btl_plc_svg,
        ], [
            '',
            ' ',
            ':',
            '/',
            $this->hyperlink_icon_svg
        ], $content);
        return $content;
    }
    public function replace_keyword_by_placeholder($match)
    {
        return $match[1] . $match[2] . '[alk]' . $match[3] . '[/alk]' . $match[4] . $match[5];
    }

    public function get_keywords()
    {
        $keywords = \BetterLinks\Helper::get_keywords();
        $keywords = $this->prepare_keywords($keywords);
        return $keywords;
    }

    public function prepare_keywords($keywords)
    {
        if (is_array($keywords)) {
            foreach ($keywords as $key => &$value) {
                $temp = json_decode($value, true);
                $tags = $this->keywords_to_tags_generator($temp['keywords']);
                $temp['keywords'] = $tags;
                $value = $temp;
            }
        }
        return $keywords;
    }

    public function keywords_to_tags_generator($string)
    {
        $string = trim($string);
        $string = preg_replace('/\,\s+|,+/', '|', $string);
        return $string;
    }
    public function get_boundary($data)
    {
        $boundary = '';
        switch ($data) {
            case 'generic':
                $boundary = '\b';
                break;

            case 'whitespace':
                $boundary = '\b \b';
                break;

            case 'comma':
                $boundary = ',';
                break;

            case 'point':
                $boundary = '\.';
                break;

            case 'none':
                $boundary = '';
                break;
        }
        return $boundary;
    }
    public function get_link_attributes($item)
    {
        // $empty_placeholder added to make it unique string so that, strings like 'target','rel','nofollow' don't get autolinked/hyperlinked
        $empty_placeholder = '#rnd5btl#' . $this->unique_number . '#/rnd5btl#';
        $attribute = ' ';
        if ($item['open_new_tab'] == true) {
            $attribute .= $empty_placeholder . 'target="' . $empty_placeholder . '_blank"';
        }
        if ($item['use_no_follow'] == true) {
            $attribute .= $empty_placeholder . 'rel="' . $empty_placeholder . 'nofollow"';
        }
        return $attribute;
    }
    public function apply_protected_tags($content)
    {
        $content = preg_replace_callback(
            '/<(a)(\s+[^>]*)?>(.*?)<\/(a)>|<(img)(\s+[^>]*)?>(.*?)/u',
            array($this, 'replace_protected_tags_by_placeholder'),
            $content
        );
        return $content;
    }
    public function apply_protected_tags_and_attribute_only($content)
    {
        $content = preg_replace_callback(
            '/<(.*?)>/u',
            array($this, 'replace_protected_tags_and_attribute_only_by_placeholder'),
            $content
        );
        return $content;
    }

    public function replace_protected_tags_by_placeholder($match)
    {
        $position = count($this->protected_tags_content_lists);
        array_push($this->protected_tags_content_lists, $match[0]);
        return '[alkpt]' . $position . '[/alkpt]';
    }
    public function replace_protected_tags_and_attribute_only_by_placeholder($match)
    {
        $position = count($this->protected_tags_content_lists);
        array_push($this->protected_tags_content_lists, $match[0]);
        return '[alkpta]' . $position . '#rnd5btl#' . $this->unique_number . '#/rnd5btl#';
    }
    public function remove_protected_tags($content)
    {
        $content = preg_replace_callback(
            '/\[alkpt\](\d+)\[\/alkpt\]/u',
            array($this, 'replace_protected_placeholder_by_tags'),
            $content
        );
        return $content;
    }
    public function remove_protected_tags_and_attribute_only($content)
    {
        $content = preg_replace_callback(
            '/\[alkpta\](\d+)/u',
            array($this, 'replace_protected_placeholder_by_tags_and_attribute_only'),
            $content
        );
        return $content;
    }
    public function replace_protected_placeholder_by_tags($match)
    {
        return $this->protected_tags_content_lists[$match[1]];
    }
    public function replace_protected_placeholder_by_tags_and_attribute_only($match)
    {
        return $this->protected_tags_content_lists[$match[1]];
    }
    public function autolink_css()
    {
?>
        <style>
            a.btl_autolink_hyperlink {
                position: relative !important;
                padding: 0 0 0 22px !important;
                display: inline-block;
            }

            svg.btl_autolink_icon_svg {
                width: 16px !important;
                height: 16px !important;
                left: 4px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                position: absolute !important;
            }
        </style>
<?php
    }
    public function make_url_string_comparable($url_string = "")
    {
        return rtrim(strtolower(preg_replace('/https?:\/\//i', '', $url_string)), "/");
    }
    public function fix_for_apostophie($tags = "")
    {
        return preg_replace("/\’|\‘|\'|\&\#8217\;|\&\#8219\;/", "(?:[\'\’\‘]|\&\#8217\;|\&\#8219\;)", $tags);
    }
}
