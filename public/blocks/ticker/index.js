/**
 * Feed Scroll Reader: the block.
 *
 * Hand-written, no build step: the editor's own globals (`wp.*`), listed in index.asset.php. The
 * preview is the server's own output (ServerSideRender), so what the editor shows is what the
 * shortcode prints. An empty setting is not saved, and the server falls back to the defaults of
 * Settings > Feed Scroll Reader, which the plugin hands over in `window.ffsrDefaults` to be shown as
 * placeholders. Colors, font size, margin and alignment are core block supports (see block.json).
 */
(function (wp) {
  'use strict';

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  // The text domain stays a literal in every call: the string extractor (wp i18n make-pot) cannot read a variable.
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var createBlock = wp.blocks.createBlock;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var components = wp.components;
  var ServerSideRender = wp.serverSideRender;

  var NAME = 'flash-feed-scroll-reader/ticker';
  var defaults = window.ffsrDefaults || {};

  /** A number from a shortcode attribute or a widget field, or undefined. */
  function toInt(value) {
    var number = parseInt(value, 10);

    return isNaN(number) ? undefined : number;
  }

  /** The shortcode's yes/no, and the widget's '1'/'0', as a boolean; undefined when it says nothing. */
  function toBoolean(value) {
    if (value === undefined || value === null || value === '') {
      return undefined;
    }

    return ['1', 'true', 'yes', 'on', 'y'].indexOf(String(value).toLowerCase()) !== -1;
  }

  /** A feed list separated by spaces or commas (the shortcode) as one address per line (the block). */
  function toLines(value) {
    var lines = String(value || '').split(/[\s,]+/).filter(Boolean);

    return lines.length ? lines.join('\n') : undefined;
  }

  /** Shortcode colors (#rgb, #rrggbb) as the block's own color support. */
  function toColors(named) {
    var hex = /^#[0-9a-f]{3,8}$/i;
    var color = {};

    if (hex.test(named.bgcolor || '')) {
      color.background = named.bgcolor;
    }

    if (hex.test(named.color || '')) {
      color.text = named.color;
    }

    return color.background || color.text ? { color: color } : undefined;
  }

  function named(attrs) {
    return (attrs && attrs.named) || {};
  }

  var transforms = {
    from: [
      {
        // [feed_scroll_reader feed="…" speed="60"] typed or pasted in a paragraph, or an existing
        // Shortcode block's text, becomes the block.
        type: 'shortcode',
        tag: 'feed_scroll_reader',
        attributes: {
          feeds: { type: 'string', shortcode: function (attrs) { return toLines(named(attrs).feed || named(attrs).feedurl); } },
          maxItems: { type: 'integer', shortcode: function (attrs) { return toInt(named(attrs).max_items); } },
          speed: { type: 'integer', shortcode: function (attrs) { return toInt(named(attrs).speed); } },
          description: { type: 'boolean', shortcode: function (attrs) { return toBoolean(named(attrs).description); } },
          descriptionLength: { type: 'integer', shortcode: function (attrs) { return toInt(named(attrs).description_length); } },
          height: { type: 'string', shortcode: function (attrs) { return named(attrs).height || undefined; } },
          target: { type: 'string', shortcode: function (attrs) { return named(attrs).target || undefined; } },
          label: { type: 'string', shortcode: function (attrs) { return named(attrs).label || undefined; } },
          style: { type: 'object', shortcode: function (attrs) { return toColors(named(attrs)); } }
        }
      },
      {
        // The classic widget, in the block-based Widgets screen: its title becomes a heading above.
        type: 'block',
        blocks: ['core/legacy-widget'],
        isMatch: function (attributes) {
          return attributes.idBase === 'feed_scroll_reader';
        },
        transform: function (attributes) {
          var raw = (attributes.instance && attributes.instance.raw) || {};
          var ticker = createBlock(NAME, {
            feeds: toLines(raw.feeds),
            maxItems: toInt(raw.max_items),
            speed: toInt(raw.speed),
            height: raw.height || undefined,
            description: toBoolean(raw.description)
          });

          return raw.title ? [createBlock('core/heading', { content: raw.title, level: 2 }), ticker] : ticker;
        }
      }
    ]
  };

  /** A range that can be emptied: empty means "the default". */
  function range(label, help, value, fallback, min, max, onChange) {
    return el(components.RangeControl, {
      __nextHasNoMarginBottom: true,
      __next40pxDefaultSize: true,
      label: label,
      help: help,
      value: value,
      initialPosition: fallback,
      min: min,
      max: max,
      allowReset: true,
      onChange: function (next) {
        onChange(next === null ? undefined : next);
      }
    });
  }

  function Edit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var blockProps = useBlockProps();
    var showDescription = attributes.description !== undefined ? attributes.description : !!Number(defaults.description);

    // An empty field is "not set": the server then uses the default.
    var text = function (key) {
      return function (value) {
        var next = {};
        next[key] = value === '' ? undefined : value;
        setAttributes(next);
      };
    };

    return el(
      Fragment,
      null,
      el(
        InspectorControls,
        null,
        el(
          components.PanelBody,
          { title: __('Feeds', 'flash-feed-scroll-reader') },
          el('p', null, __('Empty fields use the defaults of Settings > Feed Scroll Reader.', 'flash-feed-scroll-reader')),
          el(components.TextareaControl, {
            __nextHasNoMarginBottom: true,
            label: __('Feed addresses', 'flash-feed-scroll-reader'),
            help: __('One RSS or Atom address per line, up to 5. Several feeds are merged, newest first.', 'flash-feed-scroll-reader'),
            value: attributes.feeds || '',
            placeholder: defaults.feeds || '',
            rows: 4,
            onChange: text('feeds')
          }),
          range(
            __('Items', 'flash-feed-scroll-reader'),
            __('How many items the ticker shows in all, from 1 to 100.', 'flash-feed-scroll-reader'),
            attributes.maxItems,
            defaults.max_items,
            1,
            100,
            function (value) {
              setAttributes({ maxItems: value });
            }
          )
        ),
        el(
          components.PanelBody,
          { title: __('Ticker', 'flash-feed-scroll-reader'), initialOpen: false },
          range(__('Speed', 'flash-feed-scroll-reader'), __('pixels per second', 'flash-feed-scroll-reader'), attributes.speed, defaults.speed, 10, 400, function (value) {
            setAttributes({ speed: value });
          }),
          el(components.TextControl, {
            __nextHasNoMarginBottom: true,
            __next40pxDefaultSize: true,
            label: __('Height', 'flash-feed-scroll-reader'),
            help: __('A number with a unit (40px, 3em) or auto. A number alone is pixels.', 'flash-feed-scroll-reader'),
            value: attributes.height || '',
            placeholder: defaults.height || '',
            onChange: text('height')
          }),
          el(components.SelectControl, {
            __nextHasNoMarginBottom: true,
            __next40pxDefaultSize: true,
            label: __('Open links', 'flash-feed-scroll-reader'),
            value: attributes.target || '',
            options: [
              { label: __('Default', 'flash-feed-scroll-reader'), value: '' },
              { label: __('In a new tab', 'flash-feed-scroll-reader'), value: '_blank' },
              { label: __('In the same tab', 'flash-feed-scroll-reader'), value: '_self' }
            ],
            onChange: text('target')
          }),
          el(components.TextControl, {
            __nextHasNoMarginBottom: true,
            __next40pxDefaultSize: true,
            label: __('Label for screen readers', 'flash-feed-scroll-reader'),
            help: __('The name of the ticker for assistive technology.', 'flash-feed-scroll-reader'),
            value: attributes.label || '',
            placeholder: __('Latest items', 'flash-feed-scroll-reader'),
            onChange: text('label')
          })
        ),
        el(
          components.PanelBody,
          { title: __('Descriptions', 'flash-feed-scroll-reader'), initialOpen: false },
          el(components.ToggleControl, {
            __nextHasNoMarginBottom: true,
            label: __('Show the description after each title', 'flash-feed-scroll-reader'),
            checked: showDescription,
            onChange: function (value) {
              setAttributes({ description: value });
            }
          }),
          showDescription &&
            range(
              __('Description length (characters)', 'flash-feed-scroll-reader'),
              '',
              attributes.descriptionLength,
              defaults.description_length,
              10,
              500,
              function (value) {
                setAttributes({ descriptionLength: value });
              }
            )
        )
      ),
      // Disabled: a click selects the block instead of following a link of the preview.
      el(
        'div',
        blockProps,
        el(
          components.Disabled,
          null,
          el(ServerSideRender, {
            block: NAME,
            attributes: attributes,
            EmptyResponsePlaceholder: function () {
              return el('p', null, __('There is no ticker to show. Check the feed addresses.', 'flash-feed-scroll-reader'));
            }
          })
        )
      )
    );
  }

  registerBlockType(NAME, {
    edit: Edit,
    // Rendered by the server: nothing is saved in the post but the block's comment.
    save: function () {
      return null;
    },
    transforms: transforms
  });
})(window.wp);
