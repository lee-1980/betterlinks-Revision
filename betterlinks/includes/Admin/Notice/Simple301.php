<?php

namespace BetterLinks\Admin\Notice;

use BetterLinks\Abstracts\MigrationNotice;

class Simple301 extends MigrationNotice
{
    public static $pagenow;
    public static function init()
    {
        $self = new self();
        global $pagenow;
        $self::$pagenow = $pagenow;
        if (defined('SIMPLE301REDIRECTS_VERSION') && !get_option('betterlinks_notice_s301r_migrate')) {
            if (!get_option('betterlinks_hide_notice_s301r_migrate')) {
                add_action('admin_notices', [$self, 'migration_notice']);
                add_action('admin_print_footer_scripts', [$self, 'admin_scripts']);
            }
        } elseif (defined('SIMPLE301REDIRECTS_VERSION') && get_option('betterlinks_notice_s301r_migrate')) {
            if (!get_option('betterlinks_hide_notice_s301r_deactive')) {
                if (!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] !== '301options')) {
                    add_action('admin_notices', [$self, 'deactive_notice']);
                }
                add_action('admin_print_footer_scripts', [$self, 'admin_scripts']);
            }
        }
    }

    public function migration_notice()
    {
        ?>
        <div class="notice notice-info betterlinks-notice-simple301redirects-migrate <?php echo self::$pagenow !== 'admin.php' ? 'is-dismissible' : ''; ?>">
            <p>
                <?php _e('Whoops! You are already using Simple 301 Redirects on your website. To migrate your Simple 301 Redirects data to BetterLinks, click here.', 'betterlinks'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=betterlinks-settings&migration=simple301redirects')); ?>" class="button button-primary"><?php _e(
            'Start Migration',
            'betterlinks'
        ); ?></a>
            </p>
        </div>
        <?php
    }
    public function deactive_notice()
    {
        ?>
        <div class="notice notice-error betterlinks-notice-deactive-simple301redirects <?php echo self::$pagenow !== 'admin.php' ? 'is-dismissible' : ''; ?>">
            <p>
                <?php _e('All Simple 301 Redirects have been successfully migrated to BetterLinks. You can now safely deactivate Simple 301 Redirects on your website.', 'betterlinks'); ?>
                <a href="#" class="button button-primary deactive"><?php _e('Deactivate Simple 301 Redirects', 'betterlinks'); ?></a>
            </p>
        </div>
        <?php
    }

    public function admin_scripts()
    {
        $nonce = wp_create_nonce('betterlinks_admin_nonce'); ?>
		<script type='text/javascript'>
		jQuery( document ).ready(function() {
			jQuery('.betterlinks-notice-deactive-simple301redirects a.deactive').on('click', function(e){
				e.preventDefault();
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/deactive_simple301redirects',
					'security': "<?php echo $nonce; ?>"
				}, function(response) {
					if(response.success){
						location.reload(true); 
					}
				});
			})
			jQuery('.betterlinks-notice-deactive-simple301redirects button.notice-dismiss').on('click', function(){
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/migration_simple301redirects_notice_hide',
					'security': "<?php echo $nonce; ?>",
					'type': 'deactive'
				}, function(response) {});
			})
			jQuery('.betterlinks-notice-simple301redirects-migrate button.notice-dismiss').on('click', function(){
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/migration_simple301redirects_notice_hide',
					'security': "<?php echo $nonce; ?>",
					'type': 'migrate'
				}, function(response) {});
			})
		});
		</script>
		<?php
    }
}
