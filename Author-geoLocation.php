<?php
/**
 * Author: Tom Thorogood
 * Author URI: http://xenthrax.com
 * Description: Allows authors to geocode their posts using the HTML5 <a href="http://dev.w3.org/geo/api/spec-source.html" target="_blank">Geolocation API</a> and display the location using <a href="http://maps.google.com/" target="_blank">Google Maps</a>. It also supports the <a href="http://code.google.com/p/geo-location-javascript/" target="_blank">geo-location-javascript library</a> and the <a href="http://www.maxmind.com/app/javascript_city" target="_blank">MaxMind GeoIP Javascript Service</a> for backwards compatibility.
 * Plugin Name: Author geoLocation
 * Plugin URI: http://xenthrax.com/wordpress/author-geolocation/
 * Version: 1.1a3
 * 
 * Donate: http://xenthrax.com/donate/
 * 
 * Plugin Shortlink: http://xenthrax.com/author-geolocation/
 * Other Plugins: http://xenthrax.com/wordpress/
 * 
 * WordPress Plugin: https://wordpress.org/extend/plugins/author-geolocation/
 */

/**
 * If you notice any issues or bugs in the plugin please contact me [@link http://xenthrax.com/about/]
 * If you make any revisions to and/or re-release this plugin please contact me [@link http://xenthrax.com/about/]
 */

/**
 * Copyright (c) 2010-2013 Tom Thorogood
 * 
 * This file is part of "Author geoLocation" Wordpress Plugin.
 * 
 * "Author geoLocation" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation version 3.
 * 
 * You may NOT assume that you can use any other version of the GPL.
 *
 * "Author geoLocation" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with "Author geoLocation". If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @package Author geoLocation
 * @since 1.0
 */
