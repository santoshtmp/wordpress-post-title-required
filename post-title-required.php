<?php

/**
 * Plugin Name: Post Title Required
 * Plugin URI: https://github.com/santoshtmp/post-title-required
 * Description: Post Title Required plugin purpose to make title require field and limit its character.
 * Tags: Title, Required, Charcter Limit, Post Title Required
 * Contributors: santoshtmp7, younginnovations
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * Version: 1.1.1
 * Author: santoshtmp7
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: post-title-required
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// define PTREQ constant named
define('PTREQ_PATH', plugin_dir_path(__FILE__));
define('PTREQ_URL', plugin_dir_url(__FILE__));
define('PTREQ_BASENAME', plugin_basename(__FILE__));

// include file
require_once dirname(__FILE__) . '/include/class-ptreq-check-settings.php';
require_once dirname(__FILE__) . '/include/class-ptreq-settings.php';
//  
new PTREQ_CHECK_SETTINGS();
new PTREQ_SETTINGS();
