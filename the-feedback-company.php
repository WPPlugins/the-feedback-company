<?php
/*

Plugin Name:	The Feedback Company
Plugin URI:	https://feedbackcompany.nl/
Description:	Integreert The Feedback Company reviews in een Wordpress website (Dutch plugin)
Version:	1.0.2
Author:		Jan Middelkoop
Author URI:	https://www.middelkoop.cc/
License:	GPL2

This plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with this plugin. If not, see:
https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.

*/


// security - stop if file isn't accessed via WP
if (!defined('ABSPATH'))
	exit;


// scripts & css stylesheets

function feedbackcompany_scripts()
{
	wp_enqueue_script('jquery');
	wp_enqueue_script('feedbackcompany_javascript', plugins_url('the-feedback-company.js', __FILE__));
}
function feedbackcompany_styles()
{
	wp_enqueue_style('feedbackcompany_stylesheet', plugins_url('the-feedback-company.css', __FILE__));

	// css options from the admin settings page
	$custom_css = '';
	$options = get_option('feedbackcompany_options');
	if ($options['color_header_text'])
		$custom_css .= '.feedbackcompany-widgetheader { color: '.$options['color_header_text'].' !important; }'."\n";
	if ($options['color_header_background'])
		$custom_css .= '.feedbackcompany-widgetheader { background-color: '.$options['color_header_background'].' !important; }'."\n";
	if ($options['color_text'])
		$custom_css .= '.feedbackcompany-widget { color: '.$options['color_text'].' !important; }'."\n";
	if ($options['color_background'])
		$custom_css .= '.feedbackcompany-widget { background-color: '.$options['color_background'].' !important; }'."\n";
	if ($options['star_size'])
		$custom_css .= '.feedbackcompany-score { line-height: '.($options['star_size'] * 1.1).'px !important; }'."\n";
	if ($options['star_size'])
		$custom_css .= '.feedbackcompany-stars { font-size: '.$options['star_size'].'px !important; line-height: '.$options['star_size'].'px !important; }'."\n";
	if ($options['star_color'])
		$custom_css .= '.feedbackcompany-stars-positive { color: '.$options['star_color'].' !important; }'."\n";
	if ($options['star_color'])
		$custom_css .= '.feedbackcompany-score { color: '.$options['star_color'].' !important; }'."\n";
	if ($options['star_color_negative'])
		$custom_css .= '.feedbackcompany-stars-negative { color: '.$options['star_color_negative'].' !important; }'."\n";

	wp_add_inline_style('feedbackcompany_stylesheet', $custom_css);
}
add_action('wp_enqueue_scripts', 'feedbackcompany_scripts');
add_action('wp_enqueue_scripts', 'feedbackcompany_styles');


// registreren van Wordpress shortcodes

function feedbackcompany_shortcode_summary($atts, $content = "")
{
	return feedbackcompany_api_wp()->simple_score();
}
function feedbackcompany_shortcode_score($atts, $content = "")
{
	return feedbackcompany_api_wp()->widget_score();
}
function feedbackcompany_shortcode_reviews($atts, $content = "")
{
	return feedbackcompany_api_wp()->widget_reviews();
}
function feedbackcompany_shortcode_testimonial($atts, $content = "")
{
	return feedbackcompany_api_wp()->widget_testimonial($atts['id']);
}
add_shortcode('feedbackcompany_summary', 'feedbackcompany_shortcode_summary');
add_shortcode('feedbackcompany_score', 'feedbackcompany_shortcode_score');
add_shortcode('feedbackcompany_reviews', 'feedbackcompany_shortcode_reviews');
add_shortcode('feedbackcompany_testimonial', 'feedbackcompany_shortcode_testimonial');


// registeren van Wordpress widgets

class feedbackcompany_widget_score extends WP_Widget {
	function __construct() {
		parent::__construct(false, 'The Feedback Company Score');
	}