class Author_geoLocation {
	/**
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $notices = array();
	
	/**
	 * @access private
	 * @since 1.1
	 * @param string $key
	 * @param bool $esc Optional.
	 * @param bool $echo Optional.
	 * @return string Data
	 */
	function _plugin_data($key, $esc = false, $echo = false) {
		static $plugin_data;
		$key = strtolower($key);
		
		if (is_null($plugin_data)) {
			$plugin_data = get_file_data(__FILE__, array(
				'author'       => 'Author',
				'authoruri'    => 'Author URI',
				'donate'       => 'Donate',
				'name'         => 'Plugin Name',
				'uri'          => 'Plugin URI',
				'version'      => 'Version',
				'shortlink'    => 'Plugin Shortlink',
				'otherplugins' => 'Other Plugins'
			));
		}
		
		if (!array_key_exists($key, $plugin_data) || empty($plugin_data[$key]))
			return false;
		
		$data = $plugin_data[$key];
		
		if ($esc) {
			switch ($key) {
				case 'author':
				case 'name':
				case 'version':
					$data = esc_html($data);
					break;
				case 'authoruri':
				case 'donate':
				case 'uri':
				case 'shortlink':
				case 'otherplugins':
					$data = esc_url($data);
					break;
				/*default:
					return false;*/
			}
		}
		
		$data = apply_filters("{$this->slug('hook')}-plugin-data", $data, $key, $esc, $echo, $plugin_data);
		
		if ($data === NULL)
			return false;
		
		if (!$echo)
			return $data;
		
		echo $data;
		return true;
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $context Optional.
	 * @param bool $esc Optional.
	 * @param bool $echo Optional.
	 * @return string
	 */
	private function slug($context = 'name', $esc = false, $echo = false) {
		if (!is_string($context)) {
			$echo    = $esc;
			$esc     = $context;
			$context = 'name';
		}
		
		switch ($context) {
			case 'plugin':
				$slug = plugin_basename(__FILE__);
				break;
			case 'settings':
				$slug = 'settings_page_' . basename(dirname(__FILE__)) . '/' . basename(__FILE__, '.php');
				break;
			case 'name':
			case 'slug':
				$slug = preg_replace('/[^a-z0-9_\-]/i', '-', $this->_plugin_data('name'));
				break;
			case 'js':
				$slug = preg_replace('/[^a-z0-9_]/i', '_', $this->_plugin_data('name'));
				break;
			case 'hook':
				$slug = 'author-geolocation';
				break;
			default:
				$slug = false;
				break;
		}
		
		if ($context !== 'hook')
			$slug = apply_filters("{$this->slug('hook')}-slug", $slug, $context, $esc, $echo);
		
		if ($slug === false)
			return false;
		
		if ($esc)
			$slug = esc_html($slug);
		
		if (!$echo)
			return $slug;
		
		echo $slug;
		return true;
	}
	
	/**
	 * @access private
	 * @since 1.1
	 * @param bool $opening
	 * @param bool $echo Optional.
	 * @return string Comment tag
	 */
	function _comment_tag($opening, $echo = true) {
		if ($opening)
			$tag = "<!--{$this->_plugin_data('name', true)} - {$this->version(true)}: {$this->_plugin_data('uri', true)}-->\n";
		else
			$tag = "<!--/{$this->_plugin_data('name', true)}-->\n";
		
		$tag = apply_filters("{$this->slug('hook')}-comment-tag", $tag, $opening, $echo);
		
		if ($tag === false)
			return false;
		
		if (!$echo)
			return $tag;
		
		echo $tag;
		return true;
	}
	
	/**
	 * @access public
	 * @since 1.0
	 * @param bool $esc
	 * @param bool $echo
	 * @return string Plugin version
	 */
	function version($esc = false, $echo = false) {
		return $this->_plugin_data('version', $esc, $echo);
	}
	
	/**
	 * @since 1.0
	 */
	function Author_geoLocation() {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}
	
	/**
	 * @since 1.0
	 */
	function __construct() {
		load_plugin_textdomain($this->slug(), false, basename(dirname(__FILE__)) . '/lang');
		$this->add_option('version', $this->version());
		
		foreach ($this->default_options() as $name => $value)
			$this->add_option($name, $value);
		
		foreach (array('admin_head-post-new.php', 'admin_head-post.php') as $filter)
			add_action($filter, array(&$this, '_admin_head_post'));
		
		foreach (array('load-post-new.php', 'load-post.php') as $filter)
			add_action($filter, array(&$this, '_admin_post_init'));
		
		add_action("admin_head-{$this->slug('settings')}", array(&$this, '_admin_head_options'));
		add_action("load-{$this->slug('settings')}", array(&$this, '_options_init'));
		add_action('admin_notices', array(&$this, '_admin_notices'));
		add_action('the_content', array(&$this, '_the_content'));
		add_action('admin_menu', array(&$this, '_admin_menu'));
		add_action('save_post', array(&$this, '_save_post'));
		add_action('wp_head', array(&$this, '_head'));
		add_action('init', array(&$this, '_init'));
		add_shortcode('address', array(&$this, '_address_shortcode'));
		add_shortcode('map', array(&$this, '_map_shortcode'));
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _init() {
		wp_enqueue_script('google-maps', '//maps.google.com/maps/api/js?sensor=false', array(), 3);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_post_init() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('google-maps', '//maps.google.com/maps/api/js?sensor=false', array(), 3);
		
		if ($this->get_option('legacy')) {
			wp_enqueue_script('google-gears', $this->_plugin_url('/js/gears.init.js'), array(), null);
			wp_enqueue_script('geo-location', $this->_plugin_url('/js/geo.js'), array(), '0.4.7');
		}
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _head() {
		$this->_comment_tag(true);
?>
<style type="text/css">
.<?php $this->slug(true, true); ?>{border:1px solid #000;margin:5px 0;padding:5px;text-align:center;}
.<?php $this->slug(true, true); ?>-address{margin:0 0 5px;padding:0;}
.<?php $this->slug(true, true); ?>-map{width:100%;height:250px;overflow:hidden;text-align:center;}
</style>
<?php
		$this->_comment_tag(false);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $name
	 * @param string $value Optional.
	 * @return void
	 */
	private function add_option($name, $value = '') {
		$value = apply_filters("{$this->slug('hook')}-add-option", $value, $name);
		
		if ($value === NULL)
			return;
		
		add_option("{$this->slug()}-{$name}", $value);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $name
	 * @return string Option value
	 */
	private function get_option($name) {
		$value = get_option("{$this->slug()}-{$name}");
		return apply_filters("{$this->slug('hook')}-get-option", $value, $name);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $name
	 * @param string $value Optional.
	 * @return void
	 */
	private function set_option($name, $value = '') {
		$value = apply_filters("{$this->slug('hook')}-set-option", $value, $name);
		
		if ($value === NULL)
			return;
		
		update_option("{$this->slug()}-{$name}", $value);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return array Author geoLocation default options
	 */
	private function default_options() {
		$options = array(
			'legacy' => true,
			'position' => 'after',
			'type' => 'ROADMAP',
			'zoom' => 15
			);
		return apply_filters("{$this->slug('hook')}-default-options", $options);
	}
	
	/**
	 * @note WordPress < 2.9.0 will always return true
	 * @access public
	 * @since 1.0
	 * @return bool Is latest version of Author geoLocation
	 */
	function latest_version() {
		$latest = true;
		
		if (function_exists('get_site_transient')) {
			$plugins = get_site_transient('update_plugins');
			$latest = (!isset($plugins->response) || !is_array($plugins->response) || !isset($plugins->response[$this->slug('plugin')]));
		}
		
		return apply_filters("{$this->slug('hook')}-latest-version", $latest);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return string Curent workign url
	 */
	private function _plugin_url($path) {
		return apply_filters("{$this->slug('hook')}-plugin-url", plugins_url($path, __FILE__), $path);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _options_init() {
		if (isset($_POST["{$this->slug()}-submit"])) {
			if (check_admin_referer(__FILE__ . "_{$this->slug()}_{$this->version()}")) {
				$this->set_option('legacy', isset($_POST["{$this->slug()}-legacy"]));
				
				if (isset($_POST["{$this->slug()}-position"])) {
					$position = stripslashes(strtolower(trim($_POST["{$this->slug()}-position"])));
					
					if (in_array($position, array('manual', 'before', 'after')))
						$this->set_option('position', $position);
				}
				
				if (isset($_POST["{$this->slug()}-type"])) {
					$type = stripslashes(strtoupper(trim($_POST["{$this->slug()}-type"])));
					
					if (in_array($type, array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN')))
						$this->set_option('type', $type);
				}
				
				if (isset($_POST["{$this->slug()}-zoom"])) {
					$zoom = stripslashes(trim($_POST["{$this->slug()}-zoom"]));
					
					if (is_numeric($zoom) && intval($zoom) >= 0)
						$this->set_option('zoom', intval($zoom));
				}
				
				do_action("{$this->slug('hook')}-set-options");
				$this->add_notice('Options saved successfully.');
			}
		} else if (isset($_POST["{$this->slug()}-reset"])) {
			if (check_admin_referer(__FILE__ . "_{$this->slug()}_{$this->version()}")) {
				foreach ($this->default_options() as $name => $value)
					$this->set_option($name, $value);
				
				do_action("{$this->slug('hook')}-reset-options");
				$this->add_notice('Options reset.');
			}
		}
	}
	
	/**
	 * @access public
	 * @since 1.0
	 * @param int $id
	 * @return object|null Location
	 */
	function location($id = 0) {
		if (empty($id))
			$id = get_the_ID();
		
		$locationmeta = $location = get_post_meta($id, "_{$this->slug()}-location", true);
		
		if (empty($location))
			return null;
		
		if (is_object($location))
			$location = get_object_vars($location);
		else if (is_string($location))
			$location = @unserialize($location);
		
		if (empty($location))
			return null;
		
		$location = (array)$location;
		
		if (isset($location['error']) && $location['error'])
			return false;
		
		if (!isset($location['latitude'], $location['longitude'], $location['latlng']) || empty($location['latitude']) || empty($location['longitude']) || empty($location['latlng']))
			return null;
		
		unset($location['error'], $location['post_id']);
		$location = (object)array_merge(array(
			'address' => '',
			'latlng' => '0,0',
			'latitude' => '0',
			'longitude' => '0',
			'position' => $this->get_option('position'),
			'post_id' => $id,
			'type' => $this->get_option('type'),
			'zoom' => $this->get_option('zoom')
			), $location);
		return apply_filters("{$this->slug('hook')}-location", $location, $id, $locationmeta);
	}
	
	/**
	 * @access public
	 * @since 1.0
	 * @param int $id
	 * @return object|null Last known location
	 */
	function lastLocation($id = 0) {
		global $wpdb;
		
		if (empty($id))
			$id = get_current_user_id();
		
		$last = $wpdb->get_var($wpdb->prepare("SELECT post.ID FROM {$wpdb->postmeta} meta, {$wpdb->posts} post WHERE post.post_author = '%d' AND meta.post_ID = post.ID AND meta.meta_key = '_{$this->slug()}-location' ORDER BY post.post_date_gmt DESC LIMIT 0,1", $id));
		$location = !empty($last) ? $this->location($last) : null;
		return apply_filters("{$this->slug('hook')}-last-location", $location, $id, $last);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $msg
	 * @param string $type Optional.
	 * @param int $priority Optional.
	 * @return void
	 */
	private function add_notice($msg, $type = 'updated', $priority = false) {
		$type = strtolower($type);
		$priority = ($priority === false) ? (($type === 'error') ? 5 : 10) : (int)$priority;
		$msg = apply_filters("{$this->slug('hook')}-add-notice", $msg, $type, $priority);
		
		if (empty($msg))
			return false;
		
		$this->notices[$priority][] = (object)array(
			'msg' => (string)$msg,
			'type' => (string)$type
			);
		return true;
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_notices() {
		$this->notices = apply_filters("{$this->slug('hook')}-print-notices", $this->notices);
		ksort($this->notices);
		
		if (!empty($this->notices)) {
			$this->_comment_tag(true);
			
			foreach ($this->notices as $priority => $notices)
				foreach ($notices as $notice)
					echo "<div class=\"{$notice->type}\"><p>{$notice->msg}</p></div>\n";
			
			$this->_comment_tag(false);
		}
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param object $location
	 * @return string Adress link HTML
	 */
	function _address_html($location) {
		switch ($location->type) {
			case 'HYBRID':
				$map_t = 'h';
				break;
			case 'SATELLITE':
				$map_t = 'k';
				break;
			case 'TERRAIN':
				$map_t = 'p';
				break;
			case 'ROADMAP':
			default:
				$map_t = 'm';
				break;
		}
		
		$address = esc_html($location->address);
		$address = <<<EOD
<a href="http://maps.google.com/maps?q={$location->latlng}&amp;ll={$location->latlng}&amp;z={$location->zoom}&amp;t={$map_t}" rel="nofollow">{$address}</a>
EOD;
		return apply_filters("{$this->slug('hook')}-address-html", $address, $location);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param object $location
	 * @param bool $show_address Optional.
	 * @return string Map HTML
	 */
	private function map_html($location, $show_address = true) {
		static $mapcount = 0;
		$mapcount++;
		$address = sprintf(__('Posted from %1$s', 'author-geolocation'), $this->_address_html($location));
		$address_style = $show_address ? '' : ' style="display:none;"';
		$type = strtolower($location->type);
		$size = '640x250';
		
		if (has_filter('Author_geoLocation_fallback_image_size')) {
			_deprecated_function('Author_geoLocation_fallback_image_size', '1.1', "{$this->slug('hook')}-fallback-image-size");
			$size = apply_filters('Author_geoLocation_fallback_image_size', $size, $location);
		}
		
		$size = apply_filters("{$this->slug('hook')}-fallback-image-size", $size, $location, $show_address);
		$map = <<<EOD
{$this->_comment_tag(true)}
<div class="{$this->slug()}">
<div class="{$this->slug()}-address"{$address_style}>{$address}.</div>
<div class="{$this->slug()}-map" id="{$this->slug()}-map-{$mapcount}">
<img src="//maps.google.com/maps/api/staticmap?sensor=false&amp;center={$location->latlng}&amp;zoom={$location->zoom}&amp;size={$size}&amp;maptype={$type}&amp;markers=color:red|label:%26bull;|{$location->latlng}" alt="" />
</div>
<script type="text/javascript">
(function(){document.getElementById('{$this->slug()}-map-{$mapcount}').style.display='block';var latlng=new google.maps.LatLng('{$location->latitude}','{$location->longitude}'),map = new google.maps.Map(document.getElementById('{$this->slug()}-map-{$mapcount}'),{zoom:{$location->zoom},center:latlng,mapTypeId:google.maps.MapTypeId.{$location->type}});new google.maps.Marker({position:latlng,map:map})})();
</script>
</div>
{$this->_comment_tag(false)}
EOD;
		return apply_filters("{$this->slug('hook')}-map-html", $map, $location, $show_address, $size, $mapcount);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param array $atts
	 * @return string Map HTML
	 */
	function _map_shortcode($atts) {
		$atts = shortcode_atts(array('dont_hide' => false, 'post_id' => 0, 'show_address' => false), $atts);
		
		$location = $this->location($atts['post_id']);
		$show = $atts['dont_hide'] || is_singular();
		
		$map = ($show && $location) ? $this->map_html($location, $atts['show_address']) : '';
		return apply_filters("{$this->slug('hook')}-map-shortcode", $map, $atts, $location, $show);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param array $atts
	 * @return string Address
	 */
	function _address_shortcode($atts) {
		$atts = shortcode_atts(array('before' => __('Posted from ', 'author-geolocation'), 'after' => '.', 'linkify' => true, 'post_id' => 0), $atts);
		
		$location = $this->location($atts['post_id']);
		
		$address = $location ? $atts['before'] . ($atts['linkify'] ? $this->_address_html($location) : htmlentities($location->address)) . $atts['after'] : '';
		return apply_filters("{$this->slug('hook')}-address-shortcode", $address, $atts, $location);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param string $content
	 * @return Post content
	 */
	function _the_content($content) {
		if (is_singular()) {
			$location = $this->location();
			
			if ($location && apply_filters("{$this->slug('hook')}-add-to-content", true, $location, $content)) {
				switch ($location->position) {
					case 'before':
						$content = $this->map_html($location) . $content;
						break;
					case 'after':
						$content .= $this->map_html($location);
						break;
				}
			}
		}
		
		return $content;
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @param int $id
	 * @return int Post id
	 */
	function _save_post($id) {
		if (isset($_POST["{$this->slug()}-nonce"])
			&& wp_verify_nonce($_POST["{$this->slug()}-nonce"], __FILE__ . "_{$this->slug()}_{$this->version()}")
			&& (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE)
			&& current_user_can(sprintf('edit_%s', $_POST['post_type']), $id)) {
			
			$address = stripslashes(trim($_POST["{$this->slug()}-address"]));
			$location = (object)array('error' => true);
			
			if (!empty($address)) {
				$latlng = stripslashes(trim($_POST["{$this->slug()}-latlng"]));
				
				if (empty($latlng) || !$_POST["{$this->slug()}-js"]) {
					$result = wp_remote_get('http://maps.google.com/maps/api/geocode/json?address=' . urlencode($address) . '&sensor=false');
					$json = json_decode($result['body']);
					
					if (!is_a($json, 'WP_Error') && $json->status == 'OK' && count($json->results) > 0) {
						$latlng = "{$json->results[0]->geometry->location->lat},{$json->results[0]->geometry->location->lng}";
						$latlng_ = array(
							$json->results[0]->geometry->location->lat,
							$json->results[0]->geometry->location->lng
							);
					} else {
						$this->add_notice(sprintf(__('Author geoLocation encountered an error while trying to convert %1$s to latlng coordanates.', $this->slug(), htmlentities($address)));
						return $id;
					}
				} else
					$latlng_ = explode(',', $latlng);
				
				if (!empty($latlng) && count($latlng_) == 2 && !empty($latlng_[0]) && !empty($latlng_[1])) {
					$suffix = $_POST["{$this->slug()}-js"] ? '' : '-no-js';
					$position = stripslashes(strtolower(trim($_POST["{$this->slug()}-position"])));
					$type = stripslashes(strtoupper(trim($_POST["{$this->slug()}-type{$suffix}"])));
					$zoom = stripslashes(trim($_POST["{$this->slug()}-zoom{$suffix}"]));
					$location = (object)array(
						'address' => $address,
						'latlng' => $latlng,
						'latitude' => $latlng_[0],
						'longitude' => $latlng_[1],
						'position' => in_array($position, array('manual', 'before', 'after')) ? $position : $this->get_option('position'),
						'type' => in_array($type, array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN')) ? $type : $this->get_option('type'),
						'zoom' => (is_numeric($zoom) && intval($zoom) >= 0) ? intval($zoom) : $this->get_option('zoom')
						);
				}
			}
			
			$location = apply_filters("{$this->slug('hook')}-save-post", $location, $id);
			
			if (!empty($location))
				update_post_meta($id, "_{$this->slug()}-location", /*serialize(*/$location/*)*/);
		}
		
		return $id;
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_meta_box() {
		$id = (isset($_GET['post']) && !empty($_GET['post'])) ? intval(stripslashes($_GET['post'])) : 0;
		$location = !empty($id) ? $this->location($id) : null;
		
		if (is_null($location))
			$location = $this->lastLocation();
		
		$pos = $location ? $location->position : $this->get_option('position');
		$type = $location ? $location->type : $this->get_option('type');
		$zoom = $location ? $location->zoom : $this->get_option('zoom');
		
		if (!apply_filters("{$this->slug('hook')}-do-meta-box", true, $id, $location, $pos, $type, $zoom))
			return;
		
		$this->_comment_tag(true);
?>
	<div id="<?php $this->slug(true, true); ?>">
		<?php wp_nonce_field(__FILE__ . "_{$this->slug()}_{$this->version()}", "{$this->slug()}-nonce"); ?>
		<input type="hidden" id="<?php $this->slug(true, true); ?>-js" name="<?php $this->slug(true, true); ?>-js" value="0" />
		<input type="hidden" id="<?php $this->slug(true, true); ?>-type" name="<?php $this->slug(true, true); ?>-type" value="<?php echo $type; ?>" />
		<input type="hidden" id="<?php $this->slug(true, true); ?>-zoom" name="<?php $this->slug(true, true); ?>-zoom" value="<?php echo $zoom; ?>" />
		<input type="hidden" id="<?php $this->slug(true, true); ?>-latlng" name="<?php $this->slug(true, true); ?>-latlng" value="<?php echo $location ? $location->latlng : ''; ?>" />
		<div>
			<input type="text" id="<?php $this->slug(true, true); ?>-address" name="<?php $this->slug(true, true); ?>-address" value="<?php echo $location ? esc_attr($location->address) : ''; ?>" onchange="<?php $this->slug('js', true, true); ?>_geocode();" autocomplete="off" />
			<input type="button" value="Refresh" class="button hide-if-no-js" onclick="<?php $this->slug('js', true, true); ?>_go();" />
		</div>
		<div>
			<label for="<?php $this->slug(true, true); ?>-position"><?php _e('Position', $this->slug()); ?></label>
			<select id="<?php $this->slug(true, true); ?>-position" name="<?php $this->slug(true, true); ?>-position">
				<option value="manual"<?php selected($pos, 'manual'); ?>><?php _e('Manual', $this->slug()); ?></option>
				<option value="before"<?php selected($pos, 'before'); ?>><?php _e('Before', $this->slug()); ?></option>
				<option value="after"<?php selected($pos, 'after'); ?>><?php _e('After', $this->slug()); ?></option>
			</select>
			<label for="<?php $this->slug(true, true); ?>-type-no-js" class="hide-if-js"><?php _e('Type', $this->slug()); ?></label>
			<select id="<?php $this->slug(true, true); ?>-type-no-js" name="<?php $this->slug(true, true); ?>-no-js" class="hide-if-js">
				<option value="ROADMAP"<?php selected($type, 'ROADMAP'); ?>><?php _e('Map', $this->slug()); ?></option>
				<option value="SATELLITE"<?php selected($type, 'SATELLITE'); ?>><?php _e('Satellite', $this->slug()); ?></option>
				<option value="HYBRID"<?php selected($type, 'HYBRID'); ?>><?php _e('Hybrid', $this->slug()); ?></option>
				<option value="TERRAIN"<?php selected($type, 'TERRAIN'); ?>><?php _e('Terrain', $this->slug()); ?></option>
			</select>
			<label for="<?php $this->slug(true, true); ?>-zoom-no-js" class="hide-if-js"><?php _e('Zoom', $this->slug()); ?></label>
			<input id="<?php $this->slug(true, true); ?>-zoom-no-js" name="<?php $this->slug(true, true); ?>-zoom-no-js" class="hide-if-js" value="<?php echo $zoom; ?>" type="number" min="0" />
		</div>
		<div id="<?php $this->slug(true, true); ?>-map">
			<img src="//maps.google.com/maps/api/staticmap?sensor=false&amp;center=<?php echo $location ? $location->latlng : '0,0'; ?>&amp;zoom=<?php echo $zoom; ?>&amp;size=640x250&amp;maptype=<?php echo strtolower($type); echo $location ? '&amp;markers=color:red|label:%26bull;|' . $location->latlng : ''; ?>" alt="" />
		</div>
		<script type="text/javascript">document.getElementById('<?php $this->slug(true, true); ?>-js').value=1</script>
	</div>
<?php
		$this->_comment_tag(false);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_menu() {
		add_options_page($this->_plugin_data('name'), $this->_plugin_data('name'), 'manage_options', __FILE__, array(&$this, '_options_page'));
		
		foreach (array('page', 'post', 'custom_post_type') as $type) //verify this works for custom post types!
			add_meta_box('location', 'Location', array(&$this, '_admin_meta_box'), $type, 'normal', 'high');
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_head_options() {
		$this->_comment_tag(true);
?>
<style type="text/css">
#<?php $this->slug(true, true); ?> .red{color:red;}
#<?php $this->slug(true, true); ?> .green{color:green;}
#<?php $this->slug(true, true); ?> table{width:100%;}
#<?php $this->slug(true, true); ?> th{width:15%;font-weight:normal;font-size:1.1em;vertical-align:top;}
#<?php $this->slug(true, true); ?> td{font-weight:normal;font-size:0.9em;vertical-align:top;}
#<?php $this->slug(true, true); ?> abbr,#<?php $this->slug(true, true); ?> .dashed{border-bottom:1px dashed;}
</style>
<?php
		$this->_comment_tag(false);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _admin_head_post() {
		$id = (isset($_GET['post']) && !empty($_GET['post'])) ? intval(stripslashes($_GET['post'])) : 0;
		$location = !empty($id) ? $this->location($id) : null;
		
		if (is_null($location))
			$location = $this->lastLocation();
		
		if (!apply_filters("{$this->slug('hook')}-do-post-head", true, $id, $location))
			return;
		
		$this->_comment_tag(true);
?>
<style type="text/css">
#<?php $this->slug(true, true); ?>-address{width:100%;}
body.js #<?php $this->slug(true, true); ?>-address{width:90%;}
#<?php $this->slug(true, true); ?>-map{width:100%;height:250px;overflow:hidden;text-align:center;}
#<?php $this->slug(true, true); ?>>div{margin-top:5px;}
</style>
<script type="text/javascript">
var <?php $this->slug('js', true, true); ?>_geocoder;
var <?php $this->slug('js', true, true); ?>_map;
var <?php $this->slug('js', true, true); ?>_marker;

function <?php $this->slug('js', true, true); ?>_center(location) {
	<?php $this->slug('js', true, true); ?>_map.setCenter(location);
	<?php $this->slug('js', true, true); ?>_marker.setPosition(location);
}

function <?php $this->slug('js', true, true); ?>_geocode() {
	jQuery('#<?php $this->slug(true, true); ?>-latlng').val('');
	<?php $this->slug('js', true, true); ?>_geocoder.geocode({ address: jQuery('#<?php $this->slug(true, true); ?>-address').val() }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK && results[0]) {
			jQuery('#<?php $this->slug(true, true); ?>-latlng').val(results[0].geometry.location.lat() + ',' + results[0].geometry.location.lng());
			<?php $this->slug('js', true, true); ?>_center(results[0].geometry.location);
		} else {
			jQuery('#<?php $this->slug(true, true); ?>-latlng').val('');
			<?php $this->slug('js', true, true); ?>_center(new google.maps.LatLng(0, 0));
		}
	});
}

function <?php $this->slug('js', true, true); ?>_reverse_geocode(location) {
	jQuery('#<?php $this->slug(true, true); ?>-address').val(location.lat() + ',' + location.lng());
	<?php $this->slug(true, true); ?>_geocoder.geocode({ latLng: location }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK && results[0])
			jQuery('#<?php $this->slug(true, true); ?>-address').val(results[0].formatted_address);
		else
			jQuery('#<?php $this->slug(true, true); ?>-address').val(location.lat() + ',' + location.lng());
	});
}

function <?php $this->slug('js', true, true); ?>_callback(position) {
	if (position.coords.latitude && position.coords.longitude) {
		var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
		<?php $this->slug('js', true, true); ?>_center(latlng);
		<?php $this->slug('js', true, true); ?>_reverse_geocode(latlng);
		jQuery('#<?php $this->slug(true, true); ?>-latlng').val(position.coords.latitude + ',' + position.coords.longitude);
	}
}

<?php if ($this->get_option('legacy')) { ?>
function <?php $this->slug('js', true, true); ?>_fallback(e) {
	if (e && e.code == 1) {
		<?php $this->slug('js', true, true); ?>_center(new google.maps.LatLng(0, 0));
		jQuery('#<?php $this->slug(true, true); ?>-address,#<?php $this->slug(true, true); ?>-latlng').val('');
	} else {
		var old = document.getElementById('maxmind-geoip');
		var script = document.createElement('script');
		script.onload = function() {
			<?php $this->slug('js', true, true); ?>_callback({ coords: { latitude: geoip_latitude(), longitude: geoip_longitude() } });
		};
		script.onreadystatechange = function() {
			if (script.readyState == 'complete' || script.readyState == 'loaded')
				<?php $this->slug('js', true, true); ?>_callback({ coords: { latitude: geoip_latitude(), longitude: geoip_longitude() } });
		};
		script.async = true;
		script.id = 'maxmind-geoip';
		script.type = 'text/javascript';
		script.src = 'http://j.maxmind.com/app/geoip.js';
		
		if (old)
			old.parentNode.replaceChild(script, old);
		else {
			var head = document.getElementsByTagName('head')[0] || document.documentElement;
			head.insertBefore(script, head.firstChild);
		}
	}
}

function <?php $this->slug('js', true, true); ?>_go() {
	if (navigator.geolocation)
		navigator.geolocation.getCurrentPosition(<?php $this->slug('js', true, true); ?>_callback, <?php $this->slug('js', true, true); ?>_fallback);
	else if (geo_position_js && geo_position_js.init())
		geo_position_js.getCurrentPosition(<?php $this->slug('js', true, true); ?>_callback, <?php $this->slug('js', true, true); ?>_fallback);
	else
		A<?php $this->slug('js', true, true); ?>_fallback();
}
<?php } else { ?>
function <?php $this->slug('js', true, true); ?>_fallback(e) {
	if (e.code == 1) {
		<?php $this->slug('js', true, true); ?>_center(new google.maps.LatLng(0, 0));
		jQuery('#<?php $this->slug(true, true); ?>-address,#<?php $this->slug(true, true); ?>-latlng').val('');
	}
}

function <?php $this->slug('js', true, true); ?>_go() {
	if (navigator.geolocation)
		navigator.geolocation.getCurrentPosition(<?php $this->slug('js', true, true); ?>_callback, <?php $this->slug('js', true, true); ?>_fallback);
}
<?php } ?>

jQuery(function() {
	var latlng = new google.maps.LatLng('<?php echo $location ? $location->latitude : '0'; ?>', '<?php echo $location ? $location->longitude : '0'; ?>');
	<?php $this->slug('js', true, true); ?>_map = new google.maps.Map(document.getElementById('<?php $this->slug(true, true); ?>-map'), {
		zoom: <?php echo $location ? $location->zoom : $this->get_option('zoom'); ?>,
		center: latlng,
		mapTypeId: google.maps.MapTypeId.<?php echo $location ? $location->type : $this->get_option('type'); ?> 
	});
	<?php $this->slug('js', true, true); ?>_marker = new google.maps.Marker({
		position: latlng,
		map: <?php $this->slug('js', true, true); ?>_map
	});
	<?php $this->slug('js', true, true); ?>_geocoder = new google.maps.Geocoder();
	google.maps.event.addListener(<?php $this->slug('js', true, true); ?>_map, 'zoom_changed', function() {
		jQuery('#<?php $this->slug(true, true); ?>-zoom').val(<?php $this->slug('js', true, true); ?>_map.getZoom());
	});
	google.maps.event.addListener(<?php $this->slug('js', true, true); ?>_map, 'maptypeid_changed', function() {
		jQuery('#<?php $this->slug(true, true); ?>-type').val(<?php $this->slug('js', true, true); ?>_map.getMapTypeId().toUpperCase());
	});
	google.maps.event.addListener(<?php $this->slug('js', true, true); ?>_map, 'click', function(e) {
		<?php $this->slug('js', true, true); ?>_center(e.latLng);
		<?php $this->slug('js', true, true); ?>_reverse_geocode(e.latLng);
		jQuery('#<?php $this->slug(true, true); ?>-latlng').val(e.latLng.lat() + ',' + e.latLng.lng());
	});
<?php if (is_null($location)) { ?>
	<?php $this->slug('js', true, true); ?>_go();
<?php } ?>
});
</script>
<?php
		$this->_comment_tag(false);
	}
	
	/**
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	function _options_page() {
		global $wp_version;
		
		$pos = $this->get_option('position');
		$type = $this->get_option('type');
		
		$this->_comment_tag(true);
?>
	<div id="<?php $this->slug(true, true); ?>" class="wrap">
		<h2><?php $this->_plugin_data('Name', true, true); ?></h2>
		<form method="post" action="">
			<fieldset class="options">
				<table class="editform">
					<tr>
						<th scope="row"><?php _e('Author:', $this->slug()); ?></th>
						<td><a href="<?php $this->_plugin_data('authoruri', true, true); ?>" target="_blank"><?php $this->_plugin_data('author', true, true); ?></a> | <a href="<?php $this->_plugin_data('otherplugins', true, true); ?>" target="_blank"><?php printf(__('Other plugins by %1$s', $this->slug()), $this->_plugin_data('author', true)); ?></a> | <a href="<?php $this->_plugin_data('uri', true, true); ?>" target="_blank"><?php _e('Documentation', $this->slug()); ?></a> | <a href="<?php $this->_plugin_data('donate', true, true); ?>"><?php _e('Donate', $this->slug()); ?></a></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Version:', $this->slug()); ?></th>
<?php if (version_compare($wp_version, '2.9.0', '>=')) { ?>
						<td class="<?php if ($this->latest_version()) { echo 'green'; } else { echo 'red'; } ?>"><span class="dashed" title="<?php if ($this->latest_version()) { _e('Latest version', $this->slug()); } else { _e('Newer version avalible', $this->slug()); } ?>"><?php $this->version(true, true); ?></span></td>
<?php } else { ?>
						<td><span class="dashed"><?php $this->version(true, true); ?></span></td>
<?php } ?>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th scope="row"><?php _e('Legacy:', $this->slug()); ?></th>
						<td><input type="checkbox" name="<?php $this->slug(true, true); ?>-legacy"<?php checked($this->get_option('legacy')); ?> /> <?php _e('Support browsers that do not have the <a href="http://dev.w3.org/geo/api/spec-source.html" target="_blank">Geolocation API</a> with the <a href="http://code.google.com/p/geo-location-javascript/" target="_blank">geo-location-javascript library</a> and <a href="" target="_blank">Google Gears</a>.', $this->slug()); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Position:', $this->slug()); ?></th>
						<td>
							<select name="<?php $this->slug(true, true); ?>-position">
								<option value="manual"<?php selected($pos, 'manual'); ?>><?php _e('Manual', $this->slug()); ?></option>
								<option value="before"<?php selected($pos, 'before'); ?>><?php _e('Before', $this->slug()); ?></option>
								<option value="after"<?php selected($pos, 'after'); ?>><?php _e('After', $this->slug()); ?></option>
							</select>
							<?php _e('The default position to display the location in the post.', $this->slug()); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Type:', $this->slug()); ?></th>
						<td>
							<select name="<?php $this->slug(true, true); ?>-type">
								<option value="ROADMAP"<?php selected($type, 'ROADMAP'); ?>><?php _e('Map', $this->slug()); ?></option>
								<option value="SATELLITE"<?php selected($type, 'SATELLITE'); ?>><?php _e('Satellite', $this->slug()); ?></option>
								<option value="HYBRID"<?php selected($type, 'HYBRID'); ?>><?php _e('Hybrid', $this->slug()); ?></option>
								<option value="TERRAIN"<?php selected($type, 'TERRAIN'); ?>><?php _e('Terrain', $this->slug()); ?></option>
							</select>
							<?php _e('The default type of map to display.', $this->slug()); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Zoom:', $this->slug()); ?></th>
						<td>
							<input name="<?php $this->slug(true, true); ?>-zoom" value="<?php echo $this->get_option('zoom'); ?>" type="number" min="0" />
							<?php _e('The default zoom of the maps.', $this->slug()); ?>
						</td>
					</tr>
<?php do_action("{$this->slug('hook')}-options-page"); ?>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th><input type="submit" class="button-primary" name="<?php $this->slug(true, true); ?>-submit" value="Save" /></th>
						<td><input type="submit" class="button-primary" name="<?php $this->slug(true, true); ?>-reset" value="Reset" onclick="return confirm('<?php _e('WARNING: This will reset ALL options, are you sure want to continue?', $this->slug()); ?>');" /></td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th></th>
						<td><?php printf(__('Please support us by <a href="http://twitter.com/?status=I+just+installed+%2$s+WordPress+plugin+%3$s+%%23wordpress" target="_blank">tweeting about this plugin</a>, <a href="%1$s" target="_blank">writing a post about this plugin</a> or <a href="%4$s">donating</a>.', $this->slug()), admin_url('post-new.php'), urlencode($this->_plugin_data('name')), urlencode($this->_plugin_data('shortlink')), $this->_plugin_data('donate', true)); ?></td>
					</tr>
					<tr>
						<th></th>
						<td style="font-size:80%;"><?php _e('This product uses the <a href="http://www.maxmind.com/app/javascript_city" target="_blank">GeoIP JavaScript Service</a> by <a href="http://www.maxmind.com/" target="_blank">MaxMind</a>.', $this->slug()); ?></td>
					</tr>
				</table>
			</fieldset>
			<?php wp_nonce_field(__FILE__ . "_{$this->slug()}_{$this->version()}"); ?>
		</form>
	</div>
<?php
		$this->_comment_tag(false);
	}
}

/**
 * @access public
 * @since 1.0
 */
$Author_geoLocation = new Author_geoLocation();

/**
 * @access public
 * @since 1.0
 * @param int $id Optional.
 * @return object Location
 */
if (!function_exists('get_the_location')) {
	function get_the_location($id = 0) {
		global $Author_geoLocation;
		$location = $Author_geoLocation->location($id);
		return apply_filters('get_the_location', $location, $id);
	}
}

/**
 * @access public
 * @since 1.0
 * @param int $id Optional.
 * @param string $before Optional.
 * @param string $after Optional.
 * @param bool $linkify Optional.
 * @return void
 */
if (!function_exists('the_location')) {
	function the_location($id = 0, $before = 'Posted from ', $after = '.', $linkify = true) {
		global $Author_geoLocation;
		$address = $Author_geoLocation->_address_shortcode(array('before' => $before, 'after' => $after, 'linkify' => $linkify, 'post_id' => $id));
		echo apply_filters('the_location', $address, $id, $before, $after, $linkify);
	}
}
?>