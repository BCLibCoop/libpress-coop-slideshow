<?php

namespace BCLibCoop;

class SlideshowDatabase
{
    private static $dbVersion = '1.3';

    public static function activate()
    {
        self::createDbTable();
    }

    /**
     * Create slideshow-related tables any time a blog
     * loads this plugin and that blog does _not_already_
     * have the necessary tables.
     **/
    public static function createDbTable()
    {
        global $wpdb;

        $slideshow_db_version = get_option('_slideshow_db_version');

        if ($slideshow_db_version !== self::$dbVersion) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $charset_collate = $wpdb->get_charset_collate();

            $table_name = $wpdb->prefix . 'slideshows';
            // TODO: Time, both stay and
            $sql = "CREATE TABLE $table_name (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(60) NOT NULL,
                        `layout` varchar(20) NOT NULL DEFAULT 'no-thumb',
                        `transition` varchar(20) NOT NULL DEFAULT 'horizontal',
                        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `is_active` tinyint(4) NOT NULL DEFAULT '0',
                        `captions` tinyint(4) NOT NULL DEFAULT '0',
                        `options` text DEFAULT NULL,
                        PRIMARY KEY (`id`)
                        ) $charset_collate;";
            dbDelta($sql);

            $table_name = $wpdb->prefix . 'slideshow_slides';
            $sql = "CREATE TABLE $table_name (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `slideshow_id` int(11) NOT NULL,
                        `post_id` int(11) DEFAULT NULL,
                        `slide_link` varchar(250) DEFAULT NULL,
                        `ordering` tinyint(4) NOT NULL DEFAULT '0',
                        `text_title` varchar(250) DEFAULT NULL,
                        `text_content` text DEFAULT NULL,
                        PRIMARY KEY (`id`)
                        ) $charset_collate;";
            dbDelta($sql);

            update_option('_slideshow_db_version', self::$dbVersion);
        }
    }
}
