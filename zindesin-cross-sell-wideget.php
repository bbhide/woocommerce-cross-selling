<?php
/*
Plugin Name: Zindesin Cross Sell Widget
Plugin URI: http://www.ctree.com/
Description: Zindesin Cross Sell  Widget grabs a Cross Sell post and the associated thumbnail to display on your sidebar
Author: Priyanka Jain
Version: 1
Author URI: https://twitter.com/73ullet_
*/
 
 
class ZindesinCrossSellWidget extends WP_Widget
{
  function ZindesinCrossSellWidget()
  {
    $widget_ops = array('classname' => 'ZindesinCrossSellWidget', 'description' => 'Displays a zindesin Cross Sell ' );
    $this->WP_Widget('ZindesinCrossSellWidget', 'Zindesin Cross Sell', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) 
  {
  
  extract($args, EXTR_SKIP);
  // WIDGET CODE GOES HERE
  global $wpdb, $woocommerce;
  $post_id = get_the_ID();
  $all_orders = $wpdb->get_results( "
        SELECT woi.order_id 
                        FROM  `". $wpdb->prefix ."woocommerce_order_items` woi
                          INNER JOIN  `". $wpdb->prefix ."woocommerce_order_itemmeta` woim
                          ON woi.order_item_id = woim.order_item_id
                        WHERE  woim.`meta_key` =  '_product_id'
                        AND woim.`meta_value` = ".$post_id."
                ");
 
  if(!empty($all_orders)){
    foreach ($all_orders as $order) {
      $tempporducts = $wpdb->get_results("
                        SELECT DISTINCT woi.order_item_name ,woi.order_id ,woi.order_item_id,woim.*
                        FROM  `". $wpdb->prefix ."woocommerce_order_items` woi
                          INNER JOIN  `". $wpdb->prefix ."woocommerce_order_itemmeta` woim
                          ON woi.order_item_id = woim.order_item_id
                        WHERE  woi.order_id =".$order->order_id."
                        AND woim.`meta_key` =  '_product_id'
                        AND woim.`meta_value` <> ".$post_id."
                        
                      ");
      foreach ($tempporducts as $key => $tempproduct) {
          $cross_orders[$tempproduct->meta_value] = $tempproduct;
      }
    }
  }

  if(empty($cross_orders)){
    return false;
  }

  foreach ($cross_orders as $orders) {
    $product = new WC_Product($orders->meta_value);
    $product->id = $orders->meta_value;
    if($product->post->post->post_status == 'publish') {
      $img['file'] = wp_get_attachment_url( get_post_thumbnail_id( $orders->meta_value, 'thumbnail') );
      $img['c'] = '1.1';
      $img['w'] = '60';
      $cross_selling_product = array();
      $cross_selling_product['img'] = slir($img);
      $cross_selling_product['permalink'] = get_permalink( $orders->meta_value);
      $cross_selling_product['title'] =  $orders->order_item_name ." - " .$product->get_price_html();
      $cross_selling_product['add_to_cart'] = '<form action="'. do_shortcode('[add_to_cart_url id="'.$orders->meta_value.'"]') .'" class="submit_cart" method="post" enctype="multipart/form-data" data-product_id="' . $orders->meta_value .'">   
                                                  <button type="submit" class="btn btn-small button add-to" type="button">Add to cart</button>
                                              </form>';
      //end post status check if
      $cross_selling_products[] = $this->format_cross_selling_list($cross_selling_product);
    } else {
      //echo "<pre>"; print_r($product->post->post->post_status); echo "</pre>";
    }
  }
    if( !$cross_selling_products ) {
      return false;
    }
    
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title)) {
      echo $before_title . $title . $after_title;
    }
    
    echo join( ' ', $cross_selling_products );
    
    echo $after_widget;
  
  }
 
  function format_cross_selling_list($product) {

        $formatted_item = '
        <li>
          <div class="cross_sell_img">
            <a href="'. $product['permalink'] .'" >
              <img src="'. $product['img'] .'" >
            </a>
          </div>
          <div class="cross_sell_price_title">
            <a href="' . $product['permalink'] . '" >
                '. $product['title'] .'
            </a>
          </div>
          <div class="cross_sell_cart">
            '. $product['add_to_cart'] .'
          </div>
        </li>';
        return $formatted_item;
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("ZindesinCrossSellWidget");') );

?>
