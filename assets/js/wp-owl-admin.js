;(function() {
  tinymce.create('tinymce.plugins.wp_owl', {
    init: function(ed, url) {
      ed.addButton('wp_owl', {
        title: 'Insert Owl Carousel',
        //cmd: 'wp_owl_add_carousel',
        icon: 'dashicons dashicons-before dashicons-images-alt2',
        onclick: function() {
          tb_show('Insert Owl Carousel', 'admin-ajax.php?action=get_owl_carousels');
        }
      });

      // ed.addCommand('wp_owl_add_carousel', function() {
      //   ed.windowManager.open({
      //     title: 'Insert Owl Carousel',
      //     id: 'wp-owl-insert-carousel',
      //     buttons: [
      //       {
      //         text: 'Insert',
      //         id: 'wp-owl-insert-carousel-insert',
      //         class: 'insert',
      //         onclick: function(e) {
      //           console.log('yeehaw');
      //         }
      //       },
      //       {
      //         text: 'Cancel',
      //         id: 'wp-owl-insert-carousel-cancel',
      //         onclick: 'close'
      //       }
      //     ]
      //   });
      // });
    },
    createControl: function(n, cm) {
      return null;
    },
    getInfo: function() {
      return {
        longname: 'WP Owl Carousel',
        author: 'Aaron Kirkham',
        authorurl: 'https://kirkh.am',
        infourl: 'https://github.com/aaronkirkham/wp-owl-carousel',
        version: '2.1.0'
      };
    }
  });

  tinymce.PluginManager.add('wp_owl', tinymce.plugins.wp_owl);
})();