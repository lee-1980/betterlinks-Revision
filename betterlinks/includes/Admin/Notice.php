<?php

namespace BetterLinks\Admin;

use BetterLinks\Admin\WPDev\PluginUsageTracker;

use PriyoMukul\WPNotice\Notices;

class Notice
{
    private $opt_in_tracker = null;

    const ASSET_URL  = BETTERLINKS_ASSETS_URI;

    public function __construct()
    {
        $this->usage_tracker();
        $this->notices();
        // $this->review_notice();
        Notice\PrettyLinks::init();
        Notice\Simple301::init();
        Notice\ThirstyAffiliates::init();
    }

public function usage_tracker()
    {
        $this->opt_in_tracker = PluginUsageTracker::get_instance(BETTERLINKS_PLUGIN_FILE, [
            'opt_in'       => true,
            'goodbye_form' => true,
            'item_id'      => '720bbe6537bffcb73f37',
        ]);
        $this->opt_in_tracker->set_notice_options(array(
            'notice'       => __('Want to help make <strong>BetterLinks</strong> even more awesome? Be the first to get access to <strong>BetterLinks PRO</strong> with a huge <strong>50% Early Bird Discount</strong> if you allow us to track the non-sensitive usage data.', 'betterlinks'),
            'extra_notice' => __('We collect non-sensitive diagnostic data and plugin usage information. Your site URL, WordPress & PHP version, plugins & themes and email address to send you the discount coupon. This data lets us make sure this plugin always stays compatible with the most popular plugins and themes. No spam, I promise.', 'betterlinks'),
        ));
        $this->opt_in_tracker->init();
    }

