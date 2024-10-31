<?php
/*
Plugin Name: Quartz
Plugin URI: http://www.bin-co.com/tools/wordpress/plugins/quartz/
Description: Quartz Plugin lets you show random quotes/tips/links/pictures/something to visitors. These quotes can be added from the admin side.
Version: 1.01.1
Author: Binny V A
Author URI: http://binnyva.com/
*/

/**
 * Add a new menu under Manage, visible for all users with template viewing level.
 */
add_action( 'admin_menu', 'quartz_add_menu_links' );
function quartz_add_menu_links() {
	global $wp_version, $_registered_pages;
	$view_level= 'administrator';
	$page = 'edit.php';
	if($wp_version >= '2.7') $page = 'tools.php';
	
	add_submenu_page($page, __('Manage Quotes', 'quartz'), __('Manage Quotes', 'quartz'), $view_level, 'quartz/all_quotes.php' );
	$code_pages = array('import.php','quote_form.php');
	foreach($code_pages as $code_page) {
		$hookname = get_plugin_page_hookname("quartz/$code_page", '' );
		$_registered_pages[$hookname] = true;
	}
}

/**
 * User Function - Called from the Template
 */
function quartz_show($quote_count = 1, $joiner = '<br />', $mode = 'print') {
	global $wpdb;
	$quotes = $wpdb->get_col("SELECT quote FROM {$wpdb->prefix}quartz_quote WHERE status='1' ORDER BY RAND() LIMIT 0, $quote_count", 0);
	$final = implode($joiner, $quotes);
	
	if($mode == 'return') return $final;
	print $final;
}

function quartz_show_widget() {
	$count = get_option('quartz_widget_quote_count');
	
	print "<li><h3>". get_option('quartz_widget_title') . "</h3>";
	if($count > 1) print "<ul><li>" . quartz_show($count, '</li><li>', 'return') . "</li></ul>";
	else print quartz_show(1,'','return');
	
	print "</li>";
}

/// Quartz as a Sidebar widget
function quartz_widget_init() {
	if (! function_exists("register_sidebar_widget")) return;
	
	function quartz_show_options() {
		if ( $_POST['quartz-submit'] ) {
			update_option('quartz_widget_title', $_REQUEST['quartz_widget_title']);
			update_option('quartz_widget_quote_count', $_REQUEST['quartz_widget_quote_count']);
		}
		echo '<p style="text-align:right;"><label for="quartz_widget_title">' .t('Title').': <input style="width: 200px;" id="quartz_widget_title" name="quartz_widget_title" type="text" value="'.get_option("quartz_widget_title").'" /></label></p>';
		echo '<p style="text-align:right;"><label for="quartz_widget_quote_count">' .t('Number of Quotes to be Shown').': <input style="width: 200px;" id="quartz_widget_quote_count" name="quartz_widget_quote_count" type="text" value="'.get_option("quartz_widget_quote_count").'" /></label></p>';
		echo '<input type="hidden" id="quartz-submit" name="quartz-submit" value="1" />';
	}
	
	register_sidebar_widget('Show Quartz Column', 'quartz_show_widget');
	register_widget_control('Show Quartz Column', 'quartz_show_options', 200, 100);
}
add_action('plugins_loaded', 'quartz_widget_init');


/**
 * Creates tables and upload folder on activation.
 */
add_action('activate_quartz/quartz.php','quartz_activate');
function quartz_activate() {
	global $wpdb;
	
	$database_version = '1';
	$installed_db = get_option('quartz_db_version');
	
	if($database_version != $installed_db) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		// Create the table structure
		dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quartz_quote (
			ID INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
			quote TEXT NOT NULL ,
			status ENUM( '1', '0' ) NOT NULL DEFAULT '1',
			PRIMARY KEY ( `ID` )
			);");
		update_option( "quartz_db_version", $database_version );
	}
	if(!$installed_db) {
		add_option("quartz_widget_title", "Quotes");
		add_option("quartz_widget_quote_count", 1);
		$wpdb->query("INSERT INTO `{$wpdb->prefix}quartz_quote` (`quote` ,`status`) VALUES ('Powered by <a href=\"http://www.bin-co.com/blog/2008/11/quartz-wordpress-plugin/\">Quartz Plugin</a>', '1');");
	}
}
