/**
 * Settings page: the color pickers. The empty value is a real choice (transparent, inherited), and
 * wpColorPicker's "Clear" button gives it.
 */
(function ($) {
  'use strict';

  $(function () {
    $('.ffsr-color-field').wpColorPicker();
  });
})(window.jQuery);
