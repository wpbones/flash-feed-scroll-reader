<?php
/**
 * The ticker widget's form in the Widgets screen.
 *
 * @var \NSFeedScrollReaderPlugin\Widgets\TickerWidget $widget
 * @var array $instance
 */

if (!defined('ABSPATH')) {
  exit();
}

?>
<p>
  <label for="<?php echo esc_attr($widget->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'flash-feed-scroll-reader'); ?></label>
  <input class="widefat" type="text"
         id="<?php echo esc_attr($widget->get_field_id('title')); ?>"
         name="<?php echo esc_attr($widget->get_field_name('title')); ?>"
         value="<?php echo esc_attr($instance['title']); ?>">
</p>
<p>
  <label for="<?php echo esc_attr($widget->get_field_id('feeds')); ?>"><?php esc_html_e('Feed URLs, one per line:', 'flash-feed-scroll-reader'); ?></label>
  <textarea class="widefat" rows="3"
            id="<?php echo esc_attr($widget->get_field_id('feeds')); ?>"
            name="<?php echo esc_attr($widget->get_field_name('feeds')); ?>"><?php echo esc_textarea($instance['feeds']); ?></textarea>
  <small><?php esc_html_e('Empty: the feeds of Settings > Feed Scroll Reader.', 'flash-feed-scroll-reader'); ?></small>
</p>
<p>
  <label for="<?php echo esc_attr($widget->get_field_id('max_items')); ?>"><?php esc_html_e('Items:', 'flash-feed-scroll-reader'); ?></label>
  <input type="number" min="1" max="100" class="small-text"
         id="<?php echo esc_attr($widget->get_field_id('max_items')); ?>"
         name="<?php echo esc_attr($widget->get_field_name('max_items')); ?>"
         value="<?php echo esc_attr($instance['max_items']); ?>">
  <label for="<?php echo esc_attr($widget->get_field_id('speed')); ?>"><?php esc_html_e('Speed (px/s):', 'flash-feed-scroll-reader'); ?></label>
  <input type="number" min="10" max="400" class="small-text"
         id="<?php echo esc_attr($widget->get_field_id('speed')); ?>"
         name="<?php echo esc_attr($widget->get_field_name('speed')); ?>"
         value="<?php echo esc_attr($instance['speed']); ?>">
</p>
<p>
  <label for="<?php echo esc_attr($widget->get_field_id('height')); ?>"><?php esc_html_e('Height:', 'flash-feed-scroll-reader'); ?></label>
  <input type="text" class="small-text"
         id="<?php echo esc_attr($widget->get_field_id('height')); ?>"
         name="<?php echo esc_attr($widget->get_field_name('height')); ?>"
         value="<?php echo esc_attr($instance['height']); ?>" placeholder="2.75em">
  <label for="<?php echo esc_attr($widget->get_field_id('description')); ?>"><?php esc_html_e('Descriptions:', 'flash-feed-scroll-reader'); ?></label>
  <select id="<?php echo esc_attr($widget->get_field_id('description')); ?>"
          name="<?php echo esc_attr($widget->get_field_name('description')); ?>">
    <option value="" <?php selected($instance['description'], ''); ?>><?php esc_html_e('Default', 'flash-feed-scroll-reader'); ?></option>
    <option value="1" <?php selected($instance['description'], '1'); ?>><?php esc_html_e('Show', 'flash-feed-scroll-reader'); ?></option>
    <option value="0" <?php selected($instance['description'], '0'); ?>><?php esc_html_e('Hide', 'flash-feed-scroll-reader'); ?></option>
  </select>
</p>
<p><small><?php esc_html_e('Empty fields use the defaults of Settings > Feed Scroll Reader.', 'flash-feed-scroll-reader'); ?></small></p>
