### Owl Carousel for WordPress
<sup>*This plugin is based off [Tanel Kollamaa's "WP Owl Carousel"](https://wordpress.org/plugins/wp-owl-carousel/) (no longer maintained)*</sup>

* Install & Activate plugin inside WordPress admin
* Create Carousels using the menu in the admin sidebar
* Use Carousel in your posts using shortcode `[wp_owl id="xxxx"]`


#### Disable asset loading
```php
add_filter( 'wp_owl_carousel_enqueue_assets', '__return_false' );
```


#### Filters

`wp_owl_carousel_enqueue_assets` - Toggle **all** Owl Carousel asset loading.

`wp_owl_carousel_enqueue_css` - Toggle Owl Carousel base css loading.

`wp_owl_carousel_enqueue_theme_css` - Toggle Owl Carousel theme css loading.

`wp_owl_carousel_enqueue_owl_js` - Toggle Owl Carousel js loading.

`wp_owl_carousel_enqueue_plugin_js` - Toggle Owl Carousel for WordPress js loading.
