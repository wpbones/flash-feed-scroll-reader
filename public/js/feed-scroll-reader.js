/**
 * Feed Scroll Reader: the two things CSS cannot do.
 *
 * 1. Set the animation's duration from the measured width of the track and the speed in pixels per
 *    second, so a long feed and a short one move at the same speed. The server prints an estimate
 *    for the case this script never runs.
 * 2. When a link takes the keyboard focus, stop the animation and let the visitor scroll the strip,
 *    so a focused link is never off screen. A mouse click is left alone: moving the strip under the
 *    pointer would lose the click.
 *
 * Vanilla, no dependency. A ticker is any element with the data-ffsr attribute.
 */
(function () {
  'use strict';

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

  function setDuration(root) {
    var track = root.querySelector('.ffsr__track');
    var speed = parseFloat(root.getAttribute('data-speed'));

    if (!track || !(speed > 0)) {
      return;
    }

    // offsetWidth includes the padding: the viewport plus the items, the whole way the track moves.
    var seconds = track.offsetWidth / speed;

    if (seconds > 0 && isFinite(seconds)) {
      root.style.setProperty('--ffsr-duration', seconds.toFixed(2) + 's');
    }
  }

  function keyboardFocus(root) {
    root.addEventListener('focusin', function (event) {
      var target = event.target;

      if (target && target.matches && target.matches(':focus-visible')) {
        root.classList.add('ffsr--static');
      }
    });

    root.addEventListener('focusout', function (event) {
      if (!event.relatedTarget || !root.contains(event.relatedTarget)) {
        root.classList.remove('ffsr--static');
      }
    });
  }

  function init(root) {
    if (root.getAttribute('data-ffsr-ready')) {
      return;
    }

    root.setAttribute('data-ffsr-ready', '1');

    if (reduced && reduced.matches) {
      return;
    }

    setDuration(root);
    keyboardFocus(root);

    if (window.ResizeObserver) {
      new ResizeObserver(function () {
        setDuration(root);
      }).observe(root);
    }
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-ffsr]'), init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
