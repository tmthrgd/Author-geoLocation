<?php
/*
Author: Tom Thorogood
Author URI: http://thetomthorogood.wordpress.com/
Description: Allows authors to geocode their posts using the HTML5 <a href="http://dev.w3.org/geo/api/spec-source.html" target="_blank">Geolocation API</a> and display the location using <a href="http://maps.google.com/" target="_blank">Google Maps</a>. It also supports the <a href="http://code.google.com/p/geo-location-javascript/" target="_blank">geo-location-javascript library</a> and the <a href="http://www.maxmind.com/app/javascript_city" target="_blank">MaxMind GeoIP Javascript Service</a> for backwards compatibility.
Plugin Name: Author geoLocation
Plugin URI: http://thetomthorogood.wordpress.com/plugins/author-geolocation/
Version: 1.0
*/

/*
* If you notice any issues or bugs in the plugin please contact me [@link http://thetomthorogood.wordpress.com/contact]
* If you make any revisions to and/or re-release this plugin please contact me [@link http://thetomthorogood.wordpress.com/contact]
*/

/*
* Copyright © 2010 Tom Thorogood
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

/*
@access public
@since 1.0
*/
class Author_geoLocation {
	/*
	@access private
	@since 1.0
	@type array
	*/
	private $notices = array();
	
	/*
	@access private
	@since 1.0
	@type int
	*/
	private $mapcount = 0;
	
	/*
	@access public
	@since 1.0
	@return string plugin_version
	*/
	function version() {
		return '1.0';
	}
	
	/*
	@access public
	@since 1.0
	@return undefined
	*/
	function Author_geoLocation() {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}
	
