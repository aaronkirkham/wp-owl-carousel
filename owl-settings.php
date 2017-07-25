<?php
/**
 * The main settings to pass to WordPress and Owl Carousel
 *
 * @author Aaron Kirkham
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  die;
}

$owl_settings = array(
  'items' => array(
    'name' => __( 'Items', 'wp_owl' ),
    'desc' => __( 'The number of items you want to see on the screen.', 'wp_owl' ),
    'default' => 3,
    'cmb_type' => 'text',
    'type' => 'number'
  ),
  'margin' => array(
    'name' => __( 'Margin', 'wp_owl' ),
    'desc' => __( 'Right margin (in pixels) on items.', 'wp_owl' ),
    'default' => 0,
    'cmb_type' => 'text',
    'type' => 'number'
  ),
  'loop' => array(
    'name' => __( 'Loop', 'wp_owl' ),
    'desc' => __( 'Infinity loop. Duplicate last and first items to get loop illusion.', 'wp_owl' ),
    'default' => false,
    'cmb_type' => 'checkbox',
    'type' => 'bool'
  ),
  'center' => array(
    'name' => __( 'Center', 'wp_owl' ),
    'desc' => __( 'Center item. Works well with even an odd number of items.', 'wp_owl' ),
    'default' => false,
    'cmb_type' => 'checkbox',
    'type' => 'bool'
  ),
  'nav' => array(
    'name' => __( 'Navigation', 'wp_owl' ),
    'desc' => __( 'Display "next" and "prev" buttons.', 'wp_owl' ),
    'default' => false,
    'cmb_type' => 'checkbox',
    'type' => 'bool'
  ),
  'navigationTextNext' => array(
    'name' => __( 'Navigation "Next"', 'wp_owl' ),
    'desc' => __( 'Text on "Next" button', 'wp_owl' ),
    'default' => 'Next ',
    'cmb_type' => 'text',
    'type' => 'string'
  ),
  'navigationTextPrev' => array(
    'name' => __( 'Navigation "Prev"', 'wp_owl' ),
    'desc' => __( 'Text on "Prev" button', 'wp_owl' ),
    'default' => 'Prev ',
    'cmb_type' => 'text',
    'type' => 'string'
  ),
  'dots' => array(
    'name' => __( 'Pagination', 'wp_owl' ),
    'desc' => __( 'Show pagination navigation.', 'wp_owl' ),
    'default' => true,
    'cmb_type' => 'checkbox',
    'type' => 'bool'
  ),
  'lazyLoad' => array(
    'name' => __( 'Lazy Load', 'wp_owl' ),
    'desc' => __( 'Delays loading of images. Images outside of viewport won\'t be loaded before user scrolls to them. Great for mobile devices to speed up page loadings.', 'wp_owl' ),
    'default' => false,
    'cmb_type' => 'checkbox',
    'type' => 'bool'
  ),
);
