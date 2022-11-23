<?php
namespace BetterLinks\Traits;

trait DBTables
{
    public function createBetterLinksTable()
    {
        $table_name = $this->wpdb->prefix . 'betterlinks';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            link_author bigint(20) unsigned NOT NULL default '0',
            link_date datetime NOT NULL default '0000-00-00 00:00:00',
            link_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            link_title text NOT NULL,
            link_slug varchar(200) NOT NULL default '',
            link_note text NOT NULL,
            link_status varchar(20) NOT NULL default 'publish',
            nofollow varchar(10),
            sponsored varchar(10),
            track_me varchar(10),
            param_forwarding varchar(10),
            param_struct varchar(255) default NULL,
            redirect_type varchar(255) default '307',
            target_url varchar(255) default NULL,
            short_url varchar(255) default NULL,
            link_order tinyint(11) default 0,
            link_modified datetime NOT NULL default '0000-00-00 00:00:00',
            link_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
			wildcards boolean NOT NULL default 0,
			expire text default NULL,
			dynamic_redirect text default NULL,
            favorite varchar(255),
            PRIMARY KEY  (ID),
            KEY link_slug (link_slug(191)),
            KEY type_status_date (link_status,link_date,ID),
            KEY link_author (link_author),
            KEY link_order (link_order)
        ) $this->charset_collate;";
        dbDelta($sql);
    }

    public function createBetterTermsTable()
    {
        $table_name = $this->wpdb->prefix . 'betterlinks_terms';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            term_name text NOT NULL,
            term_slug varchar(200) NOT NULL default '',
            term_type varchar(15) NOT NULL,
            term_order tinyint(11) default 0,
            PRIMARY KEY  (ID),
            KEY term_slug (term_slug(191)),
            key term_type (term_type),
            key term_order (term_order)
        ) $this->charset_collate;";
        dbDelta($sql);
    }

    public function createBetterTermsRelationshipsTable()
    {
        $table_name = $this->wpdb->prefix . 'betterlinks_terms_relationships';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            term_id bigint(20) default 0,
            link_id bigint(20) default 0,
            PRIMARY KEY  (ID),
            KEY term_id (term_id),
            key link_id (link_id)
        ) $this->charset_collate;";
        dbDelta($sql);
    }
    
    public function createBetterClicksTable()
    {
        $table_name = $this->wpdb->prefix . 'betterlinks_clicks';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ID bigint(20) unsigned NOT NULL auto_increment,
            link_id bigint(20) NOT NULL,
            ip 	varchar(255) NULL,
            browser varchar(255) NULL,
            os varchar(255) NULL,
            referer varchar(255) NULL,
            host varchar(255) NULL,
            uri varchar(255) NULL,
            click_count tinyint(4) NOT NULL default 0, 
            visitor_id varchar(25) NULL,
            click_order tinyint(11) default 0,
            created_at datetime NOT NULL default '0000-00-00 00:00:00',
            created_at_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            rotation_target_url varchar(255) NULL,
            PRIMARY KEY  (ID),
            KEY ip (ip),
            key link_id (link_id),
            key click_order (click_order)
        ) $this->charset_collate;";
        dbDelta($sql);
    }
    
    public function createBetterLinkMetaTable()
    {
        $table_name = $this->wpdb->prefix . 'betterlinkmeta';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            meta_id bigint(20) unsigned NOT NULL auto_increment,
            link_id bigint(20) unsigned NOT NULL default '0',
            meta_key varchar(255) NOT NULL default '',
            meta_value longtext NOT NULL default '',
            PRIMARY KEY  (meta_id),
            KEY link_id (link_id),
            KEY meta_key (meta_key)
        ) $this->charset_collate;";
        dbDelta($sql);
    }
}
