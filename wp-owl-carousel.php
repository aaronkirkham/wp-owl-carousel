<?php
/**
 * Plugin Name: Owl Carousel for WordPress
 * Description: Owl Carousel integration for WordPress
 * Version: 2.0.1
 * Author: Aaron Kirkham
 * Author URI: https://kirkh.am
 * Text Domain: wp_owl
 * License: GPL2
 */

// This plugin is based off Tanel Kollamaa's "WP Owl Carousel"
// https://wordpress.org/plugins/wp-owl-carousel/

/*
  Copyright 2015  Tanel Kollamaa  (email : tanelkollamaa@gmail.com)

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

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// initialise the updater
require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
$check_for_updates = Puc_v4_Factory::buildUpdateChecker(
  'https://github.com/aaronkirkham/wp-owl-carousel/',
  __FILE__,
  'wp-owl-carousel'
);

if ( is_admin() ) {
  require_once __DIR__ . '/cmb2/init.php';
}

include_once 'owl-settings.php';

class Wp_Owl_Carousel {
  protected $dir;
  protected $url;
  const WP_OWL_PREFIX = 'wp_owl_';

  function __construct() {
    $this->url = untrailingslashit( plugin_dir_url( __FILE__ ) );
    $this->dir = untrailingslashit( plugin_dir_path( __FILE__ ) );

    add_action( 'init', array( $this, 'init' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
    add_action( 'edit_form_after_title', array( $this, 'render_shortcode_helper' ) );
    add_action( 'cmb2_init', array( $this, 'create_metaboxes' ) );
    add_shortcode( 'wp_owl', array( $this, 'shortcode' ) );

    // admin
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_load_assets' ) );
    add_action( 'wp_ajax_get_owl_carousels', array( $this, 'admin_ajax_get_carousels' ) );

    load_plugin_textdomain( 'wp_owl', false, basename( dirname( __FILE__ ) ) . '/languages' );
  }

  function load_assets() {
    // don't load any assets
    if ( ! apply_filters( 'wp_owl_enqueue_assets', true ) ) {
      return;
    }

    // enqueue base style
    if ( apply_filters( 'wp_owl_enqueue_vendor_css', true ) ) {
      wp_enqueue_style( 'owl-carousel-style', $this->url . '/owlcarousel2/dist/assets/owl.carousel.min.css', array(), false );

      // enqueue theme style
      if ( apply_filters( 'wp_owl_enqueue_theme_css', true ) ) {
        wp_enqueue_style( 'wp-owl-theme', $this->url . '/owlcarousel2/dist/assets/owl.carousel.default.css', array( 'owl-carousel-style' ), false );
      }
    }

    // enqueue js
    if ( apply_filters( 'wp_owl_enqueue_vendor_js', true ) ) {
      wp_enqueue_script( 'owl-carousel-script', $this->url . '/owlcarousel2/dist/owl.carousel.min.js', array( 'jquery' ), false, true );

      // enqueue plugin js
      if ( apply_filters( 'wp_owl_enqueue_plugin_js', true ) ) {
        wp_enqueue_script( 'wp-owl-js', $this->url . '/assets/js/wp-owl-carousel.js', array( 'owl-carousel-script' ), false, true );
      }
    }
  }

  function admin_load_assets() {
    wp_enqueue_style( 'wp-owl-carousel-admin', $this->url . '/assets/css/wp-owl-admin.css' );
  }

  // TODO: delete cached results if a wp_owl post gets added/edited/deleted
  function admin_ajax_get_carousels() {
    ob_start();

    $posts = get_posts( array( 'post_type' => 'wp_owl' ) );

    // TODO: use cached results

    foreach ( $posts as $post ) {
      $orig_files = $this->get_owl_items( $post->ID );

      // grab only the latest 5 images
      $files = array_slice( $orig_files, 0, 5, true );

      echo '<input type="checkbox" name="" id="wp-owl-carousel-' . $post->ID . '" class="wp-owl-carousel-item__checkbox" />';
      echo '<label for="wp-owl-carousel-' . $post->ID . '" class="wp-owl-carousel-item" data-id="' . $post->ID . '">';
      echo '  <h2 class="wp-owl-carousel-item__title">' . $post->post_title . '</h2>';
      echo '  <p class="wp-owl-carousel-p">Images in carousel:</p>';
      echo '  <div class="wp-owl-carousel-item__images">';
      
      foreach ( $files as $attachment_id => $attachment_url ) {
        $image = wp_get_attachment_image_src( $attachment_id, array( 50, 50 ) );
        echo '    <img src="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" class="wp-owl-carousel-item__image" />';
      }

      // do we have any files remaining from the slice?
      $diff = sizeof( $orig_files ) - sizeof( $files );
      if ( $diff > 0 ) {
        echo '    <div class="wp-owl-carousel-item__image-placeholder"><span>+' . $diff . '</span></div>';
      }

      echo '  </div>';
      echo '</label>';
    }

    // TODO: cache the results

    echo ob_get_clean();
    wp_die();
  }

  function mce_register_buttons( $buttons ) {
    array_push( $buttons, 'wp_owl' );
    return $buttons;
  }

  function mce_add_buttons( $plugin_array ) {
    $plugin_array['wp_owl'] = $this->url . '/assets/js/wp-owl-admin.js';
    return $plugin_array;
  }

  function init() {
    // register mce buttons
    add_filter( 'mce_buttons', array( $this, 'mce_register_buttons' ) );
    add_filter( 'mce_external_plugins', array( $this, 'mce_add_buttons' ) );

    // register custom post type
    register_post_type( 'wp_owl',
      array(
        'labels' => array(
          'name' => __( 'Owl Carousel', 'wp_owl' ),
          'singular_name' => __( 'Owl Carousel', 'wp_owl' )
        ),
        'public' => false,
        'has_archive' => false,
        'publicaly_queryable' => false,
        'query_var' => false,
        'show_ui' => true,
        'supports' => array( 'title', 'custom-fields' ),
        'menu_icon' => 'dashicons-images-alt2'
      )
    );
  }

  function create_metaboxes() {
    global $owl_settings;

    $carousel_metabox = new_cmb2_box( array(
      'id' => 'wp_owl_metabox',
      'title' => __( 'Owl Carousel', 'wp_owl' ),
      'object_types' => array( 'wp_owl' ),
      'context' => 'normal',
      'priority' => 'high',
      'show_names' => true,
      'closed' => false
    ) );

    $carousel_metabox->add_field( array(
      'name' => __( 'Images', 'wp_owl' ),
      'desc' => __( 'Images to use', 'wp_owl' ),
      'id' => self::WP_OWL_PREFIX . 'images',
      'type' => 'file_list'
    ) );

    $image_sizes = get_intermediate_image_sizes();
    $carousel_metabox->add_field( array(
      'name' => __( 'Select size', 'wp_owl' ),
      'desc' => __( 'Select image size to use.', 'wp_owl' ),
      'id' => self::WP_OWL_PREFIX . 'image_size',
      'type' => 'select',
      'show_option_none' => false,
      'default' => 'custom',
      'options' => $image_sizes
    ) );

    $carousel_metabox->add_field( array(
      'name' => __( 'Rel attribute', 'wp_owl' ),
      'desc' => __( 'Used to open images in a lightbox, see the documentation of your lightbox plugin for this value.', 'wp_owl' ),
      'default' => 'lightbox',
      'type' => 'text',
      'id' => self::WP_OWL_PREFIX . 'rel'
    ) );

    $carousel_metabox->add_field( array(
      'name' => __( 'Link to image size', 'wp_owl' ),
      'desc' => __( 'Generates link to specified image size.', 'wp_owl' ),
      'type' => 'select',
      'id' => self::WP_OWL_PREFIX . 'link_to_size',
      'options' => array_merge( array( 'none' ), $image_sizes )
    ) );

    foreach ( $owl_settings as $id => $setting ) {
      if ( $setting['cmb_type'] == 'checkbox' ) {
        $def = $this->set_checkbox_default( $setting['default'] );
      }
      else {
        $def = $setting['default'];
      }

      $carousel_metabox->add_field( array(
        'name' => $setting['name'],
        'description' => $setting['desc'],
        'id' => self::WP_OWL_PREFIX . $id,
        'type' => $setting['cmb_type'],
        'default' => $def
      ) );
    }
  }

  function render_shortcode_helper() {
    global $post;
    if ( $post->post_type != 'wp_owl' ) {
      return;
    }

    echo sprintf( '<p>%s: [wp_owl id="%s"]</p>', __( 'Paste this shortcode into a post or a page', 'wp_owl' ), $post->ID );
  }

  function shortcode( $atts, $content = null ) {
    $attributes = shortcode_atts( array(
      'id' => '',
      'slug' => ''
    ), $atts );

    return $this->generate_owl_html( $attributes );
  }

  private function get_image_attr( $lazy_load, $image ) {
    if ( $lazy_load ) {
      return "data-src=\"{$image[0]}\" class=\"owl-lazy\"";
    }
    else {
      return "src=\"{$image[0]}\"";
    }
  }

  private function get_id_from_attributes( $attributes ) {
    $id = $attributes['id'];
    $slug = $attributes['slug'];

    if ( strlen( $id ) > 0 ) {
      return (int)$id;
    }
    else if ( strlen( $slug ) > 0 ) {
      return $this->wp_slug_to_id( $slug );
    }

    return null;
  }

  function generate_owl_html( $attributes ) {
    $id = $this->get_id_from_attributes( $attributes );
    if ( $id === null ) {
      return;
    }

    $files = $this->get_owl_items( $id );

    if ( empty( $files ) ) {
      return;
    }

    $break_container = get_post_meta( $id, self::WP_OWL_PREFIX . 'break_container', true );
    $lazy_load = get_post_meta( $id, self::WP_OWL_PREFIX . 'lazyLoad', true );
    $size_id = get_post_meta( $id, self::WP_OWL_PREFIX . 'image_size', true );
    $sizes = get_intermediate_image_sizes();
    $settings = json_encode( $this->generate_settings_array( $id ) );

    ob_start();

    do_action( 'wp_owl_before_carousel', $id );

    // TODO: add some filters so people can customize id/class on both the container AND image.
    echo sprintf( '<div id="owl-carousel-%s" class="owl-carousel" data-owl-options="%s">', $id, htmlspecialchars( $settings, ENT_QUOTES, 'utf-8' ) );

    foreach ( $files as $attachment_id => $attachment_url ) {
      $image = wp_get_attachment_image_src( $attachment_id, $sizes[$size_id] );

      do_action( 'wp_owl_before_carousel_image', $id, $image );

      echo sprintf( '<img %s width="%s" height="%s" />', $this->get_image_attr( $lazy_load, $image ), $image[1], $image[2] );

      do_action( 'wp_owl_after_carousel_image', $id, $image );
    }

    echo '</div>';

    do_action( 'wp_owl_after_carousel', $id );

    return ob_get_clean();
  }

  // TODO: maybe just use ids to save the extra query?
  private function wp_slug_to_id( $slug ) {
    $posts = get_posts( array(
      'post_type' => 'wp_owl',
      'posts_per_page' => 1,
      'post_name__in' => array( $slug )
    ) );

    return sizeof( $posts ) > 0 ? $posts[0]->ID : null;
  }

  function get_owl_items( $id ) {
    $files = get_post_meta( $id, self::WP_OWL_PREFIX . 'images', 1 );
    return $files;
  }

  function generate_settings_array( $id ) {
    global $owl_settings;
    $new_settings = array();

    foreach( $owl_settings as $key => $value ) {
      $saved = get_post_meta( $id, self::WP_OWL_PREFIX . $key, true );

      if ( $owl_settings[$key]['cmb_type'] == 'checkbox' ) {
        $new_settings[$key] = ($saved == 'on' ? true : false);
      }
      else {
        if ( $owl_settings[$key]['type'] == 'number' ) {
          $saved = (int)$saved;
        }

        $new_settings[$key] = $saved;
      }
    }

    return $new_settings;
  }

  function set_checkbox_default( $default ) {
    return isset( $_GET['post'] ) ? '' : ( $default ? (string)$default : '' );
  }
}

new Wp_Owl_Carousel();
