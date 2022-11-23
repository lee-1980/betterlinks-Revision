<?php 
namespace BetterLinksPro\Traits;

trait DBTables {
    public function createBetterClicksRotationsTable()
	{
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_name = $this->wpdb->prefix . 'betterlinks_clicks_rotations';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            link_id bigint(20) NOT NULL,
            click_id bigint(20) NOT NULL,
            target_url varchar(255) NULL,
            PRIMARY KEY  (ID),
            key link_id (link_id),
            key click_id (click_id)
        ) $this->charset_collate;";
		dbDelta($sql);
	}
}