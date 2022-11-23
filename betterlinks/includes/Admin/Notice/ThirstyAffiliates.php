<?php

namespace BetterLinks\Admin\Notice;

use BetterLinks\Abstracts\MigrationNotice;

class ThirstyAffiliates extends MigrationNotice
{
    public static $pagenow;
    public static function init()
    {
        if (class_exists('ThirstyAffiliates')) {
            $self = new self();
            global $pagenow;
            $self::$pagenow = $pagenow;

            if (!get_option('betterlinks_notice_ta_migrate')) {
                if (!get_option('betterlinks_hide_notice_ta_migrate')) {
                    add_action('admin_notices', [$self, 'migration_notice']);
                    add_action('admin_print_footer_scripts', [$self, 'admin_scripts']);
                }
            } elseif (get_option('betterlinks_notice_ta_migrate')) {
                if (!get_option('betterlinks_hide_notice_ta_deactive')) {
                    if (!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] !== 'thirstylink')) {
                        add_action('admin_notices', [$self, 'deactive_notice']);
                    }
                    add_action('admin_print_footer_scripts', [$self, 'admin_scripts']);
                }
            }
        }
    }

    public function migration_notice()
    {
        ?>
        <div class="notice notice-info betterlinks-notice-thirstyaffiliates-migrate <?php echo self::$pagenow !== 'admin.php' ? 'is-dismissible' : ''; ?>">
            <p>
                <?php _e('Whoops! You are already using ThirstyAffiliates on your website. To migrate your ThirstyAffiliates data to BetterLinks, click here.', 'betterlinks'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=betterlinks-settings&migration=thirstyaffiliates')); ?>" class="button button-primary"><?php _e(
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
        <div class="notice notice-error betterlinks-notice-deactive-thirstyaffiliates <?php echo self::$pagenow !== 'admin.php' ? 'is-dismissible' : ''; ?>">
            <p>
                <?php _e('All ThirstyAffiliates have been successfully migrated to BetterLinks. You can now safely deactivate ThirstyAffiliates on your website.', 'betterlinks'); ?>
                <a href="#" class="button button-primary deactive"><?php _e('Deactivate ThirstyAffiliates', 'betterlinks'); ?></a>
            </p>
        </div>
        <?php
    }

    public function admin_scripts()
    {
        $nonce = wp_create_nonce('betterlinks_admin_nonce'); ?>
		<script type='text/javascript'>
		jQuery( document ).ready(function() {
			jQuery('.betterlinks-notice-deactive-thirstyaffiliates a.deactive').on('click', function(e){
				e.preventDefault();
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/deactive_thirstyaffiliates',
					'security': "<?php echo $nonce; ?>"
				}, function(response) {
					if(response.success){
						location.reload(true); 
					}
				});
			})
			jQuery('.betterlinks-notice-deactive-thirstyaffiliates button.notice-dismiss').on('click', function(){
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/migration_thirstyaffiliates_notice_hide',
					'security': "<?php echo $nonce; ?>",
					'type': 'deactive'
				}, function(response) {});
			})
			jQuery('.betterlinks-notice-thirstyaffiliates-migrate button.notice-dismiss').on('click', function(){
				jQuery.post(ajaxurl, {
					'action': 'betterlinks/admin/migration_thirstyaffiliates_notice_hide',
					'security': "<?php echo $nonce; ?>",
					'type': 'migrate'
				}, function(response) {});
			})
		});
		</script>
		<?php
    }
}
