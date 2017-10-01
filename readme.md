### Owl Carousel for WordPress
<sup>*This plugin is based off [Tanel Kollamaa's "WP Owl Carousel"](https://wordpress.org/plugins/wp-owl-carousel/) (no longer maintained)*</sup>

* Install & Activate plugin inside WordPress admin
* Create Carousels using the menu in the admin sidebar
* Use Carousel in your posts using shortcode `[wp_owl id="xxxx"]`

#### Hooks

`wp_owl_before_carousel` - Called before the .owl-carousel wrapper is output.

`wp_owl_after_carousel` - Called after the .owl-carousel wrapper is output

`wp_owl_before_carousel_image` - Called before the carousel image is output

`wp_owl_after_carousel_image` - Called after the carousel image is output

#### Filters

`wp_owl_enqueue_assets` - Toggle **all** Owl Carousel asset loading.

`wp_owl_enqueue_vendor_css` - Toggle Owl Carousel vendor css loading.

`wp_owl_enqueue_theme_css` - Toggle WP Owl Carousel css loading.

`wp_owl_enqueue_vendor_js` - Toggle Owl Carousel vendor js loading.

`wp_owl_enqueue_plugin_js` - Toggle WP Owl Carousel js loading.

#### Example: Disable asset loading
```php
add_filter( 'wp_owl_enqueue_assets', '__return_false' );
```