	/*
	@access public
	@since 1.0
	@return undefined
	*/
	function __construct() {
		$this->add_option('version', $this->version());
		
		foreach ($this->default_options() as $name => $value)
			$this->add_option($name, $value);
		
		add_action('admin_head-' . $this->slug(true), array(&$this, '_admin_head_options'));
		add_action('admin_head-post-new.php', array(&$this, '_admin_head_post'));
		add_action('load-' . $this->slug(true), array(&$this, '_options_init'));
		add_action('admin_head-post.php', array(&$this, '_admin_head_post'));
		add_action('load-post-new.php', array(&$this, '_admin_post_init'));
		add_action('load-post.php', array(&$this, '_admin_post_init'));
		add_action('admin_notices', array(&$this, '_admin_notices'));
		add_action('the_content', array(&$this, '_the_content'));
		add_action('admin_menu', array(&$this, '_admin_menu'));
		add_action('save_post', array(&$this, '_save_post'));
		add_action('wp_head', array(&$this, '_head'));
		add_action('init', array(&$this, '_init'));
		add_shortcode('address', array(&$this, '_address_shortcode'));
		add_shortcode('map', array(&$this, '_map_shortcode'));
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _init() {
		wp_enqueue_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=false', array(), 3);
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_post_init() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=false', array(), 3);
		
		if ($this->get_option('legacy')) {
			wp_enqueue_script('google-gears', $this->cwu() . '/js/gears.init.js', array(), null);
			wp_enqueue_script('geo-location', $this->cwu() . '/js/geo.js', array(), '0.4.7');
		}
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _head() {
?>
<!--Author geoLocation - <?php echo $this->version(); ?>: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
<style type="text/css">
.Author-geoLocation{border:1px solid #000;margin:5px 0;padding:5px;text-align:center;}
.Author-geoLocation-address{margin:0 0 5px;padding:0;}
.Author-geoLocation-map{width:100%;height:250px;overflow:hidden;text-align:center;}
</style>
<!--/Author geoLocation-->
<?php
	}
	
	/*
	@access private
	@since 1.0
	@param string $name option_name
	@param string $value option_value
	@return undefined
	*/
	private function add_option($name, $value = '') {
		add_option('Author-geoLocation-' . $name, $value);
	}
	
	/*
	@access private
	@since 1.0
	@param string $name option_name
	@return string option_value
	*/
	private function get_option($name) {
		return get_option('Author-geoLocation-' . $name);
	}
	
	/*
	@access private
	@since 1.0
	@param string $name option_name
	@param string $value option_value
	@return undefined
	*/
	private function set_option($name, $value = '') {
		update_option('Author-geoLocation-' . $name, $value);
	}
	
	/*
	@access private
	@since 1.0
	@return array default_options
	*/
	private function default_options() {
		return array(
			'legacy' => true,
			'position' => 'after',
			'type' => 'ROADMAP',
			'zoom' => 15
			);
	}
	
	/*
	@access private
	@since 1.0
	@return string wordpress plugin slug
	*/
	private function slug($settings = false) {
		return $settings ? 'settings_page_' . basename(dirname(__FILE__)) . '/' . substr(basename(__FILE__), 0, -4) : basename(dirname(__FILE__)) . '/' . basename(__FILE__);
	}
	
	/*
	@note WordPress < 2.9.0 will always return true
	@access public
	@since 1.0
	@return boolean is_latest_version
	*/
	function latest_version() {
		if (function_exists('get_site_transient')) {
			$plugins = get_site_transient('update_plugins');
			return (!isset($plugins->response) || !is_array($plugins->response) || !isset($plugins->response[$this->slug()]));
		}
		
		return true;
	}
	
	/*
	@access private
	@since 1.0
	@return string current_working_url
	*/
	private function cwu() {
		return WP_PLUGIN_URL . '/' . basename(dirname(__FILE__));
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _options_init() {
		if (isset($_POST['Author-geoLocation-submit'])) {
			if (wp_verify_nonce($_REQUEST['_wpnonce'], __FILE__ . $this->version()) && stripos(wp_get_referer(), admin_url() . 'options-general.php?page=' . $this->slug()) !== false) {
				$this->set_option('legacy', isset($_POST['Author-geoLocation-legacy']));
				
				if (isset($_POST['Author-geoLocation-position'])) {
					$position = stripslashes(strtolower(trim($_POST['Author-geoLocation-position'])));
					
					if (in_array($position, array('manual', 'before', 'after')))
						$this->set_option('position', $position);
				}
				
				if (isset($_POST['Author-geoLocation-type'])) {
					$type = stripslashes(strtoupper(trim($_POST['Author-geoLocation-type'])));
					
					if (in_array($type, array('ROADMAP', 'SATELLITE', 'HYBRID', 'TERRAIN')))
						$this->set_option('type', $type);
				}
				
				if (isset($_POST['Author-geoLocation-zoom'])) {
					$zoom = stripslashes(trim($_POST['Author-geoLocation-zoom']));
					
					if (is_numeric($zoom) && intval($zoom) >= 0)
						$this->set_option('zoom', intval($zoom));
				}
				
				$this->add_notice('Options saved successfully.');
			} else
				$this->add_notice('Error saving options.', 'error');
		} else if (isset($_POST['Author-geoLocation-reset'])) {
			if (wp_verify_nonce($_REQUEST['_wpnonce'], __FILE__ . $this->version()) && stripos(wp_get_referer(), admin_url() . 'options-general.php?page=' . $this->slug()) !== false) {
				foreach($this->default_options() as $name => $value)
					$this->set_option($name, $value);
				
				$this->add_notice('Options reset.');
			} else
				$this->add_notice('Error resetting options.', 'error');
		}
	}
	
	/*
	@access public
	@since 1.0
	@param int post id
	@return object location/null
	*/
	function location($post_id = 0) {
		if (empty($post_id))
			$post_id = get_the_ID();
		
		$location = get_post_meta($post_id, '_Author-geoLocation-location', true);
				
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
		return (object)array_merge(array(
			'address' => '',
			'latlng' => '0,0',
			'latitude' => '0',
			'longitude' => '0',
			'position' => $this->get_option('position'),
			'post_id' => $post_id,
			'type' => $this->get_option('type'),
			'zoom' => $this->get_option('zoom')
			), $location);
	}
	
	/*
	@access public
	@since 1.0
	@return object last known location/null
	*/
	function lastLocation($user_id = 0) {
		global $wpdb;
		
		if (empty($user_id)) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
		}
		
		$last = $wpdb->get_var($wpdb->prepare("SELECT post.ID FROM $wpdb->postmeta meta, $wpdb->posts post WHERE post.post_author = '%d' AND meta.post_ID = post.ID AND meta.meta_key = '_location' ORDER BY post.post_date_gmt DESC LIMIT 0,1", $user_id));
		return !empty($last) ? $this->location($last) : null;
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	private function add_notice($msg, $type = 'updated', $priority = false) {
		$type = strtolower($type);
		$priority = ($priority === false) ? (($type == 'error') ? 5 : 10) : (int)$priority;
		
		if (!isset($this->notices[$priority]))
			$this->notices[$priority] = array();
		
		$this->notices[$priority][] = (object)array(
			'msg' => $msg,
			'type' => $type
			);
	}
		
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_notices() {
		ksort($this->notices);
		
		foreach($this->notices as $priority => $notices) {
			foreach($notices as $notice)
				echo '<div class="' . $notice->type . '"><p>' . $notice->msg . "</p></div>\n";
		}
	}
	
	/*
	@access private
	@since 1.0
	@return string
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
		
		$address = htmlentities($location->address);
		return <<<EOD
<a href="http://maps.google.com/maps?q={$location->latlng}&amp;ll={$location->latlng}&amp;z={$location->zoom}&amp;t={$map_t}" rel="nofollow">{$address}</a>
EOD;
	}
	
	/*
	@access private
	@since 1.0
	@return string
	*/
	private function map_html($location, $show_address = true) {
		$this->mapcount++;
		$address = $this->_address_html($location);
		$address_style = $show_address ? '' : ' style="display:none;"';
		$type = strtolower($location->type);
		$size = apply_filters('Author_geoLocation_fallback_image_size', '640x250', $location);
		return <<<EOD
<!--Author geoLocation - {$this->version()}: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
<div class="Author-geoLocation">
<div class="Author-geoLocation-address"{$address_style}>Posted from {$address}.</div>
<div class="Author-geoLocation-map" id="Author-geoLocation-map-{$this->mapcount}">
<img src="http://maps.google.com/maps/api/staticmap?sensor=false&amp;center={$location->latlng}&amp;zoom={$location->zoom}&amp;size={$size}&amp;maptype={$type}&amp;markers=color:red|label:%26bull;|{$location->latlng}" alt="" />
</div>
<script type="text/javascript">
(function(){document.getElementById('Author-geoLocation-map-{$this->mapcount}').style.display='block';var latlng=new google.maps.LatLng('{$location->latitude}','{$location->longitude}'),map = new google.maps.Map(document.getElementById('Author-geoLocation-map-{$this->mapcount}'),{zoom:{$location->zoom},center:latlng,mapTypeId:google.maps.MapTypeId.{$location->type}});new google.maps.Marker({position:latlng,map:map});})();
</script>
</div>
<!--/Author geoLocation-->

EOD;
	}
	
	/*
	@access private
	@since 1.0
	@return string
	*/
	function _map_shortcode($atts) {
		$atts = shortcode_atts(array('dont_hide' => false, 'post_id' => 0, 'show_address' => false), $atts);
		
		if ($atts['dont_hide'] || is_singular()) {
			$location = $this->location($atts['post_id']);
			
			if ($location)
				return $this->map_html($location, $atts['show_address']);
		}
		
		return '';
	}
	
	/*
	@access private
	@since 1.0
	@return string
	*/
	function _address_shortcode($atts) {
		global $Author_geoLocation;
		$atts = shortcode_atts(array('before' => 'Posted from ', 'after' => '.', 'linkify' => true, 'post_id' => 0), $atts);
		$location = $this->location($atts['post_id']);
		return $location ? $atts['before'] . ($atts['linkify'] ? $this->_address_html($location) : htmlentities($location->address)) . $atts['after'] : '';
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _the_content($content) {
		if (is_singular()) {
			$location = $this->location();
			
			if ($location) {
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
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _save_post($post_id) {
		if (wp_verify_nonce($_POST['Author-geoLocation-nonce'], __FILE__ . $this->version()) && (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && current_user_can(sprintf('edit_%s', $_POST['post_type']), $post_id)) {
			$address = stripslashes(trim($_POST['Author-geoLocation-address']));
			$location = array('error' => true);
						
			if (!empty($address)) {
				$latlng = stripslashes(trim($_POST['Author-geoLocation-latlng']));
				
				if (empty($latlng) || !$_POST['Author-geoLocation-js']) {
					$result = wp_remote_get('http://maps.google.com/maps/api/geocode/json?address=' . urlencode($address) . '&sensor=false');
					$json = json_decode($result['body']);
					
					if (!is_a($json, 'WP_Error') && $json->status == 'OK' && count($json->results) > 0) {
						$latlng = $json->results[0]->geometry->location->lat . ',' . $json->results[0]->geometry->location->lng;
						$latlng_ = array(
							$json->results[0]->geometry->location->lat,
							$json->results[0]->geometry->location->lng
							);
					} else {
						$this->add_notice('Author geoLocation encountered an error while trying to convert ' . htmlentities($address) . ' to latlng coordanates.');
						return $post_id;
					}
				} else
					$latlng_ = explode(',', $latlng);
							
				if (!empty($latlng) && count($latlng_) == 2 && !empty($latlng_[0]) && !empty($latlng_[1])) {
					$position = stripslashes(strtolower(trim($_POST['Author-geoLocation-position'])));
					$type = stripslashes(strtoupper(trim($_POST['Author-geoLocation-js'] ? $_POST['Author-geoLocation-type'] : $_POST['Author-geoLocation-type-no-js'])));
					$zoom = stripslashes(trim($_POST['Author-geoLocation-js'] ? $_POST['Author-geoLocation-zoom'] : $_POST['Author-geoLocation-zoom-no-js']));
					$location = array(
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
			
			update_post_meta($post_id, '_Author-geoLocation-location', serialize($location));
		}
		
		return $post_id;
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_meta_box() {
		$location = (isset($_GET['post']) && !empty($_GET['post'])) ? $this->location(intval(stripslashes($_GET['post']))) : null;
		
		if (is_null($location))
			$location = $this->lastLocation();
		
		$pos = $location ? $location->position : $this->get_option('position');
		$type = $location ? $location->type : $this->get_option('type');
		$zoom = $lcoation ? $location->zoom : $this->get_option('zoom');
?>
<!--Author geoLocation - <?php echo $this->version(); ?>: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
<div id="Author-geoLocation">
<input type="hidden" name="Author-geoLocation-nonce" value="<?php echo wp_create_nonce(__FILE__ . $this->version()); ?>" />
<input type="hidden" id="Author-geoLocation-js" name="Author-geoLocation-js" value="0" />
<input type="hidden" id="Author-geoLocation-type" name="Author-geoLocation-type" value="<?php echo $type; ?>" />
<input type="hidden" id="Author-geoLocation-zoom" name="Author-geoLocation-zoom" value="<?php echo $zoom; ?>" />
<input type="hidden" id="Author-geoLocation-latlng" name="Author-geoLocation-latlng" value="<?php echo $location ? $location->latlng : ''; ?>" />
<div>
<input type="text" id="Author-geoLocation-address" name="Author-geoLocation-address" value="<?php echo $location ? $location->address : ''; ?>" onchange="Author_geoLocation_geocode();" autocomplete="off" />
<input type="button" value="Refresh" class="button hide-if-no-js" onclick="Author_geoLocation_go();" />
</div>
<div>
<label for="Author-geoLocation-position">Position</label>
<select id="Author-geoLocation-position" name="Author-geoLocation-position">
<option value="manual"<?php if ($pos == 'manual') { echo ' selected="selected"'; } ?>>Manual</option>
<option value="before"<?php if ($pos == 'before') { echo ' selected="selected"'; } ?>>Before</option>
<option value="after"<?php if ($pos == 'after') { echo ' selected="selected"'; } ?>>After</option>
</select>
<label for="Author-geoLocation-type-no-js" class="hide-if-js">Type</label>
<select id="Author-geoLocation-type-no-js" name="Author-geoLocation-type-no-js" class="hide-if-js">
<option value="ROADMAP"<?php if ($type == 'ROADMAP') { echo ' selected="selected"'; } ?>>Map</option>
<option value="SATELLITE"<?php if ($type == 'SATELLITE') { echo ' selected="selected"'; } ?>>Satellite</option>
<option value="HYBRID"<?php if ($type == 'HYBRID') { echo ' selected="selected"'; } ?>>Hybrid</option>
<option value="TERRAIN"<?php if ($type == 'TERRAIN') { echo ' selected="selected"'; } ?>>Terrain</option>
</select>
<label for="Author-geoLocation-zoom-no-js" class="hide-if-js">Zoom</label>
<input id="Author-geoLocation-zoom-no-js" name="Author-geoLocation-zoom-no-js" class="hide-if-js" value="<?php echo $zoom; ?>" type="number" min="0" />
</div>
<div id="Author-geoLocation-map">
<img src="http://maps.google.com/maps/api/staticmap?sensor=false&amp;center=<?php echo $location ? $location->latlng : '0,0'; ?>&amp;zoom=<?php echo $zoom; ?>&amp;size=640x250&amp;maptype=<?php echo strtolower($type); ?>&amp;markers=color:red|label:%26bull;|<?php echo $location ? $location->latlng : ''; ?>" alt="" />
</div>
<script type="text/javascript">(function(){document.getElementById('Author-geoLocation-js').value=1;})();</script>
</div>
<!--/Author geoLocation-->
<?php
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_menu() {
		add_options_page('Author geoLocation', 'Author geoLocation', 'manage_options', __FILE__, array(&$this, '_options_page'));
		add_meta_box('location', 'Location', array(&$this, '_admin_meta_box'), 'page', 'normal', 'high');
		add_meta_box('location', 'Location', array(&$this, '_admin_meta_box'), 'post', 'normal', 'high');
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_head_options() {
?>
<!--Author geoLocation - <?php echo $this->version(); ?>: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
<style type="text/css">
#Author-geoLocation .red{color:red;}
#Author-geoLocation .green{color:green;}
#Author-geoLocation table{width:100%;}
#Author-geoLocation th{width:15%;font-weight:normal;font-size:1.1em;vertical-align:top;}
#Author-geoLocation td{font-weight:normal;font-size:0.9em;vertical-align:top;}
#Author-geoLocation abbr,#Author-geoLocation .dashed{border-bottom:1px dashed #999;}
</style>
<!--/Author geoLocation-->
<?php
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _admin_head_post() {
		$location = (isset($_GET['post'])) ? $this->location($_GET['post']) : null;
		
		if (is_null($location))
			$location = $this->lastLocation();
?>
<!--Author geoLocation - <?php echo $this->version(); ?>: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
<style type="text/css">
#Author-geoLocation-address{width:100%;}
body.js #Author-geoLocation-address{width:90%;}
#Author-geoLocation-map{width:100%;height:250px;overflow:hidden;text-align:center;}
#Author-geoLocation>div{margin-top:5px;}
</style>
<script type="text/javascript">
var Author_geoLocation_geocoder,
	Author_geoLocation_map,
	Author_geoLocation_marker;

function Author_geoLocation_center(location) {
	Author_geoLocation_map.setCenter(location);
	Author_geoLocation_marker.setPosition(location);
}

function Author_geoLocation_geocode() {
	Author_geoLocation_geocoder.geocode({ address: jQuery('#Author-geoLocation-address').val() }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK && results[0]) {
			jQuery('#Author-geoLocation-latlng').val(results[0].geometry.location.lat() + ',' + results[0].geometry.location.lng());
			Author_geoLocation_center(results[0].geometry.location);
		} else {
			jQuery('#Author-geoLocation-latlng').val('');
			Author_geoLocation_center(new google.maps.LatLng(0, 0));
		}
	});
}

function Author_geoLocation_reverse_geocode(location) {
	jQuery('#Author-geoLocation-address').val(location.lat() + ',' + location.lng());
	Author_geoLocation_geocoder.geocode({ latLng: location }, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK && results[0])
			jQuery('#Author-geoLocation-address').val(results[0].formatted_address);
		else
			jQuery('#Author-geoLocation-address').val(location.lat() + ',' + location.lng());
	});
}

function Author_geoLocation_callback(position) {
	if (position.coords.latitude && position.coords.longitude) {
		var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
		Author_geoLocation_center(latlng);
		Author_geoLocation_reverse_geocode(latlng);
		jQuery('#Author-geoLocation-latlng').val(position.coords.latitude + ',' + position.coords.longitude);
	}
}

<?php if ($this->get_option('legacy')) { ?>
function Author_geoLocation_fallback(e) {
	if (e && e.code == 1) {
		Author_geoLocation_center(new google.maps.LatLng(0, 0));
		jQuery('#Author-geoLocation-address').val('');
	} else {
		var old = document.getElementById('maxmind-geoip'),
			script = document.createElement('script');
		script.onload = function() {
			Author_geoLocation_callback({ coords: { latitude: geoip_latitude(), longitude: geoip_longitude() } });
		};
		script.onreadystatechange = function() {
			if (script.readyState == 'complete' || script.readyState == 'loaded')
				Author_geoLocation_callback({ coords: { latitude: geoip_latitude(), longitude: geoip_longitude() } });
		};
		script.async = true;
		script.id = 'maxmind-geoip';
		script.type = 'text/javascript';
		script.src = 'http://j.maxmind.com/app/geoip.js';
		
		if (old)
			old.parentNode.replaceChild(script, old);
		else {
			var first = document.getElementsByTagName('script')[0];
			first.parentNode.insertBefore(script, first);
		}
	}
}

function Author_geoLocation_go() {
	if (navigator.geolocation)
		navigator.geolocation.getCurrentPosition(Author_geoLocation_callback, Author_geoLocation_fallback);
	else if (geo_position_js && geo_position_js.init())
		geo_position_js.getCurrentPosition(Author_geoLocation_callback, Author_geoLocation_fallback);
	else
		Author_geoLocation_fallback();
}
<?php } else { ?>
function Author_geoLocation_go() {
	if (navigator.geolocation)
		navigator.geolocation.getCurrentPosition(Author_geoLocation_callback);
}
<?php } ?>

jQuery(function() {
	var latlng = new google.maps.LatLng('<?php echo $location ? $location->latitude : '0'; ?>', '<?php echo $location ? $location->longitude : '0'; ?>');
	Author_geoLocation_map = new google.maps.Map(document.getElementById('Author-geoLocation-map'), {
		zoom: <?php echo $location ? $location->zoom : $this->get_option('zoom'); ?>,
		center: latlng,
		mapTypeId: google.maps.MapTypeId.<?php echo $location ? $location->type : $this->get_option('type'); ?> 
	});
	Author_geoLocation_marker = new google.maps.Marker({
		position: latlng,
		map: Author_geoLocation_map
	});
	Author_geoLocation_geocoder = new google.maps.Geocoder();
	google.maps.event.addListener(Author_geoLocation_map, 'zoom_changed', function() {
		jQuery('#Author-geoLocation-zoom').val(Author_geoLocation_map.getZoom());
	});
	google.maps.event.addListener(Author_geoLocation_map, 'maptypeid_changed', function() {
		jQuery('#Author-geoLocation-type').val(Author_geoLocation_map.getMapTypeId().toUpperCase());
	});
	google.maps.event.addListener(Author_geoLocation_map, 'click', function(e) {
		Author_geoLocation_center(e.latLng);
		Author_geoLocation_reverse_geocode(e.latLng);
		jQuery('#Author-geoLocation-latlng').val(e.latLng.lat() + ',' + e.latLng.lng());
	});
<?php if (is_null($location)) { ?>
	Author_geoLocation_go();
<?php } ?>
});
</script>
<!--/Author geoLocation-->
<?php
	}
	
	/*
	@access private
	@since 1.0
	@return undefined
	*/
	function _options_page() {
		global $wp_version;
		$pos = $this->get_option('position');
		$type = $this->get_option('type');
?>
<!--Author geoLocation - <?php echo $this->version(); ?>: http://thetomthorogood.wordpress.com/plugins/author-geolocation/-->
	<div id="Author-geoLocation" class="wrap">
		<h2>Author geoLocation</h2>
		<form method="post" action="">
			<fieldset class="options">
				<table class="editform">
					<tr>
						<th scope="row">Author:</th>
						<td><a href="http://thetomthorogood.wordpress.com" target="_blank">Tom Thorogood</a> | <a href="http://thetomthorogood.wordpress.com/plugins/" target="_blank">Other plugins by Tom Thorogood</a></td>
					</tr>
					<tr>
						<th scope="row">Version:</th>
<?php if (version_compare($wp_version, '2.9.0', '>=')) { ?>
						<td class="<?php if ($this->latest_version()) { echo 'green'; } else { echo 'red'; } ?>"><span class="dashed" title="<?php if ($this->latest_version()) { echo 'Latest version'; } else { echo 'Newer version avalible'; } ?>"><?php echo htmlentities($this->version()); ?></span></td>
<?php } else { ?>
						<td><span class="dashed"><?php echo htmlentities($this->version()); ?></span></td>
<?php } ?>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th scope="row">Legacy:</th>
						<td><input type="checkbox" name="Author-geoLocation-legacy"<?php if ($this->get_option('legacy')) { echo ' checked="checked"'; } ?> /> Support browsers that do not have the <a href="http://dev.w3.org/geo/api/spec-source.html" target="_blank">Geolocation API</a> with the <a href="http://code.google.com/p/geo-location-javascript/" target="_blank">geo-location-javascript library</a> and <a href="" target="_blank">Google Gears</a>.</td>
					</tr>
					<tr>
						<th scope="row">Position:</th>
						<td>
							<select name="Author-geoLocation-position">
								<option value="manual"<?php if ($pos == 'manual') { echo ' selected="selected"'; } ?>>Manual</option>
								<option value="before"<?php if ($pos == 'before') { echo ' selected="selected"'; } ?>>Before</option>
								<option value="after"<?php if ($pos == 'after') { echo ' selected="selected"'; } ?>>After</option>
							</select>
							The default position to display the location in the post.
						</td>
					</tr>
					<tr>
						<th scope="row">Type:</th>
						<td>
							<select name="Author-geoLocation-type">
								<option value="ROADMAP"<?php if ($type == 'ROADMAP') { echo ' selected="selected"'; } ?>>Map</option>
								<option value="SATELLITE"<?php if ($type == 'SATELLITE') { echo ' selected="selected"'; } ?>>Satellite</option>
								<option value="HYBRID"<?php if ($type == 'HYBRID') { echo ' selected="selected"'; } ?>>Hybrid</option>
								<option value="TERRAIN"<?php if ($type == 'TERRAIN') { echo ' selected="selected"'; } ?>>Terrain</option>
							</select>
							The default type of map to display.
						</td>
					</tr>
					<tr>
						<th scope="row">Zoom:</th>
						<td>
							<input name="Author-geoLocation-zoom" value="<?php echo $this->get_option('zoom'); ?>" type="number" min="0" />
							The default zoom of the maps.
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th><input type="submit" class="button-primary" name="Author-geoLocation-submit" value="Save" /></th>
						<td><input type="submit" class="button-primary" name="Author-geoLocation-reset" value="Reset" onclick="return confirm('WARNING: This will reset ALL options, are you sure want to continue?');" /></td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
						<th></th>
						<td>Please support us by <a href="http://twitter.com/?status=I+just+installed+Author+geoLocation+WordPress+plugin+http://bit.ly/c8wi49+%23wordpress" target="_blank">tweeting about this plugin</a> or <a href="<?php echo admin_url(); ?>post-new.php" target="_blank">writing a post about this plugin</a>.</td>
					</tr>
					<tr>
						<th></th>
						<td style="font-size:80%;">This product uses the <a href="http://www.maxmind.com/app/javascript_city" target="_blank">GeoIP JavaScript Service</a> by <a href="http://www.maxmind.com/" target="_blank">MaxMind</a>.</td>
					</tr>
				</table>
			</fieldset>
			<input name="_wpnonce" type="hidden" value="<?php echo wp_create_nonce(__FILE__ . $this->version()); ?>" />
		</form>
	</div>
<!--/Author geoLocation-->
<?php
	}
}

/*
@access public
@since 1.0
*/
$Author_geoLocation = new Author_geoLocation();

/*
@access public
@since 1.0
@param int post_id
@return object location
*/
if (!function_exists('get_the_location')) {
	function get_the_location($post_id = 0) {
		global $Author_geoLocation;
		return apply_filters('get_the_location', $Author_geoLocation->location($post_id), $post_id);
	}
}

/*
@access public
@since 1.0
@param int post_id
@return undefined
*/
if (!function_exists('the_location')) {
	function the_location($post_id = 0, $before = 'Posted from ', $after = '.', $linkify = true) {
		global $Author_geoLocation;
		echo apply_filters('the_location', $Author_geoLocation->_address_shortcode(array('before' => $before, 'after' => $after, 'linkify' => $linkify, 'post_id' => $post_id)), $post_id);
	}
}
?>