	function widget($args, $instance) {
		extract($args);
		echo $before_widget;
		echo feedbackcompany_api_wp()->widget_score();
		echo $after_widget;
	}
	function form($instance) {
		echo '<p>De vormgeving van dit widget kun je aanpassen via \'Instellingen\' in het menu links.</p>';
	}
}
class feedbackcompany_widget_reviews extends WP_Widget {
	function __construct() {
		parent::__construct(false, 'The Feedback Company Reviews slider');
	}

	function widget($args, $instance) {
		extract($args);
		echo $before_widget;
		echo feedbackcompany_api_wp()->widget_reviews();
		echo $after_widget;
	}
	function form($instance) {
		echo '<p>De vormgeving van dit widget kun je aanpassen via \'Instellingen\' in het menu links.</p>';
	}
}
class feedbackcompany_widget_testimonial extends WP_Widget {
	function __construct() {
		parent::__construct(false, 'The Feedback Company Testimonial');
	}

	function widget($args, $instance) {
		extract($args);
		echo $before_widget;
		echo feedbackcompany_api_wp()->widget_testimonial($instance['review_id']);
		echo $after_widget;
	}
	function form($instance) {
		$review_id = (isset($instance['review_id']) ? $instance['review_id'] : '');
		echo '<p>'
			. 'Review ID:<br><input name="'.$this->get_field_name('review_id').'" type="number" value="'.esc_attr($review_id).'">'
			. '</p>';
		echo '<p>De vormgeving van dit widget kun je aanpassen via \'Instellingen\' in het menu links.</p>';
	}
	function update($new_instance, $old_instance)
	{
		print_r($new_instance);
		return $new_instance;
	}
}
function feedbackcompany_register_widgets() {
	register_widget('feedbackcompany_widget_score');
	register_widget('feedbackcompany_widget_reviews');
	register_widget('feedbackcompany_widget_testimonial');
}
add_action('widgets_init', 'feedbackcompany_register_widgets');


// admin settings page

if (is_admin())
{
	require_once plugin_dir_path(__FILE__).'adminsettings.php';
}


// settings object to interface api with wp

class feedbackcompany_api_ext_wp
{
	private $wp_options;
	function __construct()
	{
		$this->wp_options = get_option('feedbackcompany_options');
	}

	function get_option($key)
	{
		if (!isset($this->wp_options[$key]))
		{
			// backwards compatibility for 1.0 to 1.0.1
			if ($key == 'title_score')
				return 'Klantbeoordelingen';
			if ($key == 'title_reviews')
				return 'Recente beoordelingen';
			if ($key == 'title_testimonial')
				return 'Beoordeling van';
			// end backwards compatibility

			return false;
		}

		return $this->wp_options[$key];
	}
	function get_options($prefix)
	{
		$ret = array();

		foreach ($this->wp_options as $key => $value)
			if (substr($key, 0, strlen($prefix)) == $prefix)
				$ret[substr($key, strlen($prefix))] = $value;

		return $ret;
	}

	function get_cache($key)
	{
		return get_transient('feedbackcompany_'.$key);
	}
	function set_cache($key, $value, $expiration)
	{
		set_transient('feedbackcompany_'.$key, $value, $expiration);
	}
	function delete_cache($key)
	{
		delete_transient('feedbackcompany_'.$key);
	}
	function get_url()
	{
		return plugins_url('', __FILE__);
	}
}


// function to interface with the library API

function feedbackcompany_api_wp()
{
	// include the feedback company php api
	require_once plugin_dir_path(__FILE__).'lib/feedbackcompany_api.php';

	static $fbcapisettings;
	static $fbcapi;

	if (!is_object($fbcapisettings))
		$fbcapisettings = new feedbackcompany_api_ext_wp();
	if (!is_object($fbcapi))
		$fbcapi = new feedbackcompany_api($fbcapisettings);

	return $fbcapi;
}


// function to interface with schema.org dictionary

function feedbackcompany_schemaorg()
{
	// include the feedback company php api
	require_once plugin_dir_path(__FILE__).'lib/feedbackcompany_schemaorg.php';

	static $fbcschemaorg;

	if (!is_object($fbcschemaorg))
		$fbcschemaorg = new feedbackcompany_schemaorg(plugin_dir_path(__FILE__).'lib/');

	return $fbcschemaorg;
}

