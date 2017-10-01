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

    add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
    add_action( 'init', array( $this, 'create_post_type' ) );
    add_action( 'edit_form_after_title', array( $this, 'render_shortcode_helper' ) );
    add_action( 'cmb2_init', array( $this, 'create_metaboxes' ) );
    add_shortcode( 'wp_owl', array( $this, 'shortcode' ) );

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

  function create_post_type() {
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

    echo sprintf( '<p>%s: [wp_owl id="%s"]</p>', __( 'Paste this shortcode into a post or a page', 'wp_owl' ), $post->post_name );
  }

  function shortcode( $atts, $content = null ) {
    $attributes = shortcode_atts( array(
      'id' => ''
    ), $atts );

    return $this->generate_owl_html( esc_attr( $attributes['id'] ) );
  }

  private function get_image_attr( $lazy_load, $image ) {
    if ( $lazy_load ) {
      return "data-src=\"{$image[0]}\" class=\"owl-lazy\"";
    }
    else {
      return "src=\"{$image[0]}\"";
    }
  }

  function generate_owl_html( $slug ) {
    $id = $this->wp_slug_to_id( $slug );
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
