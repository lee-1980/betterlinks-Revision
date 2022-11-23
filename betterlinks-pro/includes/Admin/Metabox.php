<?php
namespace BetterLinksPro\Admin;

class Metabox
{
    public static function init()
    {
        $self = new self();
        add_action('add_meta_boxes', [$self, 'add_auto_keyword_metabox'], 10, 2);
        add_action('save_post', [$self, 'save_auto_keyword_metabox'], 10, 3);
    }

    public function add_auto_keyword_metabox($post_type, $post)
    {
        add_meta_box('betterlinks-auto-keyword', __('BetterLinks Auto-Link Keywords', 'betterlinks'), [$this, 'auto_keyword_callback'], $post_type, 'side', 'core');
    }

    public function auto_keyword_callback($post)
    {
        $disable_auto_keyword = get_post_meta($post->ID, 'betterlinks_is_disable_auto_keyword', true); ?>
        <p>
            <label>
                <input 
                    type="checkbox" 
                    name="betterlinks_is_disable_auto_keyword" 
                    <?php checked(filter_var($disable_auto_keyword, FILTER_VALIDATE_BOOLEAN), true) ?> 
                />
                <?php esc_html_e('Disable Auto-Link Keywords', 'betterlinks-pro'); ?>
            </label>
        </p>
        <?php
    }

    public function save_auto_keyword_metabox($post_id, $post, $update)
    {
        $disable_auto_keyword = (isset($_POST['betterlinks_is_disable_auto_keyword']) ? filter_var(sanitize_text_field($_POST['betterlinks_is_disable_auto_keyword']), FILTER_VALIDATE_BOOLEAN) : false);
        update_post_meta($post_id, 'betterlinks_is_disable_auto_keyword', $disable_auto_keyword);
    }
}
