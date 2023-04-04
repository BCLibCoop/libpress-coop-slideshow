<?php

/**
 * Coop Slideshow Admin
 *
 * A slideshow/carousel for use with the Prototype theme.
 *
 * PHP Version 7
 *
 * @package           BCLibCoop\SlideshowAdmin
 * @author            Erik Stainsby <eric.stainsby@roaringsky.ca>
 * @author            Ben Holt <ben.holt@bc.libraries.coop>
 * @author            Jonathan Schatz <jonathan.schatz@bc.libraries.coop>
 * @author            Sam Edwards <sam.edwards@bc.libraries.coop>
 * @copyright         2013-2022 BC Libraries Cooperative
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Coop Slideshow Admin
 * Description:       Slideshow setup configurator
 * Version:           3.1.1
 * Network:           true
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            BC Libraries Cooperative
 * Author URI:        https://bc.libraries.coop
 * Text Domain:       coop-slideshow
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace BCLibCoop;

// No Direct Access
defined('ABSPATH') || die(-1);

define('COOP_SLIDESHOW_PLUGIN', __FILE__);

/**
 * Require Composer autoloader if installed on it's own
 */
if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

/**
 * Hook on plugins_loaded for friendlier priorities
 */
add_action('plugins_loaded', function () {
    SlideshowDatabase::activate();

    /**
     * Only load admin interfaces for an admin request
     */
    add_action('init', function () {
        if (is_admin()) {
            new SlideshowAdmin();
        }
    });

    /**
     * Only load the frontend features for a frontend request
     */
    add_action('template_redirect', function () {
        new SlideshowFrontend();
    });
});
