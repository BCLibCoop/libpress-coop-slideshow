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
 * @copyright         2013-2021 BC Libraries Cooperative
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Coop Slideshow Admin
 * Description:       Slideshow setup configurator
 * Version:           1.1.3
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

require_once 'inc/slideshow-admin.php';
require_once 'inc/slideshow-defaults.php';
require_once 'inc/slideshow-manager.php';
require_once 'inc/slideshow-frontside.php';

/**
 * Check for DB updates on activation
 */
register_activation_hook(__FILE__, ['BCLibCoop\SlideshowAdmin', 'activate']);

/**
 * Only load admin interfaces for an admin request
 */
add_action('init', function () {
    if (is_admin()) {
        new SlideShowManager();
        new SlideshowDefaults();
    }
});

/**
 * Only load the frontend features for a frontend request
 */
add_action('template_redirect', function () {
    new Slideshow();
});
