


<?php
/*
 * Plugin Name: Postebase
 * Plugin URI: https://postebase.com
 * Description: Postebase companion plugin. Add's extra details to rest responses and expands rest api to work with the postebase platform.
 * Version: 0.0.4
 * Requires at least: 6.4
 * Tested up to: 6.5.2
 * Requires PHP: 7.4
 * Author: Postebase
 * Text Domain: postebase
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
!defined("ABSPATH") ? exit() : "";

define("postebase_path", plugin_dir_path(__FILE__));

//require uip_plugin_path . "admin/vendor/autoload.php";
//require uip_plugin_path . "admin/uipress-compiler.php";

require "vendor/autoload.php";

// Start the rest expansion
new \Postebase\Rest\Filters\RestApiExtender();
// Start the updater
$updater = new \Postebase\Update\Updater(__FILE__);
$updater->init();

