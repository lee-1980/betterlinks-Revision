<?php
namespace BetterLinks;

class Installer extends \WP_Background_Process
{
    use Traits\DBTables;
    use Traits\DBMigrate;
    protected $wpdb;
    protected $charset_collate;
    protected $action = 'betterlinks_background_task';
    public $activation;
    public $migration;
    public $db_version;

    public function __construct()
    {
        parent::__construct();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        $this->activation = ['create_db_tables', 'db_migration', 'fix_betterlinks_db', 'insert_terms_data', 'create_json_files', 'save_settings', 'update_json_links'];
        $this->migration = ['db_migration', 'fix_betterlinks_db', 'update_json_links', 'clear_cache'];
        $this->db_version = Helper::btl_get_option('betterlinks_db_version');
    }

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($item)
    {
        if (method_exists($this, $item)) {
            try {
                $this->$item();
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error('BetterLinks background task triggered fatal error for callback ' . esc_html($item), E_USER_WARNING); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
                }
            }
        } elseif(!(strpos($item, "prli_links-") === false)) {
            $item = absint(substr($item, 11)); // getting the ID(number) by deleting 'prli_links-' (used 11 because the length of 'prli_links-' is 11)
            $migrator = new \BetterLinks\Tools\Migration\PTLOneClick();
            if( ! $migrator->insert_link( $item ) ) {
                return true;
            }
        } elseif(!(strpos($item, "prli_clicks-") === false)) {
            $item = absint(substr($item, 12)); // getting the ID(number) by deleting 'prli_clicks-' (used 12 because the length of 'prli_clicks-' is 12)
            $migrator = new \BetterLinks\Tools\Migration\PTLOneClick();
            if( ! $migrator->insert_click( $item ) ) {
                return true;
            }
        } elseif(
            $item === "betterlinks_notice_ptl_migrate" || 
            $item === "betterlinks_ptl_links_migrated" || 
            $item === "betterlinks_ptl_clicks_migrated"
        ){
            $this->after_migration_done();
            Helper::btl_update_option($item, true);
        }
        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        parent::complete();
        // Show notice to user or perform some other arbitrary task...
    }

    public function after_migration_done(){
        // 'betterlinks/admin/after_import_data' hook's work done here
        $Cron = new \BetterLinks\Cron();
        $Cron->write_json_links();
        $Cron->analytics();
        Helper::clear_query_cache();
    }

    public function create_db_tables()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $this->createBetterLinksTable();
        $this->createBetterTermsTable();
        $this->createBetterTermsRelationshipsTable();
        $this->createBetterClicksTable();
        $this->createBetterLinkMetaTable();
        // set plugin version in 'option table' if not already setted 
        // (i.e. when this plugin gets installed on a site for the very first time)
        if (!Helper::btl_get_option('betterlinks_version')) {
            Helper::btl_update_option('betterlinks_version', BETTERLINKS_VERSION, true);
        }
        // set db version in 'option table' if not already setted 
        // (i.e. when this plugin gets installed on a site for the very first time)
        if (!Helper::btl_get_option('betterlinks_db_version')) {
            Helper::btl_update_option('betterlinks_db_version', BETTERLINKS_DB_VERSION, true);
        }
    }

    public function insert_terms_data()
    {
        try {
            Helper::insert_term([
                'term_name' => 'Uncategorized',
                'term_slug' => 'uncategorized',
                'term_type' => 'category',
            ]);
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }

    public function save_settings()
    {
        if (!Helper::btl_get_option(BETTERLINKS_LINKS_OPTION_NAME)) {
            $value = [
                'redirect_type'         => '307',
                'nofollow'   		    => true,
                'sponsored'  	        => '',
                'track_me'   		    => true,
                'param_forwarding'      => '',
                'wildcards'  	        => false,
                'disablebotclicks'      => false,
                'is_allow_gutenberg'    => true,
                'force_https'   	    => false,
                'prefix'                => 'go',
                'is_allow_qr'           => false,
                'is_random_string'      => false,
                'is_autolink_icon'      => false,
                'is_autolink_headings'  => true,
                'is_case_sensitive'     => false,
            ];
            Helper::btl_update_option(BETTERLINKS_LINKS_OPTION_NAME, json_encode($value));
        }
    }

    /**
     * Create files/directories.
     */
    public function create_json_files()
    {
        $emptyContent = '{}';
        $files = [
            [
                'base' => BETTERLINKS_UPLOAD_DIR_PATH,
                'file' => 'index.html',
                'content' => '',
            ],
            [
                'base' => BETTERLINKS_UPLOAD_DIR_PATH,
                'file' => 'links.json',
                'content' => $emptyContent,
            ],
            [
                'base' => BETTERLINKS_UPLOAD_DIR_PATH,
                'file' => 'clicks.json',
                'content' => $emptyContent,
            ],
        ];

        foreach ($files as $file) {
            if (wp_mkdir_p($file['base']) && !file_exists(trailingslashit($file['base']) . $file['file'])) {
                $file_handle = @fopen(trailingslashit($file['base']) . $file['file'], 'wb');
                if ($file_handle) {
                    fwrite($file_handle, $file['content']);
                    fclose($file_handle);
                }
            }
        }
    }

    public function update_json_links()
    {
        $Cron = new Cron();
        $Cron->write_json_links();
    }

    public function db_migration()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        if ($this->db_version && $this->db_version != BETTERLINKS_DB_VERSION) {
            if (BETTERLINKS_DB_VERSION == '1.1') {
                $this->db_migration_1_1();
            } elseif (BETTERLINKS_DB_VERSION == '1.2') {
                $this->db_migration_1_2();
            } elseif (BETTERLINKS_DB_VERSION == '1.4') {
                $this->db_migration_1_4();
            } elseif (BETTERLINKS_DB_VERSION == '1.5') {
                $this->createBetterLinkMetaTable();
            }
            if (version_compare($this->db_version, '1.3', '<')) {
                $this->db_migration_1_1();
                $this->db_migration_1_2();
            }
        }
        Helper::btl_update_option('betterlinks_db_version', BETTERLINKS_DB_VERSION);
    }

    public function clear_cache()
    {
        Helper::clear_query_cache();
    }

    public function fix_betterlinks_db()
    {
        $btl_db_alter_options = Helper::btl_get_option(BETTERLINKS_DB_ALTER_OPTIONS);
        $is_favorite_column_exist = isset($btl_db_alter_options["added_favorite_column"]) ? $btl_db_alter_options["added_favorite_column"] : false;
        if (!$is_favorite_column_exist) {
            delete_transient(BETTERLINKS_CACHE_LINKS_NAME);
            global $wpdb;
            $table          = $wpdb->prefix . 'betterlinks';
            $results        = $wpdb->get_col("DESC $table", 0);
            if (in_array("favorite", $results)) {
                Helper::btl_update_option(BETTERLINKS_DB_ALTER_OPTIONS, [
                    "added_favorite_column" => true,
                ]);
            } else {
                $query_result = $wpdb->query("ALTER TABLE $table ADD favorite varchar(255) NOT NULL");
                Helper::btl_update_option(BETTERLINKS_DB_ALTER_OPTIONS, [
                    "added_favorite_column" => $query_result,
                ]);
            }
        }
    }
}