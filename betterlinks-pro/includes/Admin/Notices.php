<?php
namespace BetterLinksPro\Admin;

class Notices {
    public static function init(){
        $self = new self();

        add_action('admin_notices', array($self, 'install_core_notice'));
        add_action('wp_ajax_betterlinkspro/admin/install_core_installer', [$self, 'install_core_installer']);
    }
    public function install_core_notice()
    {
        if(did_action('betterlinks_loaded') === 1) return;
        $has_installed = get_plugins();
        $button_text = isset( $has_installed['betterlinks/betterlinks.php'] ) ? __( 'Activate Now!', 'betterdocs-pro' ) : __( 'Install Now!', 'betterdocs-pro' );
        ?>
        <div class="error notice is-dismissible">
			<p><?php echo sprintf( '<strong>%1$s</strong> %2$s <strong>%3$s</strong> %4$s', __( 'BetterLinks Pro', 'betterlinks-pro' ), __( 'requires', 'betterlinks-pro' ), __( 'BetterLinks', 'betterlinks-pro' ), __( 'core plugin to be installed. Please get the plugin now!', 'betterlinks-pro' ) ) ?> <button id="betterlinks-install-core" class="button button-primary"><?php echo $button_text; ?></button></p>
		</div>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                $('#betterlinks-install-core').on('click', function (e) {
                    var self = $(this);
                    e.preventDefault();
                    self.addClass('install-now updating-message');
                    self.text('<?php echo esc_js( 'Installing...' ); ?>');

                    $.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        type: 'post',
                        data: {
                            action: 'betterlinkspro/admin/install_core_installer',
                            _wpnonce: '<?php echo wp_create_nonce('betterlinks_pro_install_core_installer'); ?>',
                        },
                        success: function(response) {
                            self.text('<?php echo esc_js( 'Installed' ); ?>');
                            window.location.href = '<?php echo admin_url( 'admin.php?page=betterlinks' ); ?>';
                        },
                        error: function(error) {
                            self.removeClass('install-now updating-message');
                            alert( error );
                        },
                        complete: function() {
                            self.attr('disabled', 'disabled');
                            self.removeClass('install-now updating-message');
                        }
                    });
                });
            } );
        </script>
        <?php
    }

    public function install_core_installer()
    {
        check_ajax_referer('betterlinks_pro_install_core_installer', '_wpnonce');
        if( ! current_user_can( 'manage_options' ) ) wp_die();
        $has_installed = get_plugins();
        if(isset( $has_installed['betterlinks/betterlinks.php'] )){
            $result = activate_plugin('betterlinks/betterlinks.php', '', false );
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            if ($result === false) {
                wp_send_json_error(__('Plugin couldn\'t be activated.', 'betterlinks-pro'));
            }
            wp_send_json_success(__('BetterLinks is activated!', 'betterlinks-pro'));
            wp_die();
        }

        $result = \BetterLinksPro\Helper::install_plugin('betterlinks');
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success(__('Plugin is installed successfully!', 'betterlinks-pro'));
        wp_die();
    }
}