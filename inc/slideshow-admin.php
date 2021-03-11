<?php

namespace BCLibCoop;

class SlideshowAdmin
{
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

        /* MAINTENANCE utility
        $del = "DELETE FROM $wpdb->options WHERE option_name='_slideshow_db_version'";
        $wpdb->query($del);
        */

        $slideshow_db_version = get_option('_slideshow_db_version');

        if ('1.2' === $slideshow_db_version) {
            // return or run an update ...
            // error_log('_slideshow_db_version: ' . $slideshow_db_version);
            return;
        }

        // error_log( 'creating the slideshow table' );

        $table_name = $wpdb->prefix . 'slideshows';
        $sql = "CREATE TABLE $table_name ("
            . " id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, "
            . " title varchar(60) NOT NULL, "
            . " layout varchar(20) NOT NULL DEFAULT 'no-thumb', "
            . " transition varchar(20) NOT NULL DEFAULT 'horizontal',"
            . " date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL ,"
            . " is_active tinyint NOT NULL DEFAULT 0, "
            . " captions tinyint NOT NULL DEFAULT 0);";
        $wpdb->query($sql);


        $table_name = $wpdb->prefix . 'slideshow_slides';
        $sql = "CREATE TABLE $table_name ("
            . " id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, "
            . " slideshow_id int(11)  NOT NULL, "
            . " post_id int(11), "
            . " slide_link varchar(250), "
            . " ordering tinyint DEFAULT 0 NOT NULL, "
            . " text_title varchar(250), "
            . " text_content text "
            . " );";
        $wpdb->query($sql);

        update_option('_slideshow_db_version', '1.2');
    }
}
