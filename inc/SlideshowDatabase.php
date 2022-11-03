<?php

namespace BCLibCoop;

class SlideshowDatabase
{
    private static $dbVersion = '1.4';

    public static function activate()
    {
        self::createDbTable();
        self::migrateTime();
        update_option('_slideshow_db_version', self::$dbVersion);
    }

    /**
     * Create slideshow-related tables any time a blog
     * loads this plugin and that blog does _not_already_
     * have the necessary tables.
     **/
    public static function createDbTable()
    {
        global $wpdb;

        $slideshow_db_version = get_option('_slideshow_db_version', '0.0');

        if (version_compare($slideshow_db_version, self::$dbVersion, '<')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $charset_collate = $wpdb->get_charset_collate();

            $table_name = $wpdb->prefix . 'slideshows';
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

            $wpdb->query("ALTER TABLE `{$wpdb->prefix}slideshows` CHANGE `date` `date` DATETIME  NOT NULL  DEFAULT CURRENT_TIMESTAMP");

            $table_name = $wpdb->prefix . 'slideshow_slides';
            $sql = "CREATE TABLE $table_name (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `slideshow_id` int(11) DEFAULT NULL,
                        `post_id` int(11) DEFAULT NULL,
                        `slide_link` varchar(250) DEFAULT NULL,
                        `ordering` tinyint(4) NOT NULL DEFAULT '0',
                        `text_title` varchar(250) DEFAULT NULL,
                        `text_content` text DEFAULT NULL,
                        PRIMARY KEY (`id`)
                        ) $charset_collate;";
            dbDelta($sql);

            $wpdb->query("ALTER TABLE `{$wpdb->prefix}slideshow_slides` CHANGE `slideshow_id` `slideshow_id` INT(11)  NULL  DEFAULT NULL");
        }
    }

    public static function migrateTime()
    {
        global $wpdb;

        $slideshow_db_version = get_option('_slideshow_db_version', '0.0');

        if (version_compare($slideshow_db_version, '1.4', '<')) {
            $speed = 500; // transition time
            $pause = 4000; // linger time
            $auto = true;

            $tag = '_slideshow_';

            // Get all db options, quicker, probably already cached
            $alloptions = wp_load_alloptions();

            foreach ($alloptions as $name => $val) {
                // Just the slideshow options please
                if (strpos($name, $tag) !== 0) {
                    continue;
                }

                $name = str_replace($tag, '', $name);

                if ($name === 'speed' || $name === 'pause') {
                    $$name = (int) $val;
                }

                if ($name === 'auto' || $name === 'autoStart') {
                    $val = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $auto = $auto & $val;
                }

                // delete option - not DB version
                if ($name !== 'db_version') {
                    delete_option($tag . $name);
                }
            }

            $show_options = maybe_serialize([
                'coop_transition_time' => $speed < 500 ? 'short' : ($speed > 600 ? 'long' : 'medium'),
                'coop_pause' => $pause < 4000 ? 'short' : ($pause > 6000 ? 'long' : 'medium'),
            ]);

            $wpdb->update($wpdb->prefix . 'slideshows', ['options' => $show_options], ['options' => null]);
        }
    }
}
