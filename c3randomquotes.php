<?php
/*
Plugin Name: c3 Random Quotes
Plugin URI: http://www.creed3.com/
Description: Selects a random quote and displays it in a Wordpress sidebar or post.
Version: 1.3
Author: creed3 : Scott Hampton
Author URI: http://www.creed3.com
License: GPLv2
*/

/*  Copyright 2015-2020  Scott Hampton  (contact : http://www.creed3.com/)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

	// Register Style Sheet //

add_action( 'wp_enqueue_scripts', 'register_c3rq_styles' );

function register_c3rq_styles() {
	wp_register_style( 'c3rq', plugins_url( 'c3-random-quotes/c3rq.css' ) );
	wp_enqueue_style( 'c3rq' );
}

	// Define Widget Arg Names Array //
$c3rq_options = array('leading_quote','widget_width','font_size','quote_color','author_color','author_prefix');
	
	// Start Class //

class c3rq_widget extends WP_Widget {
 
	// Constructor //
	
    function __construct() {
    	load_plugin_textdomain('c3rq', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        parent::__construct(false, $name = __('c3 Random Quotes', 'c3rq'), array('description' => __('Selects a random quote and displays it in a Wordpress sidebar.', 'c3rq')) );	
    }
    
	// Extract Args & Display Random Quote //

	function widget($args, $instance) {
    	global $c3rq_quote;
		extract( $args );
		
		// Begin building quote block
		$c3rq_quote = '<div class="c3rq"';
		if ($instance['widget_width'] != ''){$c3rq_quote .= ' style="width:'.$instance['widget_width'].';"';}
		$c3rq_quote .= '>';
		
		// Add leading quote image/character & styles
		if ($instance['leading_quote'] != 'none'){$c3rq_quote .= '<img src="'.plugins_url( '/images/quote-'.$instance['leading_quote'].'.png', __FILE__ ).'" align="left" style="margin-right:4px;"/>';}
		$c3rq_quote .= '<div class="c3rq_quote"';
		if ($instance['font_size'] != '' || $instance['quote_color'] != ''){
			$c3rq_quote .= ' style="';
			if ($instance['font_size'] != ''){$c3rq_quote .= 'font-size:'.$instance['font_size'].';';}
			if ($instance['quote_color'] != ''){$c3rq_quote .= 'color:'.$instance['quote_color'].';';}
			$c3rq_quote .= '"';
		}
		$c3rq_quote .= '>';
		if ($instance['leading_quote'] == 'none'){$c3rq_quote .= '&ldquo;';}
		
		// Query db for page titled Quotes For Widget
		global $wpdb;
		$allquotes = $wpdb->get_var( "SELECT post_content FROM $wpdb->posts WHERE post_title = 'Quotes For Widget' AND post_status = 'private' AND post_type = 'page'" );
		// If page is found
		if ($allquotes){
			// Split array into separate lines
			$quotes = explode( "\n", str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $allquotes ) );
			
			// Select random quote and split into quote/attribution
			$quote = explode( "#",$quotes[ array_rand( $quotes ) ] );
		}
		// Set default quote to display
		else {
			$quote = array( 'No matter how much cats fight, there always seem to be plenty of kittens.','Abraham Lincoln' );
		}
		// Add end quote & begin author
		$c3rq_quote .= $quote[0].'&rdquo;</div><div class="c3rq_author"';
		if ($instance['font_size'] != '' || $instance['author_color'] != ''){
			$c3rq_quote .= ' style="';
			if ($instance['font_size'] != ''){$c3rq_quote .= 'font-size:'.$instance['font_size'].';';}
			if ($instance['author_color'] != ''){$c3rq_quote .= 'color:'.$instance['author_color'].';';}
			$c3rq_quote .= '"';
		}
		$c3rq_quote .= '>';
		
		// Add author prefix character
		if ($instance['author_prefix'] != ''){$c3rq_quote .= $instance['author_prefix'];}
		
		// Add author & finish quote block
		$c3rq_quote .= trim( $quote[1] ).'</div></div>';
		
		// Send to screen (also saved for shortcode)
		echo $c3rq_quote;
	}
		
	// Update Settings //
 
	function update($new_instance, $old_instance) {
		global $c3rq_options;
		$message = $type = NULL;
		
		foreach ($c3rq_options as $opt){$instance[$opt] = sanitize_text_field($new_instance[$opt]);}
		
		// Validate Input : leading_quote & author_prefix do not need validation
		
		if (isset($instance['widget_width']) && $instance['widget_width'] != ''){
			// quote width incorrect
			if ( substr($instance['widget_width'], -2) != 'px' || !preg_match('/\d*/' ,substr($instance['widget_width'], 0, -2)) ){$type = 'error';$message = __( 'The width of the quote widget is not in the correct format.<br />', 'c3rq' );echo "width is not good";}
		}
		else {$type = 'error';$message = __( 'The width of the quote widget is required.<br />', 'c3rq' );}
		if (isset($instance['font_size']) && $instance['font_size'] != ''){$font_size = 'font-size: '.$instance['font_size'].';';}
		else {$font_size = '';}
		if (isset($instance['quote_color']) && $instance['quote_color'] != ''){$quote_color = 'color: '.$instance['quote_color'].';';}
		else {$quote_color = '';}
		if (isset($instance['author_color']) && $instance['author_color'] != ''){$author_color = 'color: '.$instance['author_color'].';';}
		else {$author_color = '';}
		add_settings_error(
			'c3rq_message',
			'c3rq_message',
			$message,
			$type
		);		
		return $instance;
	}
 
	// Widget Control Panel //
	
	function form($instance) {
		global $c3rq_options;
		$defaults = array( 'show_quote' => 'on' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		// set arg values
		foreach ($c3rq_options as $opt){
			if (!isset($instance[$opt])){
				if ($opt == 'leading_quote'){$instance['leading_quote'] = 'none';}
				else {$instance[$opt] = '';}
			}
		}
?>
		<p>
			<label for="<?php echo $this->get_field_id('leading_quote'); ?>"><?php _e('Leading quotation mark image', 'c3rq'); ?>:</label>
			<select id="<?php echo $this->get_field_id('leading_quote'); ?>" name="<?php echo $this->get_field_name('leading_quote'); ?>" class="widefat" style="width:100%;">
				<option value="none" <?php selected('none', $instance['leading_quote']); ?>><?php _e('No image', 'c3rq'); ?></option>
				<option value="white" <?php selected('white', $instance['leading_quote']); ?>><?php _e('White', 'c3rq'); ?></option>
				<option value="black" <?php selected('black', $instance['leading_quote']); ?>><?php _e('Black', 'c3rq'); ?></option>
				<option value="gray" <?php selected('gray', $instance['leading_quote']); ?>><?php _e('Gray', 'c3rq'); ?></option>
				<option value="red" <?php selected('red', $instance['leading_quote']); ?>><?php _e('Red', 'c3rq'); ?></option>
				<option value="yellow" <?php selected('yellow', $instance['leading_quote']); ?>><?php _e('Yellow', 'c3rq'); ?></option>
				<option value="green" <?php selected('green', $instance['leading_quote']); ?>><?php _e('Green', 'c3rq'); ?></option>
				<option value="blue" <?php selected('blue', $instance['leading_quote']); ?>><?php _e('Blue', 'c3rq'); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('widget_width'); ?>"><?php _e('Width<span style="font-size:8pt"> (in pixels)</span> of quote widget<span style="font-size:8pt;color:#dd0000;"> (required)</span><br /><span style="font-size:8pt"> (must be a number and end with "px")</span>', 'c3rq'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('widget_width'); ?>" name="<?php echo $this->get_field_name('widget_width'); ?>" type="text" value="<?php echo $instance['widget_width']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('font_size'); ?>"><?php _e('Font size (optional)<br /><span style="font-size:8pt"> (must be a number and end with "px" or "pt")</span>', 'c3rq'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('font_size'); ?>" name="<?php echo $this->get_field_name('font_size'); ?>" type="text" value="<?php echo $instance['font_size']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('quote_color'); ?>"><?php _e('Font color of quote (optional)<br /><span style="font-size:8pt"> (e.g. "#ff0000" or "red")</span>', 'c3rq'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('quote_color'); ?>" name="<?php echo $this->get_field_name('quote_color'); ?>" type="text" value="<?php echo $instance['quote_color']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('author_color'); ?>"><?php _e('Font color of attribution (optional)<br /><span style="font-size:8pt"> (e.g. "#ff0000" or "red")</span>', 'c3rq'); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('author_color'); ?>" name="<?php echo $this->get_field_name('author_color'); ?>" type="text" value="<?php echo $instance['author_color']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('author_prefix'); ?>"><?php _e('Attribution prefix', 'c3rq'); ?>:</label>
			<select id="<?php echo $this->get_field_id('author_prefix'); ?>" name="<?php echo $this->get_field_name('author_prefix'); ?>" class="widefat" style="width:100%;">
				<option value="" <?php selected('', $instance['author_prefix']); ?>><?php _e('No prefix', 'c3rq'); ?></option>
				<option value="-" <?php selected('-', $instance['author_prefix']); ?>><?php _e('Single hyphen ( - )', 'c3rq'); ?></option>
				<option value="--" <?php selected('--', $instance['author_prefix']); ?>><?php _e('Double hyphen ( -- )', 'c3rq'); ?></option>
				<option value=":" <?php selected(':', $instance['author_prefix']); ?>><?php _e('Single colon ( : )', 'c3rq'); ?></option>
				<option value="::" <?php selected('::', $instance['author_prefix']); ?>><?php _e('Double colon ( :: )', 'c3rq'); ?></option>
				<option value=".:" <?php selected('.:', $instance['author_prefix']); ?>><?php _e('Period and colon ( .: )', 'c3rq'); ?></option>
				<option value="~" <?php selected('~', $instance['author_prefix']); ?>><?php _e('Single tilde ( ~ )', 'c3rq'); ?></option>
			</select>
		</p>
    <?php }
}

	// Shortcode Function //
	
    function c3rq_shortcode($atts, $content=null) {
    	global $c3rq_quote;
        return $c3rq_quote;
    }
    add_shortcode('c3rq', 'c3rq_shortcode');

// End class c3rq_widget
add_action ( 'widgets_init', 'c3rq_init_widget' );
function c3rq_init_widget() {
	return register_widget('c3rq_widget');
}