<?php
/**
Plugin Name: CBEARNINGS WIDGET
Plugin URI: http://www.ifyouknowit.com/
Description: This plugin,widget will provide clickbank database. To help click bank affilates in their effort to promote clickbank products.It has short code, search widget, and set clickbank id from admin panel.want professional version check <a href="http://magento.ifyouknowit.com">Go pro</a>   
Author: ifyouknowit.com
Version: 1.0
Author URI: http://www.ifyouknowit.com/
Donate link: http://www.ifyouknowit.com/

*/
class cbe_widget extends WP_Widget {
        
        public function __construct() {
		parent::WP_Widget(false,'Find CB keywords','description=To search');
        }

        public function form( $instance ) {
               // outputs the options form on admin
		if( $instance) {
		     $title = esc_attr($instance['title']);    
		} else {
		     $title = '';
		}
         ?>

		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
	<?php
        }

        public function update( $new_instance, $old_instance ) {
               // processes widget options to be saved

	     	      $instance = $old_instance;
		      
		      $instance['title'] = strip_tags($new_instance['title']);
		      
	     return $instance;
        }

        public function widget( $args, $instance ) {
               // outputs the content of the widget

 	extract($args);
       // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);	   
	   echo $before_widget;
	   // Display the widget
	   echo '<div class="mywidget">';

	   // Check if title is set
	   if ( $title ) {
	      echo $before_title . $title . $after_title;
	   }
	   // Check if text is set
	   echo '</div>';

	   echo '<div id="sbox">';
  	   echo '<form method="post"><input id="cbtext" name="cbtext" type="text"><br/><input type="submit" name="cbgo" id="cbgo" value="Go"></form>';
	   echo '</div>';
	   echo $after_widget;

        }

}


function ok_register_widgets_cb() {
 register_widget('cbe_widget');
}

add_action('widgets_init', 'ok_register_widgets_cb');
?>
