/**
 * @file file_browser.view.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.FileBrowserView = {
    attach: function (context) {
      $('.view-content').imagesLoaded(function () {
        $('.view-content').masonry({
          itemSelector: '.grid-item',
          columnWidth: 350,
          gutter: 5
        });
      });

      $('.grid-item').once('bind-click-event').click(function () {
        var input = $(this).find('.views-field-entity-browser-select input');
        input.prop('checked', !input.prop('checked'));
        if (input.prop('checked')) {
          $(this).addClass('checked');
        }
        else {
          $(this).removeClass('checked');
        }
      });
    }
  };

}(jQuery, Drupal));