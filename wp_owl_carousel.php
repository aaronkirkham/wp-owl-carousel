<?php
/**
 * Plugin Name: Owl Carousel for WordPress
 * Description: Owl Carousel integration for Wordpress
 * Version: 1.0.0
 * Author: Aaron Kirkham
 * Text Domain: wp_owl
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
  die;
}

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

if ( is_admin() ) {
  require_once __DIR__ . '/vendor/cmb2/init.php';
}

include_once 'owl_settings.php';

class Wp_Owl_Carousel{
  protected $dir;
  protected $url;
  const prefix = 'wp_owl_';

  function __construct() {
    $this->url = plugin_dir_url( __FILE__ );
    $this->dir = plugin_dir_path( __FILE__ );

    add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
    add_action( 'init', array( $this, 'create_post_type' ) );
    add_action( 'edit_form_after_title', array( $this, 'render_shortcode_helper' ) );
    add_action( 'cmb2_init', array( $this, 'create_metaboxes' ) );
    add_shortcode( 'wp_owl', array( $this, 'shortcode' ) );

    load_plugin_textdomain( 'wp_owl', false, basename( dirname( __FILE__ ) ) . '/languages' );
  }

  function load_assets() {
    /*
    wp_enqueue_style( 'owl-style', $this->url . '/owl-carousel/owl.carousel.css' );
    wp_enqueue_style( 'owl-theme', $this->url . '/owl-carousel/owl.theme.css', array( 'owl-style' ) );
    wp_enqueue_script( 'owl-carousel', $this->url . '/owl-carousel/owl.carousel.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'wp-owl-carousel', $this->url . '/js/wp-owl-carousel.js', array( 'owl-carousel' ) );
    */
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
        'supports' => array( 'title', 'custom-fields' )
      )
    );
  }

  function create_metaboxes() {
    global $owl_settings;

    $carousel_metabox = new_cmb2_box( array(
      'id'            => 'wp_owl_metabox',
      'title'         => __( 'Owl Carousel', 'wp_owl' ),
      'object_types'  => array( 'wp_owl' ), // Post type
      'context'       => 'normal',
      'priority'      => 'high',
      'show_names'    => true,
      'closed'        => false
    ) );

    $carousel_metabox->add_field( array(
      'name'       => __( 'Images', 'wp_owl' ),
      'desc'       => __( 'Images to use', 'wp_owl' ),
      'id'         => self::prefix .'images',
      'type'       => 'file_list'
    ) );

    $image_sizes = get_intermediate_image_sizes();
    $carousel_metabox->add_field( array(
      'name'             => __( 'Select size', 'wp_owl' ),
      'desc'             => __( 'Select image size to use', 'wp_owl' ),
      'id'               => self::prefix . 'image_size',
      'type'             => 'select',
      'show_option_none' => false,
      'default'          => 'custom',
      'options'          => $image_sizes
    ) );

    $carousel_metabox->add_field( array(
      'name' => __( 'Rel attribute', 'wp_owl' ),
      'desc' => __( 'Used to open images in a lightbox, see the documentation of your lightbox plugin for this value', 'wp_owl' ),
      'default' => 'lightbox',
      'type' => 'text',
      'id' => self::prefix .'rel'
    ) );

    $carousel_metabox->add_field( array(
      'name' => __( 'Link to image size', 'wp_owl' ),
      'desc' => __( 'Generates link to specified image size', 'wp_owl' ),
      'type' => 'select',
      'id' => self::prefix .'link_to_size',
      'options' => array_merge( array( 'none' ), $image_sizes )
    ) );

    foreach( $owl_settings as $id => $setting ) {
      if ( $setting['cmb_type'] == 'checkbox' ) {
        $def = $this->set_checkbox_default( $setting['default'] );
      }
      else {
        $def = $setting['default'];
      }

      $carousel_metabox->add_field( array(
        'name' => $setting['name'],
        'description' => $setting['desc'],
        'id' => self::prefix . $id,
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

    echo sprintf( '<p>%s: <strong>[wp_owl id="%s"]</strong></p>', __( 'Paste this shortcode into a post or a page', 'wp_owl' ), $post->ID );
  }

  function shortcode( $atts, $content = null ) {
    $attributes = shortcode_atts( array(
      'id' => ''
    ), $atts );

    return $this->generate_owl_html( esc_attr( $attributes['id'] ) );
  }

  function generate_owl_html( $id ) {
    $files = $this->get_owl_items( $id );
    if ( empty( $files ) ) {
      return;
    }

    $size_id = get_post_meta( $id, self::prefix . 'image_size', true );
    $sizes = get_intermediate_image_sizes();

    $settings = json_encode( $this->generate_settings_array( $id ) );




    /*
    $lazyLoad = get_post_meta($id,self::prefix.'lazyLoad',true);
    $link_to_size = get_post_meta($id,self::prefix.'link_to_size',true);
    $rel = get_post_meta($id,self::prefix.'rel',true);
    $html = '<div id="owl-carousel-'.$id.'" class="owl-carousel" data-owloptions=\''.$settings.'\'>';
    foreach($files as $id => $url){
      $html .= '<div>';
      $img = wp_get_attachment_image_src( $id, $sizes[$size_id] );
      if($link_to_size != 0){
        $img_link = wp_get_attachment_image_src($id,$sizes[$link_to_size - 1]);

        $html .= '<a href="'.$img_link[0].'"';
        $html .= (!empty($rel)) ? ' rel="'.$rel.'"' : '';
        $html .= ' >';
      }

      $html .= '<img width="'.$img[1].'" height="'.$img[2].'" src="' . $img[0] .'" ';

      if($lazyLoad == 'on'){
        $html .= 'class="lazyOwl" ';
        $html .= 'data-src="'.$img[0].'" ';
      }
      $html .= '/>';

      $html .= ($link_to_size != 0) ? ' </a>' : '';

      $html .='</div>';
    }

    $html .='</div>';

    return $html;
    */
  }

  function get_owl_items( $id ) {
    $files = get_post_meta( $id, self::prefix . 'images', 1 );
    return $files;
  }

  function generate_settings_array( $id ) {
    global $owl_settings;
    $new_settings = array();

    foreach( $owl_settings as $key => $value ) {
      $saved = get_post_meta( $id, self::prefix . $key, true );

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
