<?php
/*
Plugin Name: Simple Image Gallery
Plugin URI: https://om4.com.au/plugins/
Description: Creates powerful and attractive image galleries that don't require Adobe Flash.
Version: 1.8.3
Author: OM4
Author URI: https://om4.com.au/plugins/
Text Domain: om4-simplegallery
Git URI: https://github.com/OM4/simple-gallery
Git Branch: release
License: GPLv2
*/

/*  Copyright 2009-2016 OM4 (email: plugins@om4.com.au    web: https://om4.com.au/plugins/)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class OM4_Simple_Gallery {
	
	var $version = '1.8.3';
	
	var $dbVersion = 1;
	
	var $installedVersion;
	
	var $dirname;
	
	var $url;
	
	static $number = 1;

	var $script_suffix = '';

	var $style_suffix = '';
	
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		
		// Uncomment to prevent browsers caching the JS file while debugging.
		//$this->version .= time();
		
		// Store the name of the directory that this plugin is installed in
		$this->dirname = str_replace('/simple-gallery.php', '', plugin_basename(__FILE__));
		
		$this->url = plugins_url($this->dirname . '/');

		register_activation_hook(__FILE__, array($this, 'Activate'));
		
		add_action('init', array($this, 'LoadDomain'));
		
		add_action('init', array($this, 'CheckVersion'));
		
		add_action('init', array($this, 'RegisterShortcode'));

		add_action('wp_enqueue_scripts', array($this, 'RegisterScripts'));

		add_filter('option_srel_options', array($this, 'ForceShutterReloadedInHead'));
		
		$this->installedVersion = intval(get_option('om4_simple_gallery_db_version'));
	}
	
	/**
	 * Intialise I18n
	 *
	 */
	function LoadDomain() {
		load_plugin_textdomain( 'om4-simplegallery', false, dirname( plugin_basename( __FILE__) ) );
	}
	
	/**
	 * Plugin Activation Tasks
	 *
	 */
	function Activate() {
		// There aren't really any installation tasks at the moment
		
		if (!$this->installedVersion) {
			$this->installedVersion = $this->dbVersion;
			$this->SaveInstalledVersion();
		}
	}
	
	/**
	 * Performs any upgrade tasks if required
	 *
	 */
	function CheckVersion() {
		if ($this->installedVersion != $this->dbVersion) {
			// Upgrade tasks
			if ($this->installedVersion == 0) {
				$this->installedVersion++;
			}
			$this->SaveInstalledVersion();
		}
		
	}
	
	function RegisterShortcode() {
		add_shortcode('simplegallery', array($this, 'ShortcodeHandler'));
	}
	
	/**
	 * Register the required JS/CSS so it is included in the page's <head> section
	 */
	function RegisterScripts() {

	    // Load the minified js and CSS files, unless these constants are set
	    $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	    $this->style_suffix = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';
	    
	    wp_enqueue_script('simple_gallery_js', "{$this->url}simple-gallery{$this->script_suffix}.js", array('jquery'), $this->version);
	    wp_enqueue_style('simple_gallery', "{$this->url}simple-gallery{$this->style_suffix}.css", array(), $this->version, 'screen');
	}
	
	/**
	 * Handler for the [simplegallery] shortcode
	 */
	function ShortcodeHandler($atts, $content = null) {
	
		// List of supported shortcode attributes and their default values
		$defaults = array(
		  'columns' => '1', // The number of columns for the thumbnails. If columns is set to 0, no row breaks will be included. Default: 1
		  'cssid' => '', // Unique HTML/CSS ID/selector for this image gallery. Default: auto-generated in the format simplegallery_x where x is a unique number
		  'exclude' => '', // Comma separated list of attachment IDs to exclude from this gallery. exclude="21,32,43" Please note that include and exclude cannot be used together (in that case include="" will take preference)
		  'fade' => 'out-in', // out-in|none|over	out-in: when changing the large image, fade the current image out, and fade the next image in (displays the "loading" message).
		  //						none: just change the large image with no fading
		  //						over: fades the new image over the top of the existing image (doesn't display a "loading" message). For this effect to work properly, all images should be the same size, and the largeimagewidth and largeimageheight parameters should be set to this size.
		  'fadespeed' => 600, // Number of milliseconds it takes to fade in the image. Has no effect if fade="none"
		  'id' => get_the_ID(), // The page/post ID to display the images from. The gallery will display images which are attached to that post. The default behaviour if no ID is specified is to display images attached to the current post.
		  'include' => '', // Comma separated list of attachment IDs to include in this gallery. include="23,39,45 will show only these attachment IDs. 
		  'largeimagewidth' => '600', // Maximum width of the large image (in pixels). Default: 600
		  'largeimageheight' => '400', // Maximum height of the large image (in pixels). Default: 400
		  'navigation' => 1, // Whether or not to display the next/previous navigation. 1=Yes, 0=No. Default: 1
		  'orderby' => 'menu_order', // The field used to sort the thumbnails. The default is "menu_order". Valid values: menu_order|ID|rand
		   //						menu_order: Sort by the how the images are sorted on the media gallery tab.
		   //						ID: Sort by the attachment ID. ie. the order that they were uploaded into the media library.
		   //						rand: Randomly order the thumbnails on each page load. The 'order' parameter is ignored if orderby=rand
		  'order'      => 'ASC', // Sort order (ascending or descending) for the thumbnails. Valid values: ASC | DESC. Default: ASC.
		  'thumbnailalign' => 'left', // Thumbnail section alignment. Valid values: left | right | top | bottom    Default: left
		  'thumbnailscroll' => 0, // Whether or not to display the next/previous scrolling navigation. Only supported when thumbnailalign="bottom" 1=Yes, 0=No. Default: 0
		  'size' => 'thumbnail'  // Thumbnail image size. Valid values: thumbnail | medium | large   default: thumbnail     The size of the images for "thumbnail", "medium" and "large" can be configured in WordPress admin panel under Settings > Media
		);
		
		$atts = shortcode_atts( $defaults, $atts);
		
		// Valid values for each parameter
		$validValues['thumbnailalign'] = array('left', 'right', 'top', 'bottom');
		$validValues['fade'] = array('out-in', 'none', 'over');
		
		$atts['largeimagewidth'] = intval($atts['largeimagewidth']);
		if (! $atts['largeimagewidth'] ) $atts['largeimagewidth'] = $defaults['largeimagewidth'];
		
		$atts['largeimageheight'] = intval($atts['largeimageheight']);
		if (! $atts['largeimageheight'] ) $atts['largeimageheight'] = $defaults['largeimageheight'];

		$atts['fadespeed'] = intval($atts['fadespeed']);
		if (! $atts['fadespeed'] ) $atts['fadespeed'] = $defaults['fadespeed'];

		$atts['navigation'] = intval($atts['navigation']);

	   // Validate each of the parameters
        foreach ($atts as $key => $value) {
            if (isset($validValues[$key])) {
                if (!in_array($value, $validValues[$key])) {
                    // Invalid value
                    if (isset($defaults[$key])) {
                    	//use default instead
                    	$atts[$key] = $defaults[$key];
                    } else {
                    	unset($atts[$key]);
                    }
                }
            } else {
                $atts[$key] = esc_attr($value);
            }
        }

		$atts['thumbnailscroll'] = intval($atts['thumbnailscroll']);
		if ( 'bottom' != $atts['thumbnailalign'] ) // Only supported when thumbnailalign=bottom
			$atts['thumbnailscroll'] = 0;

		// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
		if ( isset( $attr['orderby'] ) ) {
			if ($attr['orderby'] == 'rand') {
			    $attr['order'] = '';
			} else {
			    $attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			}
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
		
		extract( $atts, EXTR_SKIP );
		if (empty($cssid)) {
			$cssid = 'simplegallery_' . self::$number;
		}

		// CSS rules for this image gallery
        $selector = "#$cssid";

        $css = <<<EOD
$selector .loading { background:transparent url('{$this->url}loading.gif') no-repeat scroll 50% 20%; }
$selector .largeimage img { max-width: {$largeimagewidth}px; max-height: {$largeimageheight}px; }
$selector .largeimage { width: {$largeimagewidth}px; height: {$largeimageheight}px; }

EOD;
        
        // CSS rules that are dependent on the gallery thumbnail alignnment
        switch ($thumbnailalign) {
        	case 'left':
        	case 'right':
        		// Float the thumbnails to the left/right
        		$css .= <<<EOD
$selector .thumbnails { float: $thumbnailalign; height: {$largeimageheight}px; }
EOD;
        		break;
        	case 'top':
        	case 'bottom':
        		// thumbnails position is controlled below (by the HTML output order)
        		
        		// Override the columns parameter so the thumbs appear next to each other
        		$columns = 0;
        		
        		// Remove all floats and dimensions
        		$css .= <<<EOD
$selector, $selector .thumbnails { height: auto; }
$selector .largeimage, $selector .thumbnails { width: 100%; }
$selector .thumbnails { float: none; }
$selector .largeimage { float: none; }
$selector .thumbnails .gallery-item { width: auto !important; }
EOD;
        		break;
        }
		
        $html = <<<EOD
<style type="text/css">
$css
</style>
<script type="text/javascript">
//<![CDATA[
if (typeof gallerySettings === 'undefined') {
	var gallerySettings = []; // Define the empty settings array
}

EOD;

        $html .= <<<EOD
gallerySettings['$cssid'] = {
  fade : '$fade',
  fadespeed : $fadespeed,
  thumbnailscroll : $thumbnailscroll
}
//]]>
</script>

EOD;
		
		$html .= '<div class="simplegallery" id="' . $cssid . '">';
		
		// Thumbnail panel HTML code
		// See http://codex.wordpress.org/Gallery_Shortcode for documentation on the WordPress [gallery] shortcode
		// gallery_shortcode() in wp-includes/media.php is the WordPress [gallery] shortcode handler function
		$class = 'thumbnails';
		if ( $thumbnailscroll ) {
			$class .= ' hasthumbnailscroll';
		}
		$thumbs = '<div class="' . $class. '">';
		if ( $thumbnailscroll ) {
			$thumbs .= '<div class="thumbnailscroll thumbnailscroll-prev">&nbsp;</div>';
			$thumbs .= '<div class="thumbnailscroll thumbnailscroll-next">&nbsp;</div>';
		}
		$thumbs .= '<div class="gallerywrapper">';
		$thumbs .= do_shortcode("[gallery columns=$columns link=file id=$id orderby=\"$orderby\" order=\"$order\" size=\"$size\" include=\"$include\" exclude=\"$exclude\"]");
		$thumbs .= '</div>'; // end div.gallerywrapper
		$thumbs .= '</div>'; // end div.thumbnails
		
		// Large image panel HTML code
		$large = '<div class="largeimage loading">';
		
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby, 'numberposts' => 1) );
		
		// If there are no images for the gallery, then don't output anything
		if (!is_array($attachments)) return '';
		
		$link = '';
		$caption = '';
		foreach ($attachments as $att) {
			$link = wp_get_attachment_image_src($att->ID, 'full');
			$caption = $att->post_excerpt;
		}
		

		$large .= '<a href="' . $link[0] . '"><img src="' . $this->url . '/img/trans.gif" title="" alt="" class="simplegallerylargeimage" /></a>';
		
		// Display image caption
		$large .= '<h3 class="imagecaption">' . $caption . '</h3>';
		
		if ($navigation) {
			// Display image navigation
			$large .= '<div class="simplegallerynavbar"></div>';
		}
		
		$large .= "</div>";
		
		
		// Now output the thumbnail/largeimage divs in the appropriate order
		if ($thumbnailalign == 'bottom') {
			// Bottom thumbnail alignment -> print large image div then thumbnail div
			$html .= $large . $thumbs;
		} else {
			// left, right or top alignment -> print thumbnail div first
			$html .= $thumbs . $large;
		}
		
		$html .= "<div class=\"clearboth\"></div></div>";
		
		self::$number++;
		
		return $html;
	}
	
    /**
     * If using the shutter reloaded plugin, force the shutter scripts to be loaded in the header.
     * If they are loaded in the footer, then the full screen shutter view doesn't work properly in IE8.
     * See Bug #1406
     * 
     * Called during get_option('srel_options')
     */
    function ForceShutterReloadedInHead($value) {
        $value['headload'] = true;
        return $value;
    }
	
	function SaveInstalledVersion() {
		update_option('om4_simple_gallery_db_version', $this->installedVersion);
	}
}

if(defined('ABSPATH') && defined('WPINC')) {
	if (!isset($GLOBALS["om4_Simple_Gallery"])) {
		$GLOBALS["om4_Simple_Gallery"] = new OM4_Simple_Gallery();
	}
}

?>