    public function notices(){

        $notices = new Notices([
            'id'          => 'betterlinks',
            'store'       => 'options',
            'storage_key' => 'notices',
            'version'     => '1.0.0',
            'lifetime'    => 3,
            'styles'      => self::ASSET_URL . 'css/betterlinks-admin-notice.css',
        ]);

        global $betterlinks;
        $current_user = wp_get_current_user();
        $total_links = (is_array($betterlinks) && isset($betterlinks['links']) ? count($betterlinks['links']) : 0);

        $review_notice = sprintf(
            '%s, %s! %s',
            __('Howdy', 'betterlinks'),
            $current_user->user_login,
            sprintf(
                __('👋 You have created %d Shortened URLs so far 🎉 If you are enjoying using BetterLinks, feel free to leave a 5* Review on the WordPress Forum.', 'betterlinks'),
                $total_links
            )
        );

        $_review_notice = [
            'thumbnail' => self::ASSET_URL . 'images/logo-large.svg',
            'html' => '<p>'. $review_notice .'</p>',
            'links' => [
                'later' => array(
                    'link' => 'https://wordpress.org/plugins/betterlinks/#reviews',
                    'target' => '_blank',
                    'label' => __( 'Ok, you deserve it!', 'betterlinks' ),
                    'icon_class' => 'dashicons dashicons-external',
                ),
                'allready' => array(
                    'label' => __('I already did', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-smiley',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                ),
                'maybe_later' => array(
                    'label' => __('Maybe Later', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-calendar-alt',
                    'attributes' => [
                        'data-later' => true
                    ],
                ),
                'support' => array(
                    'link' => 'https://wpdeveloper.com/support',
                    'label' => __('I need help', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-sos',
                ),
                'never_show_again' => array(
                    'label' => __('Never show again', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-dismiss',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                )
            ]
        ];

        $notices->add(
            'review',
            $_review_notice,
            [
                'start'       => $notices->strtotime( '+20 day' ),
                'recurrence'  => 30,
                'refresh'     => BETTERLINKS_VERSION,
                'dismissible' => true,
            ]
        );

        $notices->add(
            'opt_in',
            [ $this->opt_in_tracker, 'notice' ],
            [
                'classes'     => 'updated put-dismiss-notice',
                'start'       => $notices->strtotime( '+25 day' ),
                'refresh'     => BETTERLINKS_VERSION,
                'dismissible' => true,
                'do_action'   => 'wpdeveloper_notice_clicked_for_betterlinks',
                'display_if'  => ! is_array( $notices->is_installed( 'betterlinks-pro/betterlinks-pro.php' ) )
            ]
        );


        $lifetime_notice = [
            'thumbnail' => self::ASSET_URL . 'images/logo-large.svg',
            'html' => sprintf(
                '<p>%1$s</p>',
                sprintf(
                    __('BetterLinks Reached 10,000+ Users! 🎉 As a gratitude towards all of you, we are offering <strong>HUGE</strong> discounts on <strong>LIFETIME</strong> plan, starting now at <strong>$199</strong>! <strong><a class="button button-small button-primary" href="%1$s" target="_blank">%2$s</a></strong>', 'betterlinks'),
                    esc_url('https://betterlinks.io/#pricing'),
                    __('Grab The Deal', 'betterlinks')
                )
            ),
        ];

        $notices->add(
            'lifetime_pro',
            $lifetime_notice,
            [
                'classes'     => 'updated put-dismiss-notice',
                'start'       => $notices->strtotime( '+35 day' ),
                'refresh'     => BETTERLINKS_VERSION,
                'recurrence'  => 7,
                'expire'      => strtotime('11:59:59pm 20th October, 2022'),
                'dismissible' => true,
                'display_if'  => ! is_array( $notices->is_installed( 'betterlinks-pro/betterlinks-pro.php' ) )
            ]
        );

		$_black_friday_notice_message = sprintf(
			'<p style="margin: 0"><strong>%s</strong>: %s <a class="button button-small" href="%s" target="_blank">%s</a></p>',
			__('Black Friday Exclusive', 'templately'),
			__( '🎉 SAVE up to 40% & access to BetterLinks features.', 'betterlinks' ),
			esc_url('https://betterlinks.io/#pricing'),
			__( 'Grab The Deal', 'betterlinks' )
		);

		$_black_friday_notice = [
            'thumbnail' => self::ASSET_URL . 'images/logo-large.svg',
            'html' => $_black_friday_notice_message,
        ];

        $notices->add(
            'black_friday_notice',
            $_black_friday_notice,
            [
                'start'       => $notices->time(),
                'recurrence'  => false,
                'dismissible' => true,
                "expire"      => 1669852799,
                'display_if'  => ! is_array( $notices->is_installed( 'betterlinks-pro/betterlinks-pro.php' ) )
            ]
        );

        $notices->init();
    }

    public function review_notice()
    {
        $notice = new WPDev\WPDevNotice(BETTERLINKS_PLUGIN_BASENAME, BETTERLINKS_VERSION);

        /**
         * Current Notice End Time.
         * Notice will dismiss in 3 days if user does nothing.
         */
        $notice->cne_time = '7 Day';
        /**
         * Current Notice Maybe Later Time.
         * Notice will show again in 7 days
         */
        $notice->maybe_later_time = '7 Day';

        $notice->text_domain = 'betterlinks';

        $scheme = (parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)) ? '&' : '?';
        $url = $_SERVER['REQUEST_URI'] . $scheme;
        $notice->links = [
            'review' => array(
                'later' => array(
                    'link' => 'https://wordpress.org/plugins/betterlinks/#reviews',
                    'target' => '_blank',
                    'label' => __('Ok, you deserve it!', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-external',
                ),
                'allready' => array(
                    'link' => $url,
                    'label' => __('I already did', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-smiley',
                    'data_args' => [
                        'dismiss' => true,
                    ],
                ),
                'maybe_later' => array(
                    'link' => $url,
                    'label' => __('Maybe Later', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-calendar-alt',
                    'data_args' => [
                        'later' => true,
                    ],
                ),
                'support' => array(
                    'link' => 'https://wpdeveloper.com/support',
                    'label' => __('I need help', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-sos',
                ),
                'never_show_again' => array(
                    'link' => $url,
                    'label' => __('Never show again', 'betterlinks'),
                    'icon_class' => 'dashicons dashicons-dismiss',
                    'data_args' => [
                        'dismiss' => true,
                    ],
                ),
            ),
        ];

        /**
         * This is review message and thumbnail.
         */
        global $betterlinks;
        $current_user = wp_get_current_user();
        $total_links = (is_array($betterlinks) && isset($betterlinks['links']) ? count($betterlinks['links']) : 0);
        $notice->message('review', '<p>'.esc_html__('Howdy, ', 'betterlinks') . $current_user->user_login . esc_html__('! 👋 You have created ', 'betterlinks'). $total_links.' '.esc_html__('Shortened URLs so far 🎉 If you are enjoying using BetterLinks, feel free to leave a 5* Review on the WordPress Forum.', 'betterlinks').'</p>');
        $notice->thumbnail('review', plugins_url('assets/images/logo-large.svg', BETTERLINKS_PLUGIN_BASENAME));

        $notice->options_args = array(
            'notice_will_show' => [
                'opt_in' => $notice->timestamp,
                'upsale' => $notice->makeTime($notice->timestamp, '7 Day'),
                'review' => $notice->makeTime($notice->timestamp, '3 Day'), // after 3 days
            ],
        );
        // main notice init
        $notice->init();
    }
}
