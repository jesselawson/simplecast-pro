<?php

/*
 * @wordpress-plugin
 * Plugin Name:       Simplecast Pro
 * Plugin URI:        https://github.com/lawsonry/simplecast-pro
 * Description:       Shortcodes for integrating your Simplecast-hosted podcast into your WordPress site.
 * Version:           1.0.1
 * Author:            Jesse Lawson <jesse@lawsonry.com>
 * Author URI:        https://lawsonry.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simplecast-pro
 * Domain Path:       /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!class_exists('WP_Http')) {
	include_once(ABSPATH . WPINC . '/class-http.php');
}

define( 'SIMPLECAST_PRO_VERSION', '1.0.1' );

//
// GITHUB UPDATER (so we're not dependent on the WordPress theme repo)
//
function handle_github_update() {
	if(class_exists('GitHubUpdater')) {
		new GitHubUpdater('plugin', __FILE__);
	}
}

add_action('admin_init', 'handle_github_update');

//
// SETTINGS PAGE CONFIG
//
function simplecast_pro_settings_init() {
	 
	add_settings_section(
		'simplecast_pro_settings_section',
		'Simplecast Pro Settings',
		'simplecast_pro_settings_section_callback',
		'writing'
	);
 	
 	add_settings_field(
		'simplecast_pro_api_key',
		'Simplecast API Key',
		'simplecast_pro_api_key_callback',
		'writing',
		'simplecast_pro_settings_section'
	);

	register_setting( 'writing', 'simplecast_pro_api_key' );
	
	add_settings_field(
		'simplecast_pro_podcast_id',
		'Simplecast Podcast ID',
		'simplecast_pro_podcast_id_callback',
		'writing',
		'simplecast_pro_settings_section'
	);

	register_setting( 'writing', 'simplecast_pro_podcast_id' );
} 
 
add_action( 'admin_init', 'simplecast_pro_settings_init' );
 
function simplecast_pro_settings_section_callback() {
	 echo '<p>This plugin by <a href="https://lawsonry.com">Jesse Lawson</a> is 100% free and open source. If you want to help support this plugin and all of Jesse\'s work, <a href="https://www.patreon.com/jesselawson">become a patron</a> for as little as $1/month. For help getting started with the Simplecast Pro plugin, head over to <a href="https://lawsonry.com/simplecast-pro-plugin">Jesse\'s website</a>. <strong>Note: You must have a Simplecast API key.</strong> <a href="https://simplecast.com/user/edit">Click here</a> to go to your Simplecast account and generate one.</p>
	 ';
}
 
function simplecast_pro_api_key_callback() {
 	echo '<input name="simplecast_pro_api_key" id="simplecast_pro_api_key" type="text" value="'.sprintf("%s", get_option('simplecast_pro_api_key')).'">';
}

function simplecast_pro_podcast_id_callback() {
	echo '<input name="simplecast_pro_podcast_id" id="simplecast_pro_podcast_id" type="text" value="'.sprintf("%s", get_option('simplecast_pro_podcast_id')).'">';
}

//
// SHORTCODES CONFIG
//

// Minimum required usage:
// [simplecast_player episode="1"]
// Where episode is the number corresponding to $result[0]["number"]
// Optional usage:
// [simplecast_player episode="1" theme="dark"]
// Where episode is the number corresponding to $result[0]["number"] and theme is either "light" or "dark"
function simplecast_player_shortcode($atts) {
	extract(shortcode_atts(array(
		'episode' => '1',
		'theme' => 'light'
	), $atts, 'simplecast_player'));

	// Get the HTML for the player embed
	$podcast_id = get_option('simplecast_pro_podcast_id');
	$simplecast_api_key = get_option('simplecast_pro_api_key');

	// Request the embed code
	$req_url = 'https://api.simplecast.com/v1/podcasts/'.esc_html($podcast_id).'/episodes.json?api_key='.$simplecast_api_key;
	$request = wp_remote_get($req_url);

	// Check if error
	if(is_wp_error($request)) {
		return '<!-- Unable to contact the simplecast api. -->';
	}

	$body = wp_remote_retrieve_body($request);
	$data = json_decode($body, true);
	$episode_uid = "";
	
	// Get the embed id (episode_uid) from the element where "number" = $episode
	foreach($data as $extracted_episode) {
		if($extracted_episode["number"] == $episode) {
			// IMPORTANT: this depends on sharing_url being exactly 25 characters before the uid. At time of writing, it is = https://simplecast.com/s/
			$episode_uid = substr($extracted_episode["sharing_url"], 25);
		}
	}

	if($episode_uid == "") {
		return "<!-- Could not load player for episode $episode. Make sure your episode actually exists, and make sure you're not in the wrong dimension. !-->";
	}
	
	return "<iframe frameborder='0' height='200px' scrolling='no' seamless src='https://embed.simplecast.com/".$episode_uid."?color=f5f5f5' width='100%'></iframe>";
	
}

add_shortcode('simplecast_player', 'simplecast_player_shortcode');


