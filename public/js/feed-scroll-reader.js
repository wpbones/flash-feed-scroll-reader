/**
 * Feed Scroll Reader: what CSS cannot do.
 *
 * 1. Set the animation's duration from the measured width of the track and the speed in pixels per
 *    second, so a long feed and a short one move at the same speed. The server prints an estimate
 *    for the case this script never runs. It measures again when the web fonts arrive and when the
 *    track or the box changes size, and changes the duration only when it is off by more than 10%,
 *    keeping the animation where it was, so nothing jumps.
 * 2. When a link takes the keyboard focus, stop the animation and let the visitor scroll the strip,
 *    so a focused link is never off screen. A mouse click is left alone: moving the strip under the
 *    pointer would lose the click.
 * 3. The pause button (WCAG 2.2.2), for touch screens, where there is no hover and no keyboard.
 *
 * Vanilla, no dependency. A ticker is any element with the data-ffsr attribute.
 */
(function () {
  'use strict';

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

  /** The running animation of the track, if the browser can tell. */
  function animationOf(track) {
    if (!track.getAnimations) {
      return null;
    }

    var all = track.getAnimations();

    for (var i = 0; i < all.length; i++) {
      if (/^ffsr-scroll/.test(all[i].animationName || '')) {
        return all[i];
      }
    }

    return null;
  }

  function setDuration(root) {
    var track = root.querySelector('.ffsr__track');
    var speed = parseFloat(root.getAttribute('data-speed'));

    if (!track || !(speed > 0)) {
      return;
    }

    // offsetWidth includes the padding: the viewport plus the items, the whole way the track moves.
    var seconds = track.offsetWidth / speed;
    var current = parseFloat(getComputedStyle(root).getPropertyValue('--ffsr-duration'));

    if (!(seconds > 0) || !isFinite(seconds)) {
      return;
    }

    // Close enough: leave the animation alone.
    if (current > 0 && Math.abs(seconds - current) / current <= 0.1) {
      return;
    }

    var animation = animationOf(track);
    var progress = null;

    if (animation && animation.currentTime !== null && current > 0) {
      progress = animation.currentTime / (current * 1000);
    }

    root.style.setProperty('--ffsr-duration', seconds.toFixed(2) + 's');

    // The new duration applies to the same animation: put it back where it was.
    if (animation && progress !== null) {
      animation.currentTime = progress * seconds * 1000;
    }
  }

  function keyboardFocus(root) {
    root.addEventListener('focusin', function (event) {
      var target = event.target;

      if (target && target.matches && target.matches('.ffsr__link:focus-visible')) {
        root.classList.add('ffsr--static');
      }
    });

    root.addEventListener('focusout', function (event) {
      if (!event.relatedTarget || !root.contains(event.relatedTarget)) {
        root.classList.remove('ffsr--static');
      }
    });
  }

  function toggle(root) {
    var button = root.querySelector('.ffsr__toggle');

    if (!button) {
      return;
    }

    button.hidden = false;
    button.addEventListener('click', function () {
      var paused = root.classList.toggle('ffsr--paused');
      button.setAttribute('aria-pressed', paused ? 'true' : 'false');
    });
  }

  function init(root) {
    if (root.getAttribute('data-ffsr-ready')) {
      return;
    }

    root.setAttribute('data-ffsr-ready', '1');

    // A still list: nothing to measure, nothing to pause.
    if (reduced && reduced.matches) {
      return;
    }

    var track = root.querySelector('.ffsr__track');
    var measure = function () {
      setDuration(root);
    };

    measure();
    keyboardFocus(root);
    toggle(root);

    // Web fonts change the width of the text after the first measure.
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(measure);
    }

    if (window.ResizeObserver) {
      var observer = new ResizeObserver(measure);

      observer.observe(root);

      if (track) {
        observer.observe(track);
      }
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
