/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

/*
 * Mobile's Front-end Library
 */

(function($, window, document, undefined) {
    'use strict';

    var header_helpers = function(class_array) {
        var head = $('head');

        head.prepend($.map(class_array, function(class_name) {
            if (head.has('.' + class_name).length === 0) {
                return '<meta class="' + class_name + '" />';
            }
        }));
    };

    header_helpers([
        'mobile-mq-small',
        'mobile-mq-small-only',
        'mobile-mq-medium',
        'mobile-mq-medium-only',
        'mobile-data-attribute-namespace'
    ]);

    // Enable FastClick if present

    $(function() {
        if (typeof FastClick !== 'undefined') {
            // Don't attach to body if undefined
            if (typeof document.body !== 'undefined') {
                FastClick.attach(document.body);
            }
        }
    });

    // Namespace functions.

    var attr_name = function(init) {
        var arr = [];
        if (!init) {
            arr.push('data');
        }
        if (this.namespace.length > 0) {
            arr.push(this.namespace);
        }
        arr.push(this.name);

        return arr.join('-');
    };

    var add_namespace = function(str) {
        var parts = str.split('-'),
            i = parts.length,
            arr = [];

        while (i--) {
            if (i !== 0) {
                arr.push(parts[i]);
            } else {
                if (this.namespace.length > 0) {
                    arr.push(this.namespace, parts[i]);
                } else {
                    arr.push(parts[i]);
                }
            }
        }

        return arr.reverse().join('-');
    };

    // Event binding and data-options updating.

    var bindings = function(method, options) {
        var self = this,
            config = ($.isArray(options) ? options[0] : options) || method,
            bind = function() {
                var $this = $(this),
                    should_bind_events = !$(self).data(self.attr_name(true) + '-init');
                $this.data(self.attr_name(true) + '-init', $.extend(true, {}, self.settings, config, self.data_options($this)));

                if (should_bind_events) {
                    self.events(this);
                }
            };

        if ($(this.scope).is('[' + this.attr_name() + ']')) {
            bind.call(this.scope);
        } else {
            $('[' + this.attr_name() + ']', this.scope).each(bind);
        }
        // # Patch to fix #5043 to move this *after* the if/else clause in order for Backbone and similar frameworks to have improved control over event binding and data-options updating.
        if (typeof method === 'string') {
            if($.isArray(options)) return this[method].apply(this, options);
            else return this[method].call(this, options);
        }

    };

    var single_image_loaded = function(image, callback) {
        function loaded() {
            callback(image[0]);
        }

        function bindLoad() {
            this.one('load', loaded);
        }

        if (!image.attr('src')) {
            loaded();
            return;
        }

        if (image[0].complete || image[0].readyState === 4) {
            loaded();
        } else {
            bindLoad.call(image);
        }
    };

    /*
     * jquery.requestAnimationFrame
     * https://github.com/gnarf37/jquery-requestAnimationFrame
     * Requires jQuery 1.8+
     *
     * Copyright (c) 2012 Corey Frang
     * Licensed under the MIT license.
     */

    (function(jQuery) {


        // requestAnimationFrame polyfill adapted from Erik Möller
        // fixes from Paul Irish and Tino Zijdel
        // http://paulirish.com/2011/requestanimationframe-for-smart-animating/
        // http://my.opera.com/emoller/blog/2011/12/20/requestanimationframe-for-smart-er-animating

        var animating,
            lastTime = 0,
            vendors = ['webkit'],
            requestAnimationFrame = window.requestAnimationFrame,
            cancelAnimationFrame = window.cancelAnimationFrame,
            jqueryFxAvailable = 'undefined' !== typeof jQuery.fx;

        for (; lastTime < vendors.length && !requestAnimationFrame; lastTime++) {
            requestAnimationFrame = window[vendors[lastTime] + 'RequestAnimationFrame'];
            cancelAnimationFrame = cancelAnimationFrame ||
                window[vendors[lastTime] + 'CancelAnimationFrame'] ||
                window[vendors[lastTime] + 'CancelRequestAnimationFrame'];
        }

        function raf() {
            if (animating) {
                requestAnimationFrame(raf);

                if (jqueryFxAvailable) {
                    jQuery.fx.tick();
                }
            }
        }

        if (requestAnimationFrame) {
            // use rAF
            window.requestAnimationFrame = requestAnimationFrame;
            window.cancelAnimationFrame = cancelAnimationFrame;

            if (jqueryFxAvailable) {
                jQuery.fx.timer = function(timer) {
                    if (timer() && jQuery.timers.push(timer) && !animating) {
                        animating = true;
                        raf();
                    }
                };

                jQuery.fx.stop = function() {
                    animating = false;
                };
            }
        } else {
            // polyfill
            window.requestAnimationFrame = function(callback) {
                var currTime = new Date().getTime(),
                    timeToCall = Math.max(0, 16 - (currTime - lastTime)),
                    id = window.setTimeout(function() {
                        callback(currTime + timeToCall);
                    }, timeToCall);
                lastTime = currTime + timeToCall;
                return id;
            };

            window.cancelAnimationFrame = function(id) {
                clearTimeout(id);
            };

        }

    }($));

    function removeQuotes(string) {
        if (typeof string === 'string' || string instanceof String) {
            string = string.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g, '');
        }

        return string;
    }

    function MediaQuery(selector) {
        this.selector = selector;
        this.query = '';
    }

    MediaQuery.prototype.toString = function() {
　　　　　return this.query = this.query || $(this.selector).css('font-family').replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, '');
    };

    window.Mobile = {
        name: 'Mobile',

        media_queries: {
            'small': new MediaQuery('.mobile-mq-small'),
            'small-only': new MediaQuery('.mobile-mq-small-only'),
            'medium': new MediaQuery('.mobile-mq-medium'),
            'medium-only': new MediaQuery('.mobile-mq-medium-only')
        },

        stylesheet: $('<style></style>').appendTo('head')[0].sheet,

        global: {
            namespace: undefined
        },

        init: function(scope, libraries, method, options, response) {
            var args = [scope, method, options, response],
                responses = [];

            // set mobile global scope
            this.scope = scope || this.scope;

            this.set_namespace();

            if (libraries && typeof libraries === 'string' && !/reflow/i.test(libraries)) {
                if (this.libs.hasOwnProperty(libraries)) {
                    responses.push(this.init_lib(libraries, args));
                }
            } else {
                for (var lib in this.libs) {
                    responses.push(this.init_lib(lib, libraries));
                }
            }

            // $(window).load(function() {
            //     $(window)
            //         .trigger('resize.imagesbox')
            //         .trigger('resize.dropdown')
            //         .trigger('resize.equalizer')
            //         .trigger('resize.responsive')
            //         .trigger('resize.topbar')
            //         .trigger('resize.slides');
            // });

            return scope;
        },

        init_lib: function(lib, args) {
            if (this.libs.hasOwnProperty(lib)) {
                this.patch(this.libs[lib]);

                if (args && args.hasOwnProperty(lib)) {
                    if (typeof this.libs[lib].settings !== 'undefined') {
                        $.extend(true, this.libs[lib].settings, args[lib]);
                    } else if (typeof this.libs[lib].defaults !== 'undefined') {
                        $.extend(true, this.libs[lib].defaults, args[lib]);
                    }
                    return this.libs[lib].init.apply(this.libs[lib], [this.scope, args[lib]]);
                }

                args = args instanceof Array ? args : new Array(args);
                return this.libs[lib].init.apply(this.libs[lib], args);
            }

            return function() {};
        },

        patch: function(lib) {
            lib.scope = this.scope;
            lib.namespace = this.global.namespace;
            lib['data_options'] = this.utils.data_options;
            lib['attr_name'] = attr_name;
            lib['add_namespace'] = add_namespace;
            lib['bindings'] = bindings;
        },

        inherit: function(scope, methods) {
            var methods_arr = methods.split(' '),
                i = methods_arr.length;

            while (i--) {
                if (this.utils.hasOwnProperty(methods_arr[i])) {
                    scope[methods_arr[i]] = this.utils[methods_arr[i]];
                }
            }
        },

        set_namespace: function() {

            // Description:
            //    Don't bother reading the namespace out of the meta tag
            //    if the namespace has been set globally in javascript
            //
            // Example:
            //    Mobile.global.namespace = 'my-namespace';
            // or make it an empty string:
            //    Mobile.global.namespace = '';
            //
            //

            // If the namespace has not been set (is undefined), try to read it out of the meta element.
            // Otherwise use the globally defined namespace, even if it's empty ('')
            var namespace = (this.global.namespace === undefined) ? $('.mobile-data-attribute-namespace').css('font-family') : this.global.namespace;

            // Finally, if the namsepace is either undefined or false, set it to an empty string.
            // Otherwise use the namespace value.
            this.global.namespace = (namespace === undefined || /false/i.test(namespace)) ? '' : namespace;
        },

        libs: {},

        // methods that can be inherited in libraries
        utils: {

            // Description:
            //    Parses data-options attribute
            //
            // Arguments:
            //    El (jQuery Object): Element to be parsed.
            //
            // Returns:
            //    Options (Javascript Object): Contents of the element's data-options
            //    attribute.
            data_options: function(el, data_attr_name) {
                data_attr_name = data_attr_name || 'options';
                var opts = {},
                    ii, p, opts_arr,
                    data_options = function(el) {
                        var namespace = Mobile.global.namespace;

                        if (namespace.length > 0) {
                            return el.data(namespace + '-' + data_attr_name);
                        }

                        return el.data(data_attr_name);
                    };

                var cached_options = data_options(el);

                if (typeof cached_options === 'object') {
                    return cached_options;
                }

                opts_arr = (cached_options || ':').split(';');
                ii = opts_arr.length;

                function isNumber(o) {
                    return !isNaN(o - 0) && o !== null && o !== '' && o !== false && o !== true;
                }

                function trim(str) {
                    if (typeof str === 'string') {
                        return $.trim(str);
                    }
                    return str;
                }

                while (ii--) {
                    p = opts_arr[ii].split(':');
                    p = [p[0], p.slice(1).join(':')];

                    if (/true/i.test(p[1])) {
                        p[1] = true;
                    }
                    if (/false/i.test(p[1])) {
                        p[1] = false;
                    }
                    if (isNumber(p[1])) {
                        if (p[1].indexOf('.') === -1) {
                            p[1] = parseInt(p[1], 10);
                        } else {
                            p[1] = parseFloat(p[1]);
                        }
                    }

                    if (p.length === 2 && p[0].length > 0) {
                        opts[trim(p[0])] = trim(p[1]);
                    }
                }

                return opts;
            },

            // Description:
            //    Adds JS-recognizable media queries
            //
            // Arguments:
            //    Media (String): Key string for the media query to be stored as in
            //    Mobile.media_queries
            //
            //    Class (String): Class name for the generated <meta> tag
            register_media: function(media, media_class) {
                if (Mobile.media_queries[media] === undefined) {
                    $('head').append('<meta class="' + media_class + '"/>');
                    Mobile.media_queries[media] = removeQuotes($('.' + media_class).css('font-family'));
                }
            },

            // Description:
            //    Add custom CSS within a JS-defined media query
            //
            // Arguments:
            //    Rule (String): CSS rule to be appended to the document.
            //
            //    Media (String): Optional media query string for the CSS rule to be
            //    nested under.
            add_custom_rule: function(rule, media) {
                if (media === undefined && Mobile.stylesheet) {
                    Mobile.stylesheet.insertRule(rule, Mobile.stylesheet.cssRules.length);
                } else {
                    var query = Mobile.media_queries[media];

                    if (query !== undefined) {
                        Mobile.stylesheet.insertRule('@media ' +
                            Mobile.media_queries[media] + '{ ' + rule + ' }', Mobile.stylesheet.cssRules.length);
                    }
                }
            },

            // Description:
            //    Performs a callback function when an image is fully loaded
            //
            // Arguments:
            //    Image (jQuery Object): Image(s) to check if loaded.
            //
            //    Callback (Function): Function to execute when image is fully loaded.
            image_loaded: function(images, callback) {
                var self = this,
                    unloaded = images.length;

                function pictures_has_height(images) {
                    var pictures_number = images.length;

                    for (var i = pictures_number - 1; i >= 0; i--) {
                        if (images.attr('height') === undefined) {
                            return false;
                        };
                    };

                    return true;
                }

                if (unloaded === 0 || pictures_has_height(images)) {
                    callback(images);
                }

                images.each(function() {
                    single_image_loaded($(this), function() {
                        unloaded -= 1;
                        if (unloaded === 0) {
                            callback(images);
                        }
                    });
                });
            },

            // Description:
            //    Returns a random, alphanumeric string
            //
            // Arguments:
            //    Length (Integer): Length of string to be generated. Defaults to random
            //    integer.
            //
            // Returns:
            //    Rand (String): Pseudo-random, alphanumeric string.
            random_str: function() {
                if (!this.fidx) {
                    this.fidx = 0;
                }
                this.prefix = this.prefix || [(this.name || 'M'), (+new Date).toString(36)].join('-');

                return this.prefix + (this.fidx++).toString(36);
            },

            // Description:
            //    Helper for window.matchMedia
            //
            // Arguments:
            //    mq (String): Media query
            //
            // Returns:
            //    (Boolean): Whether the media query passes or not
            match: function(mq) {
                return window.matchMedia(mq).matches;
            },

            // Description:
            //    Helpers for checking Mobile default media queries with JS
            //
            // Returns:
            //    (Boolean): Whether the media query passes or not

            is_small_up: function() {
                return this.match(Mobile.media_queries.small);
            },

            is_medium_up: function() {
                return this.match(Mobile.media_queries.medium);
            },

            is_small_only: function() {
                return !this.is_medium_up();
            },

            is_medium_only: function() {
                return this.is_medium_up();
            }
        }
    };

    $.fn.mobile = function() {
        var args = Array.prototype.slice.call(arguments, 0);

        return this.each(function() {
            Mobile.init.apply(Mobile, [this].concat(args));
            return this;
        });
    };

}(jQuery, window, window.document));

(function($, window, document, undefined) {
    'use strict';

    Mobile.libs.alert = {
        name: 'alert',

        settings: {
            callback: function() {}
        },

        init: function(scope, method, options) {
            this.bindings(method, options);
        },

        events: function() {
            var self = this;

            $(this.scope).off('.alert').on('click.alert', '[' + this.attr_name() + '] .close', function(e) {
                var alertBox = $(this).closest('[' + self.attr_name() + ']'),
                    settings = alertBox.data(self.attr_name(true) + '-init') || self.settings;

                e.preventDefault();
                alertBox.addClass('alert-close');
                alertBox.on('transitionend webkitTransitionEnd', function(e) {
                    $(this).trigger('close.alert').remove();
                    settings.callback();
                });
            });
        },

        reflow: function() {}
    };
}(jQuery, window, window.document));

(function($, window, document, undefined) {
    'use strict';

    var openModals = [];

    Mobile.libs.modal = {
        name: 'modal',

        locked: false,

        settings: {
            animation: 'fade',
            animation_speed: 250,
            close_on_backdrop_click: true,
            close_on_esc: true,
            close_modal_class: 'close-modal',
            multiple_opened: false,
            backdrop: true,
            backdrop_class: 'backdrop',
            root_element: 'body',
            no_scroll: true,
            preventTargetDefault: true,
            open: $.noop,
            opened: $.noop,
            close: $.noop,
            closed: $.noop,
            on_ajax_error: $.noop,
            css: {
                open: {
                    'opacity': 0,
                    'visibility': 'visible'
                },
                close: {
                    'opacity': 1,
                    'visibility': 'hidden'
                }
            }
        },

        init: function(scope, method, options) {
            $.extend(true, this.settings, method, options);
            this.bindings(method, options);
        },

        events: function(scope) {
            var self = this;

            $(this.scope)
                .off('.modal')
                .on('click.modal', '[' + this.add_namespace('data-modal-id') + ']:not(:disabled):not(.disabled)', function(e) {
                    if(self.settings.preventTargetDefault) e.preventDefault();

                    if (!self.locked) {
                        var element = $(this),
                            ajax = element.data('modal-ajax'),
                            replaceContentSel = element.data('modal-replace-content');

                        self.locked = true;

                        if (typeof ajax === 'undefined') {
                            self.open.call(self, element);
                        } else {
                            var url = ajax === true ? element.attr('href') : ajax;
                            self.open.call(self, element, {
                                url: url
                            }, {
                                replaceContentSel: replaceContentSel
                            });
                        }
                    }
                });

            $(document)
                .on('click.modal', this.close_targets(), function(e) {
                    if (self.settings.preventTargetDefault) e.preventDefault();
                    if (!self.locked) {
                        var settings = $('[' + self.attr_name() + '].open').data(self.attr_name(true) + '-init') || self.settings,
                            backdrop_clicked = settings.backdrop && ($(e.target)[0] === $('.' + settings.backdrop_class)[0]);

                        if (backdrop_clicked) {
                            if (settings.close_on_backdrop_click) {
                                e.stopPropagation();
                            } else {
                                return;
                            }
                        }

                        self.locked = true;
                        self.close.call(self, backdrop_clicked ? $('[' + self.attr_name() + '].open:not(.toback)') : $(this).closest('[' + self.attr_name() + ']'));
                    }
                });

            if ($('[' + this.attr_name() + ']', this.scope).length > 0) {
                $(this.scope)
                    // .off('.modal')
                    .on('open.modal', this.settings.open)
                    .on('opened.modal', this.settings.opened)
                    .on('opened.modal', this.open_video)
                    .on('close.modal', this.settings.close)
                    .on('closed.modal', this.settings.closed)
                    .on('closed.modal', this.close_video);
            } else {
                $(this.scope)
                    // .off('.modal')
                    .on('open.modal', '[' + this.attr_name() + ']', this.settings.open)
                    .on('opened.modal', '[' + this.attr_name() + ']', this.settings.opened)
                    .on('opened.modal', '[' + this.attr_name() + ']', this.open_video)
                    .on('close.modal', '[' + this.attr_name() + ']', this.settings.close)
                    .on('closed.modal', '[' + this.attr_name() + ']', this.settings.closed)
                    .on('closed.modal', '[' + this.attr_name() + ']', this.close_video);
            }

            return true;
        },

        open: function(target, ajax_settings) {
            var self = this,
                modal;

            if (target) {
                if (typeof target.selector !== 'undefined') {
                    // Find the named node; only use the first one found, since the rest of the code assumes there's only one node
                    modal = $('#' + target.data('modal-id')).first();
                } else {
                    modal = $(this.scope);

                    ajax_settings = target;
                }
            } else {
                modal = $(this.scope);
            }

            var settings = modal.data(this.attr_name(true) + '-init');
            settings = settings || this.settings;

            if (modal.hasClass('open') && target !== undefined && target.attr('data-modal-id') == modal.attr('id')) {
                return this.close(modal);
            }

            if (!modal.hasClass('open')) {
                var open_modal = $('[' + this.attr_name() + '].open');

                modal.attr('tabindex', '0').attr('aria-hidden', 'false');

                // prevents annoying scroll positioning bug with position: absolute;
                if (settings.no_scroll) {
                    var $doc = $('html');
                    $doc.one('open.modal', function() {
                        $(this).addClass('modal-open');
                    }).on('touchmove', function(e) {
                        e.preventDefault();
                    });
                }

                // Prevent namespace event from triggering twice
                modal.on('open.modal', function(e) {
                    if (e.namespace !== 'modal') return;
                });

                modal.on('open.modal').trigger('open.modal');

                if (open_modal.length < 1) {
                    this.toggle_backdrop(modal, true);
                }

                if (typeof ajax_settings === 'string') {
                    ajax_settings = {
                        url: ajax_settings
                    };
                }

                var openModal = function() {
                    if (open_modal.length > 0) {
                        if (settings.multiple_opened) {
                            self.to_back(open_modal);
                        } else {
                            self.hide(open_modal, settings.css.close);
                        }
                    }

                    // bl: add the open_modal that isn't already in the background to the openModals array
                    if (settings.multiple_opened) {
                        openModals.push(modal);
                    }

                    self.show(modal, settings.css.open);
                };

                if (typeof ajax_settings === 'undefined' || !ajax_settings.url) {
                    openModal();
                } else {
                    var old_success = typeof ajax_settings.success !== 'undefined' ? ajax_settings.success : null;
                    $.extend(ajax_settings, {
                        success: function(data, textStatus, jqXHR) {
                            if ($.isFunction(old_success)) {
                                var result = old_success(data, textStatus, jqXHR);
                                if (typeof result == 'string') {
                                    data = result;
                                }
                            }

                            if (typeof options !== 'undefined' && typeof options.replaceContentSel !== 'undefined') {
                                modal.find(options.replaceContentSel).html(data);
                            } else {
                                modal.html(data);
                            }

                            $(modal).mobile('section', 'reflow');
                            $(modal).children().mobile();

                            openModal();
                        }
                    });

                    // check for if user initalized with error callback
                    if (settings.on_ajax_error !== $.noop) {
                        $.extend(ajax_settings, {
                            error: settings.on_ajax_error
                        });
                    }

                    $.ajax(ajax_settings);
                }
            }
            $(window).trigger('resize');
        },

        close: function(modal) {
            var modal = modal && modal.length ? modal : $(this.scope),
                open_modals = $('[' + this.attr_name() + '].open'),
                settings = modal.data(this.attr_name(true) + '-init') || this.settings,
                self = this;

            if (open_modals.length > 0) {

                modal.removeAttr('tabindex', '0').attr('aria-hidden', 'true');

                // prevents annoying scroll positioning bug with position: absolute;
                if (settings.no_scroll) {
                    var $doc = $('html');
                    $doc.one('close.modal', function() {
                        $(this).removeClass('modal-open');
                    })
                    .off('touchmove');
                }

                this.locked = true;

                modal.trigger('close.modal');

                if ((settings.multiple_opened && open_modals.length === 1) || !settings.multiple_opened || modal.length > 1) {
                    this.toggle_backdrop(modal, false);
                    this.to_front(modal);
                }

                if (settings.multiple_opened) {
                    var isCurrent = modal.is(':not(.toback)');
                    this.hide(modal, settings.css.close, settings);
                    if (isCurrent) {
                        // remove the last modal since it is now closed
                        openModals.pop();
                    } else {
                        // if this isn't the current modal, then find it in the array and remove it
                        openModals = $.grep(openModals, function(elt) {
                            var isThis = elt[0] === modal[0];
                            if (isThis) {
                                // since it's not currently in the front, put it in the front now that it is hidden
                                // so that if it's re-opened, it won't be .toback
                                self.to_front(modal);
                            }
                            return !isThis;
                        });
                    }
                    // finally, show the next modal in the stack, if there is one
                    if (openModals.length > 0) {
                        this.to_front(openModals[openModals.length - 1]);
                    }
                } else {
                    this.hide(open_modals, settings.css.close, settings);
                }
            }
        },

        close_targets: function() {
            var base = '.' + this.settings.close_modal_class;

            if (this.settings.backdrop && this.settings.close_on_backdrop_click) {
                return base + ', .' + this.settings.backdrop_class;
            }

            return base;
        },

        toggle_backdrop: function(modal, state) {
            if (!this.settings.backdrop) return;
            if ($('.' + this.settings.backdrop_class).length === 0) {
                this.settings.backdrop = $('<div />', {
                        'class': this.settings.backdrop_class
                    })
                    .appendTo('body').hide();
            }

            var visible = this.settings.backdrop.filter(':visible').length > 0;
            if (state != visible) {
                if (state == undefined ? visible : !state) {
                    this.hide(this.settings.backdrop);
                } else {
                    this.show(this.settings.backdrop);
                }
            }
        },

        show: function(el, css) {
            // is modal
            if (css) {
                var settings = el.data(this.attr_name(true) + '-init') || this.settings,
                    root_element = settings.root_element,
                    context = this;

                if (el.parent(root_element).length === 0) {
                    var placeholder = el.wrap('<div style="display: none;" />').parent();

                    el.on('closed.modal.wrapped', function() {
                        el.detach().appendTo(placeholder);
                        el.unwrap().unbind('closed.modal.wrapped');
                    });

                    el.detach().appendTo(root_element);
                }

                var animData = getAnimationData(settings.animation);
                if (!animData.animate) {
                    this.locked = false;
                }

                if (animData.fade) {
                    var end_css = {
                        opacity: 1
                    };

                    return requestAnimationFrame(function() {
                        return el
                            .css(css)
                            .animate(end_css, settings.animation_speed, 'linear', function() {
                                context.locked = false;
                                el.trigger('opened.modal');
                            })
                            .addClass('open')
                            .trigger('open.modal');
                    });
                }

                return el.css(css)
                    .addClass('open')
                    .trigger('opened.modal');
            }

            var settings = this.settings;

            // should we animate the background?
            if (getAnimationData(settings.animation).fade) {
                return el.fadeIn(settings.animation_speed);
            }

            this.locked = false;

            return el.show();
        },

        to_back: function(el) {
            el.addClass('toback');
        },

        to_front: function(el) {
            el.removeClass('toback');
        },

        hide: function(el, css) {
            // is modal
            if (css) {
                var settings = el.data(this.attr_name(true) + '-init'),
                    context = this;
                settings = settings || this.settings;

                var animData = getAnimationData(settings.animation);
                if (!animData.animate) {
                    this.locked = false;
                }

                if (animData.fade) {
                    var end_css = {
                        opacity: 0
                    };

                    return requestAnimationFrame(function() {
                        return el
                            .animate(end_css, settings.animation_speed, 'linear', function() {
                                context.locked = false;
                                el.css(css).trigger('closed.modal');
                            })
                            .removeClass('open');
                    });
                }

                return el.css(css).removeClass('open').trigger('closed.modal');
            }

            var settings = this.settings;

            // should we animate the background?
            if (getAnimationData(settings.animation).fade) {
                return el.fadeOut(settings.animation_speed);
            }

            return el.hide();
        },

        close_video: function(e) {
            var video = $('.flex-video', e.target),
                iframe = $('iframe', video);

            if (iframe.length > 0) {
                iframe.attr('data-src', iframe[0].src);
                iframe.attr('src', iframe.attr('src'));
                video.hide();
            }
        },

        open_video: function(e) {
            var video = $('.flex-video', e.target),
                iframe = video.find('iframe');

            if (iframe.length > 0) {
                var data_src = iframe.attr('data-src');
                if (typeof data_src === 'string') {
                    iframe[0].src = iframe.attr('data-src');
                } else {
                    var src = iframe[0].src;
                    iframe[0].src = undefined;
                    iframe[0].src = src;
                }
                video.show();
            }
        },

        off: function() {
            $(this.scope).off('.modal');
        },

        reflow: function() {}
    };

    /*
     * getAnimationData('fade')       // {animate: true,  fade: true}
     * getAnimationData(null)         // {animate: false, fade: false}
     */
    function getAnimationData(str) {
        var fade = /fade/i.test(str);
        return {
            animate: fade,
            fade: fade
        };
    }
}(jQuery, window, window.document));

(function($, window, document, undefined) {
    'use strict';

    Mobile.libs.tab = {
        name: 'tab',

        settings: {
            active_class: 'active',
            content_class: 'tabs-content',
            panel_class: 'content',
            callback: function() {},
            deep_linking: false,
            scroll_to_content: true
        },

        default_tab_hashes: [],

        init: function(scope, method, options) {
            var self = this;

            // Store the default active tabs which will be referenced when the
            // location hash is absent, as in the case of navigating the tabs and
            // returning to the first viewing via the browser Back button.
            $('[' + this.attr_name() + '] > .active > a', this.scope).each(function() {
                self.default_tab_hashes.push(this.hash);
            });

            // store the initial href, which is used to allow correct behaviour of the
            // browser back button when deep linking is turned on.
            this.entry_location = window.location.href;

            this.bindings(method, options);
            this.handle_location_hash_change();
        },

        events: function() {
            var self = this;

            var usual_tab_behavior = function(e, target) {
                var settings = $(target).closest('[' + self.attr_name() + ']').data(self.attr_name(true) + '-init');
                if ('ontouchstart' in document) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.toggle_active_tab($(target).parent());
                }
            };

            $(this.scope)
                .off('.tab')
                // Click event: tab title
                .on('click.tab', '[' + this.attr_name() + '] > * > a', function(e) {
                    var el = this;
                    usual_tab_behavior(e, el);
                });

            // Location hash change event
            $(window).on('hashchange.tab', function(e) {
                e.preventDefault();
                self.handle_location_hash_change();
            });
        },

        handle_location_hash_change: function() {

            var self = this;

            $('[' + this.attr_name() + ']', this.scope).each(function() {
                var settings = $(this).data(self.attr_name(true) + '-init');
                if (settings.deep_linking) {
                    // Match the location hash to a label
                    var hash;
                    if (settings.scroll_to_content) {
                        hash = self.scope.location.hash;
                    } else {
                        // prefix the hash to prevent anchor scrolling
                        hash = self.scope.location.hash.replace('mob-', '');
                    }
                    if (hash != '') {
                        // Check whether the location hash references a tab content div or
                        // another element on the page (inside or outside the tab content div)
                        var hash_element = $(hash);
                        if (hash_element.hasClass(settings.panel_class) && hash_element.parent().hasClass(settings.content_class)) {
                            // Tab content div
                            self.toggle_active_tab($('[' + self.attr_name() + '] > * > a[href=' + hash + ']').parent());
                        } else {
                            // Not the tab content div. If inside the tab content, find the
                            // containing tab and toggle it as active.
                            var hash_tab_container_id = hash_element.closest('.' + settings.panel_class).attr('id');
                            if (hash_tab_container_id != undefined) {
                                self.toggle_active_tab($('[' + self.attr_name() + '] > * > a[href=#' + hash_tab_container_id + ']').parent(), hash);
                            }
                        }
                    } else {
                        // Reference the default tab hashes which were initialized in the init function
                        for (var ind = 0; ind < self.default_tab_hashes.length; ind++) {
                            self.toggle_active_tab($('[' + self.attr_name() + '] > * > a[href=' + self.default_tab_hashes[ind] + ']').parent());
                        }
                    }
                }
            });
        },

        toggle_active_tab: function(tab, location_hash) {
            var self = this,
                tabs = tab.closest('[' + this.attr_name() + ']'),
                tab_link = tab.find('a'),
                anchor = tab.children('a').first(),
                target_hash = '#' + anchor.attr('href').split('#')[1],
                target = $(target_hash),
                siblings = tab.siblings(),
                settings = tabs.data(this.attr_name(true) + '-init'),
                go_to_hash = function(hash) {
                    // This function allows correct behaviour of the browser's back button when deep linking is enabled. Without it
                    // the user would get continually redirected to the default hash.
                    var is_entry_location = window.location.href === self.entry_location,
                        default_hash = settings.scroll_to_content ? self.default_tab_hashes[0] : is_entry_location ? window.location.hash : 'mob-' + self.default_tab_hashes[0].replace('#', '')

                    if (!(is_entry_location && hash === default_hash)) {
                        window.location.hash = hash;
                    }
                };

            // allow usage of data-tab-content attribute instead of href
            if (anchor.data('tab-content')) {
                target_hash = '#' + anchor.data('tab-content').split('#')[1];
                target = $(target_hash);
            }

            if (settings.deep_linking) {

                if (settings.scroll_to_content) {

                    // retain current hash to scroll to content
                    go_to_hash(location_hash || target_hash);

                    if (location_hash == undefined || location_hash == target_hash) {
                        tab.parent()[0].scrollIntoView();
                    } else {
                        $(target_hash)[0].scrollIntoView();
                    }
                } else {
                    // prefix the hashes so that the browser doesn't scroll down
                    if (location_hash != undefined) {
                        go_to_hash('mob-' + location_hash.replace('#', ''));
                    } else {
                        go_to_hash('mob-' + target_hash.replace('#', ''));
                    }
                }
            }

            // WARNING: The activation and deactivation of the tab content must
            // occur after the deep linking in order to properly refresh the browser window.
            // Clean up multiple attr instances to done once
            tab.addClass(settings.active_class).triggerHandler('opened');
            tab_link.attr('aria-selected', 'true');
            siblings.removeClass(settings.active_class);
            siblings.find('a').attr('aria-selected', 'false');
            target.siblings().removeClass(settings.active_class).attr('aria-hidden', 'true');
            target.addClass(settings.active_class).attr('aria-hidden', 'false');
            settings.callback(tab);
            target.triggerHandler('toggled', [target]);
            tabs.triggerHandler('toggled', [tab]);
        },

        off: function() {},

        reflow: function() {}
    };
}(jQuery, window, window.document));

(function($, window, document, undefined) {
    'use strict';

    Mobile.libs.tips = {
        name: 'tips',

        settings: {
            // to display the massage text and so on
            content: '',
            // disappear after how many milliseconds.
            delay: 3000,
            // controll the tips' style.
            type: 'pop',
            // show on init
            show: false,
            // extra class
            class: '',
            // relative positioning of element.
            relativeTo: document.body,
            // background overlay element.
            has_overlay: false,
            overlay_class: 'overlay'
        },

        effect: {
            pop: {
                in: 'slide-in-down',
                out: 'slide-out-up'
            },
            msg: {
                in: 'fade-in',
                out: 'fade-out'
            },
            slide: {
                in: 'slide-in-up',
                out: 'slide-out-down'
            },
            overlay: {
                in: 'fade-in',
                out: 'fade-out'
            }
        },

        init: function(scope, method, options) {
            this.bindings(method, options);
            this.settings.show && this.show();
        },

        events: function() {
            var self = this;
            var attr = this.attr_name();
            $(this.scope)
                .on('click.tips', '[' + attr + ']', function(e) {
                    e.preventDefault();

                    var content = $(this).attr(attr + '-content');
                    var type = $(this).attr(attr);

                    self.show(content, type);
                });
        },

        create: function(content, type) {
            var tipTpl = '<div class="' + type + 'tip ' + this.settings.class + '">' +
                '<div class="content animated">' +
                content +
                '</div>' +
            '</div>';
            this.element = $(tipTpl).appendTo(document.body);

            if(this.settings.has_overlay) {
                var overlayTpl = '<div class="' + this.settings.overlay_class + ' animated"></div>';
                this.overlay = $(overlayTpl).appendTo(document.body);
            }
        },

        show: function(content, type) {
            content = content || this.settings.content;
            type = type || this.settings.type;

            clearTimeout(this.timer);
            if(!this.element) this.create(content, type);
            else {
                this.element
                    .off('animationend webkitAnimationEnd')
                    .find('.content')
                        .html(content);

                if(this.overlay) this.overlay.off('animationend webkitAnimationEnd');
            }

            if(type === 'pop' && this.settings.relativeTo) {
                this.element.css('top', $(this.settings.relativeTo).offset().top);
            }

            type = this.effect[type];
            this.element
                .show()
                .trigger('show.tips')
                .find('.content')
                    .removeClass(type.out)
                    .addClass(type.in);

            if(this.overlay) {
                this.overlay
                    .show()
                    .trigger('show.overlay')
                    .removeClass(this.effect.overlay.out)
                    .addClass(this.effect.overlay.in)
            }

            if (this.settings.delay) {
                this.timer = setTimeout($.proxy(function() {
                    this.hide(type);
                }, this), this.settings.delay);
            }
        },

        hide: function(type) {
            var self = this;
            type = type || this.effect[this.settings.type];

            this.element
                .trigger('hide.tips')
                .find('.content')
                    .removeClass(type.in)
                    .addClass(type.out)
                    .one('animationend webkitAnimationEnd', function() {
                        $(this).parent().remove();
                        self.element = null;
                    });
            if(this.overlay) {
                this.overlay
                    .trigger('hide.overlay')
                    .removeClass(this.effect.overlay.in)
                    .addClass(this.effect.overlay.out)
                    .one('animationend webkitAnimationEnd', function() {
                        $(this).remove();
                        self.overlay = null;
                    });
            }
        },

        reflow: function() {}
    };
})(jQuery, window, window.document);

(function($, window, document, undefined) {
    'use strict';

    Mobile.libs.topbar = {
        name: 'topbar',

        settings: {
            sticky_class: 'sticky',
            start_offset: 0,
            is_hover: true
        },

        init: function(section, method, options) {
            Mobile.inherit(this, 'add_custom_rule');
            var self = this;

            this.bindings(method, options);

            $('[' + this.attr_name() + ']', this.scope).each(function() {
                var topbar = $(this),
                    topbarContainer = topbar.parent(),
                    maxHeight = Math.max(topbarContainer.outerHeight(), topbar.outerHeight()),
                    settings = topbar.data(self.attr_name(true) + '-init');
                if (topbarContainer.hasClass('fixed')) {
                    if (topbarContainer.hasClass('bottom')) {
                        $('body').css('padding-bottom', maxHeight);
                    }
                    else {
                        $('body').css('padding-top', maxHeight);
                    }
                    return;
                }

                if (self.stickable(topbarContainer, settings)) {
                    self.settings.sticky_class = settings.sticky_class;
                    self.settings.sticky_topbar = topbar;
                    topbar.data('height', topbarContainer.outerHeight(true));
                    topbar.data('stickyOffset', topbarContainer.offset().top);

                    if (!settings.sticked) {
                        settings.start_offset && topbarContainer.css('top', settings.start_offset);
                        self.sticked(topbar);

                        // Pad body when sticky (scrolled) or fixed.
                        self.add_custom_rule('.act-topbar-fixed { padding-top: ' + topbar.data('height') + 'px; }');
                    }
                }

            });

        },

        stickable: function(topbarContainer, settings) {
            return topbarContainer.hasClass(settings.sticky_class);
        },

        timer: null,

        events: function(bar) {
            var self = this;

            $(this.scope)
                .off('.topbar')
                .on('click.topbar contextmenu.topbar', '.top-bar .top-bar-section li a[href^="#"],[' + this.attr_name() + '] .top-bar-section li a[href^="#"]', function(e) {
                    var li = $(this).closest('li'),
                        topbar = li.closest('[' + self.attr_name() + ']'),
                        settings = topbar.data(self.attr_name(true) + '-init');

                    if (settings.is_hover) {
                        var hoverLi = $(this).closest('.hover');
                        hoverLi.removeClass('hover');
                    }
                });

            $(window).off('.topbar')
                .on('resize.topbar', this.resize())
                .trigger('resize.topbar')
                .load(function() {
                    // Ensure that the offset is calculated after all of the pages resources have loaded
                    $(this).trigger('resize.topbar');
                });

            $('body').off('.topbar').on('click.topbar', function(e) {
                var parent = $(e.target).closest('li').closest('li.hover');

                if (parent.length > 0) {
                    return;
                }

                $('[' + self.attr_name() + '] li.hover').removeClass('hover');
            });

            // Show dropdown menus when their items are focused
            $(this.scope).find('.dropdown a')
                .focus(function() {
                    $(this).parents('.has-dropdown').addClass('hover');
                })
                .blur(function() {
                    $(this).parents('.has-dropdown').removeClass('hover');
                });
        },

        resize: function() {
            var self = this;
            $('[' + this.attr_name() + ']').each(function() {
                var topbar = $(this),
                    settings = topbar.data(self.attr_name(true) + '-init');

                var stickyContainer = topbar.parent('.' + self.settings.sticky_class);
                var stickyOffset;

                if (self.stickable(stickyContainer, self.settings)) {
                    if (stickyContainer.hasClass('fixed')) {
                        // Remove the fixed to allow for correct calculation of the offset.
                        stickyContainer.removeClass('fixed');

                        stickyOffset = stickyContainer.offset().top;
                        if ($(document.body).hasClass('act-topbar-fixed')) {
                            stickyOffset -= topbar.data('height');
                        }

                        topbar.data('stickyOffset', stickyOffset);
                        stickyContainer.addClass('fixed');
                    } else {
                        stickyOffset = stickyContainer.offset().top;
                        topbar.data('stickyOffset', stickyOffset);
                    }
                }

            });
        },

        sticked: function(topbar) {
            // check for sticky
            this.sticky(topbar.parent());

            topbar.data(this.attr_name(true), $.extend({}, topbar.data(this.attr_name(true)), {
                sticked: true
            }));
        },

        sticky: function(element) {
            var self = this;

            $(window).on('scroll', function() {
                if(!self.supportSticky(element)) {
                    self.update_sticky_positioning();
                }
                self.changeStatus(element, 'sticking');
            });
        },

        changeStatus: function(element, className) {
            var stickier = this.settings.sticky_topbar;
            if(stickier) {
                if (this.isSticky(stickier)) {
                    element.addClass(className);
                }
                else {
                    element.removeClass(className);
                }
            }
        },

        isSticky: function(element) {
            var $window = $(window),
                distance = element.data('stickyOffset') - this.settings.start_offset;
            return $window.scrollTop() > distance;
        },

        supportSticky: function(element) {
            var dom = document.createElement('test');
            dom.style.position = '-webkit-sticky';
            dom.style.position = 'sticky';
            return /sticky/.test(dom.style.position) && ['visible', ''].indexOf($(element).parent().css('overflow')) > -1;
        },

        update_sticky_positioning: function() {
            var klass = '.' + this.settings.sticky_class,
                stickier = this.settings.sticky_topbar;

            if (stickier && this.stickable(stickier.parent(), this.settings)) {
                if (this.isSticky(stickier)) {
                    if (!$(klass).hasClass('fixed')) {
                        $(klass).addClass('fixed');
                        $('body').addClass('act-topbar-fixed');
                    }
                } else {
                    if ($(klass).hasClass('fixed')) {
                        $(klass).removeClass('fixed');
                        $('body').removeClass('act-topbar-fixed');
                    }
                }
            }
        },

        off: function() {
            $(this.scope).off('.topbar');
            $(window).off('.topbar');
        },

        reflow: function() {}
    };
}(jQuery, window, window.document));

(function($, window, document, undefined) {
    'use strict';

    Mobile.libs.validator = {
        name: 'validator',

        settings: {
            validate_on: 'manual', // change (when input value changes), blur (when input blur), manual (when call custom events)
            exception: ':hidden, [data-validator-ignore]', // ignore validate with 'exception' setting
            focus_on_invalid: false, // automatically bring the focus to an invalid input field
            has_hint: true, // popup a alert window if invalid
            error_labels: true, // labels with a for="inputId" will receive an `error` class
            error_class: 'has-error', // labels with a for="inputId" will receive an `error` class
            feedback: '.form-row', // support a parent(s) selector for feedback an error message box
            alert_element: '.alert-box', // for an error message box class
            isAjax: false, // You can set ajax mode
            preventDefault: false,
            // the amount of time Validator will take before it validates the form (in ms).
            // smaller time will result in faster validation
            timeout: 1000,
            patterns: {
                alpha: /^[a-zA-Z]*$/,
                digital: /^\d*$/,
                alpha_digital: /^[a-zA-Z\d]*$/,
                int: /^[-+]?\d*$/,
                positive: /^\+?\d*(?:[\.]\d+)?$/,
                negative: /^-\d*(?:[\.]\d+)?$/,
                number: /^[-+]?\d*(?:[\.]\d+)?$/,
                // http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#valid-e-mail-address
                email: /^[\w.!#$%&'*+\/=?^`{|}~-]+@[a-zA-Z\d](?:[a-zA-Z\d-]{0,61}[a-zA-Z\d])?(?:\.[a-zA-Z\d](?:[a-zA-Z\d-]{0,61}[a-zA-Z\d])?)*$/,
                // http://blogs.lse.ac.uk/lti/2008/04/23/a-regular-expression-to-match-any-url/
                url: /^(https?|ftp|file|ssh):\/\/([-;:&=\+\$,\w]+@{1})?([-A-Za-z\d\.]+)+:?(\d+)?((\/[-\+~%\/\.\w]+)?\??([-\+=&;%@\.\w]+)?#?([\w]+)?)?/,
                // abc.de
                domain: /^([a-zA-Z\d]([a-zA-Z\d\-]{0,61}[a-zA-Z\d])?\.)+[a-zA-Z]{2,8}$/,
                datetime: /^([0-2]\d{3})\-([0-1]\d)\-([0-3]\d)\s([0-2]\d):([0-5]\d):([0-5]\d)([-+]([0-1]\d)\:00)?$/,
                // YYYY-MM-DD
                date: /(?:19|20)\d{2}[-/.](?:(?:0?[1-9]|1[0-2])[-/.](?:0?[1-9]|1\d|2\d)|(?:(?!02)(?:0?[1-9]|1[0-2])[-/.](?:30))|(?:(?:0?[13578]|1[02])[-/.]31))$/,
                // HH:MM:SS
                time: /^(0?\d|1\d|2[0-3])(:[0-5]\d){2}$/,
                // #FFF or #FFFFFF
                color: /^#([a-fA-F\d]{6}|[a-fA-F\d]{3})$/,
                mobile: /^0?(?:1(?:[38]\d)|(?:4[579])|(?:[57][0-35-9]))\d{8}$/,
                tel: /^(0\d{2,3}-?)?[2-9]\d{5,7}(-\d{1,5})?$/,
                zip: /^\d{6}$/
            },
            verifiers: {
                requiredone: function(el, required, parent) {
                    return $(el).closest('[' + this.attr_name() + ']').find('input[type="' + el.type + '"][name="' + el.name + '"]:checked:not(:disabled)').length;
                },
                equalto: function(el, required, parent) {
                    var from = document.querySelector(el.getAttribute(this.add_namespace(this.attr_name() + '-equalto')));

                    return from && (from.value === el.value);

                },
                oneof: function(el, required, parent) {
                    var els = document.querySelectorAll(el.getAttribute(this.add_namespace(this.attr_name() + '-oneof')));
                    return this.valid_oneof(els, required, parent);
                }
            },
            alerts: {
                required: '请{how}{placeholder}！',
                alpha: '请填写英文字母！',
                digital: '只允许填写数字！',
                alpha_digital: '请填写英文字母或数字！',
                int: '请填写整数！',
                positive: '请填写正数！',
                negative: '请填写负数！',
                number: '请填写数值！',
                email: '请填写正确的邮箱地址！',
                url: '请填写正确的 URL 地址！',
                domain: '请填写正确的域名！',
                datetime: '请填写正确的日期和时间格式！',
                date: '请填写正确的日期格式！',
                time: '请填写正确的时间格式！',
                color: '请填写十六进制颜色格式！',
                mobile: '手机号码有误，请重新填写！',
                tel: '电话号码有误，请重新填写!',
                zip: '邮政编码格式有误，请重新填写！'
            }
        },

        timer: null,

        init: function(scope, method) {
            this.bindings(method, Array.prototype.slice.call(arguments, 2));
        },

        events: function(scope) {
            var self = this,
                form = $(scope).attr('novalidate', 'novalidate'),
                settings = form.data(this.attr_name(true) + '-init') || {};

            this.invalid_attr = this.add_namespace('data-invalid');

            function validate(el, originalSelf, e) {
                clearTimeout(self.timer);
                self.timer = setTimeout(function() {
                    self.validate(el, [originalSelf], e);
                }.bind(originalSelf), settings.timeout);
            }

            form
                .off('.validator')
                .on('submit.validator', function(e) {
                    var $this = $(this);
                    var is_ajax = $this.attr(self.attr_name()) === 'ajax' || self.settings.isAjax;
                    $this.find('[type=submit]').prop('disabled', true);
                    return self.validate($this, $this.find('input, textarea, select, [' + self.attr_name() +  '-verifier]').not(settings.exception).get(), e, is_ajax);
                })
                .on('validate.validator', function(e) {
                    if (settings.validate_on === 'manual') {
                        self.validate($(this), [e.target], e);
                    }
                })
                .on('reset', function(e) {
                    return self.reset($(this), e);
                })
                .find('input, textarea, select').not(settings.exception)
                .off('.validator')
                .on('blur.validator', function(e) {
                    var id = this.id,
                        parent = $(this).closest('form'),
                        eqTo = parent.find('[' + self.attr_name() +  '-equalto="#' + id + '"]')[0];
                    // checks if there is an equalTo equivalent related by id
                    if (eqTo && eqTo.value) {
                        validate(parent, eqTo, e);
                    }

                    if (settings.validate_on === 'blur') {
                        validate(parent, this, e);
                    }
                })
                .on('change.validator', function(e) {
                    var id = this.id,
                        parent = $(this).closest('form'),
                        eqTo = parent.find('[' + self.attr_name() +  '-equalto="#' + id + '"]')[0];
                    // checks if there is an equalTo equivalent related by id
                    if (eqTo && eqTo.value) {
                        validate(parent, eqTo, e);
                    }

                    if (settings.validate_on === 'change') {
                        validate(parent, this, e);
                    }
                });
                // Not compatible, so commet it for a while
                // .on('focus.validator', function(e) {
                //     if (navigator.userAgent.match(/iPad|iPhone|Android|BlackBerry|Windows Phone|webOS/i)) {
                //         $('html, body').animate({
                //             scrollTop: $(e.target).offset().top
                //         }, 100);
                //     }
                // });
        },

        reset: function(form, e) {
            form.removeAttr(this.invalid_attr);
            var settings = form.data(this.attr_name(true) + '-init') || {};

            $('[' + this.invalid_attr + ']', form).removeAttr(this.invalid_attr);
            $('.' + settings.error_class, form).not(settings.alert_element).removeClass(settings.error_class);
            $(':input', form).not(':radio, :checkbox, :button, :submit, :reset,' + settings.exception).val('').removeAttr(this.invalid_attr);
            $('input:radio, input:checkbox', form).prop('checked', false).removeAttr(this.invalid_attr);
        },

        validate: function(form, els, e, is_ajax) {
            var validations = this.parse_patterns(form, els),
                validation_count = validations.length,
                submit_event = /submit/i.test(e.type);

            // Has to count up to make sure the focus gets applied to the top error
            for (var i = 0; i < validation_count; i++) {
                if (!validations[i] && (submit_event || is_ajax)) {
                    if (this.settings.focus_on_invalid) {
                        els[i].focus();
                    }
                    form.trigger('invalid.validator', [e]);
                    $(els[i]).closest('form').attr(this.invalid_attr, '');
                    form.find('button[type=submit]').prop('disabled', false);
                    return false;
                }
            }

            if (submit_event || is_ajax) {
                if (this.settings.preventDefault) e.preventDefault();
                form.trigger('valid.validator', [e]);
            }

            form.removeAttr(this.invalid_attr);

            if (is_ajax) {
                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    dataType: 'json',
                    beforeSend: function() {
                        $(document).mobile('tips', 'show', ['<div class="text-align"><i class="spinner"></i></div>', 'msg']);
                    }
                })
                .done(function(rs) {
                    form.trigger('complete.validator', [rs]);
                    form.find('[type=submit]').prop('disabled', false);
                })
                .fail(function() {
                    form.find('[type=submit]').prop('disabled', false);
                });
                return false;
            }

            return true;
        },

        parse_patterns: function(form, els) {
            var i = els.length,
                el_patterns = [];

            while (i--) {
                el_patterns.push(this.pattern(els[i]));
            }

            if (el_patterns.length) {
                el_patterns = this.check_validation(form, el_patterns);
            }

            return el_patterns;
        },

        pattern: function(el) {
            var type = el.type,
                required = el.hasAttribute('required'),
                pattern = el.getAttribute('pattern') || '',
                verifier = el.getAttribute(this.add_namespace(this.attr_name() + '-verifier')) || '',
                eqTo = el.hasAttribute(this.add_namespace(this.attr_name() + '-equalto')),
                oneof = el.hasAttribute(this.add_namespace(this.attr_name() + '-oneof'));


            if (this.settings.patterns.hasOwnProperty(pattern)) {
                return [el, pattern, this.settings.patterns[pattern], required];
            } else if (pattern) {
                return [el, null, new RegExp('^' + pattern + '$'), required];
            } else if (verifier) {
                return [el, verifier, this.settings.patterns[type], required];
            } else if (this.settings.patterns.hasOwnProperty(type)) {
                return [el, type, this.settings.patterns[type], required];
            } else if (eqTo || oneof) {
                return [el, eqTo ? 'equalto' : 'oneof', pattern, required];
            } else {
                pattern = /^[\s\S]*$/;
                return [el, required ? 'required' : null, pattern, required];
            }
        },

        // TODO: Break this up into smaller methods, getting hard to read.
        check_validation: function(form, el_patterns) {
            var i = el_patterns.length,
                validations = [],
                settings = form.data(this.attr_name(true) + '-init') || {};

            while (i--) {
                var el = el_patterns[i][0],
                    required = el_patterns[i][3],
                    value = el.value.trim(),
                    is_radio = el.type === 'radio',
                    is_checkbox = el.type === 'checkbox',
                    direct_parent = $(el).parent(),
                    verifier = el.getAttribute(this.add_namespace(this.attr_name() + '-verifier')),
                    label = (function() {
                        var label = $(el).siblings('label');
                        if (!label.length) {
                            label = $('label[for="' + el.id + '"]');
                        }
                        return label;
                    })(),
                    valid_length = required ? (value.length > 0) : true,
                    el_validations = [],
                    parent,
                    valid;

                if ((is_radio || is_checkbox) && required) {
                    verifier = 'requiredone';
                }
                // support old way to do equalTo validations
                if (el.getAttribute(this.add_namespace(this.attr_name() + '-equalto'))) {
                    verifier = 'equalto';
                }
                if (el.getAttribute(this.add_namespace(this.attr_name() + '-oneof'))) {
                    verifier = 'oneof';
                }

                if (settings.feedback) {
                    parent = $(el).parents(settings.feedback).eq(0);
                }
                if (!parent || !parent.length) {
                    if (direct_parent.is('label')) {
                        parent = direct_parent.parent();
                    } else {
                        parent = direct_parent;
                    }
                }

                if (verifier) {
                    // Validate using each of the specified (space-delimited) verifiers.
                    var verifiers = verifier.split(' ');
                    var last_valid = true,
                        all_valid = true;
                    for (var iv = 0; iv < verifiers.length; iv++) {
                        valid = this.settings.verifiers[verifiers[iv]].apply(this, [el, required, parent]);
                        el_validations.push(valid);
                        all_valid = valid && last_valid;
                        last_valid = valid;
                    }
                    if (all_valid) {
                        this.validSuccess(el, parent, label);
                    } else {
                        validations = this.validError(el, parent, label, i, el_patterns, el_validations);
                        if(validations.length) break;
                    }
                } else {
                    el_validations.push(el_patterns[i][2].test(value) && valid_length || !required && !value.length || el.disabled);

                    el_validations = [el_validations.every(function(valid) {
                        return valid;
                    })];
                    if (el_validations[0]) {
                        this.validSuccess(el, parent, label);
                    } else {
                        validations = this.validError(el, parent, label, i, el_patterns, el_validations);
                        if(validations.length) break;
                    }
                }
                validations = validations.concat(el_validations);
            }

            return validations;
        },

        validSuccess: function(el, parent, label) {
            el.removeAttribute(this.invalid_attr);
            el.setAttribute('aria-invalid', 'false');
            el.removeAttribute('aria-describedby');
            parent.removeClass(this.settings.error_class);
            if (label.length > 0 && this.settings.error_labels) {
                label.removeClass(this.settings.error_class).removeAttr('role');
            }
            $(el).triggerHandler('valid');
        },

        validError: function(el, parent, label, i, el_patterns, el_validations) {
            var validations = [];
            el.setAttribute(this.invalid_attr, '');
            el.setAttribute('aria-invalid', 'true');

            // Try to find the error associated with the input
            var errorElement = parent.find(this.settings.alert_element);
            var type = this.settings.alerts[el_patterns[i][1]];
            var dataset = el_patterns[i][0].dataset;
            var msg = dataset.alerts;
            // var alertKeys = Object.keys(dataset).filter(function(k, v) {
            //     return k.indexOf('alerts') === 0;
            // });
            // var msg = '';
            // $.each(alertKeys, function() {
            //     if (this.replace('alerts', '').toLowerCase() == el_patterns[i][1]) {
            //         msg = dataset[this];
            //     }
            // });

            var how = '输入';
            if(!msg) {
                if (type) {
                    if (['radio', 'checked'].indexOf(el.type) > -1 || el.tagName === 'select') {
                        how = '选择';
                    }
                    msg = type.replace('{how}', how).replace('{placeholder}', label.text().replace(/[：:]$/, '') || el_patterns[i][0].placeholder || '其中一项');
                } else {
                    msg = '输入不符合要求，请检查！';
                }
            }

            if (!errorElement.length) {
                if(this.settings.has_hint) {
                    alert(msg);
                    $(el).triggerHandler('invalid');
                    return validations.concat(el_validations);
                }
            } else {
                // errorElement.html(msg);
                var errorID = errorElement.attr('id');
                if (errorID) {
                    el.setAttribute('aria-describedby', errorID);
                }
            }

            // el.setAttribute('aria-describedby', $(el).find('.error')[0].id);
            parent.addClass(this.settings.error_class);
            if (label.length > 0 && this.settings.error_labels) {
                label.addClass(this.settings.error_class).attr('role', 'alert');
            }
            $(el).triggerHandler('invalid');

            return validations;
        },

        // valid_checkbox: function(el, required) {
        //     var el = $(el),
        //         valid = (el.is(':checked') || !required || el.get(0).getAttribute('disabled'));

        //     if (valid) {
        //         el.removeAttr(this.invalid_attr).parent().removeClass(this.settings.error_class);
        //         $(el).triggerHandler('valid');
        //     } else {
        //         el.attr(this.invalid_attr, '').parent().addClass(this.settings.error_class);
        //         $(el).triggerHandler('invalid');
        //     }

        //     return valid;
        // },

        // valid_radio: function(el, required) {
        //     var name = el.getAttribute('name'),
        //         group = $(el).closest('[data-' + this.attr_name(true) + ']').find("[name='" + name + "']"),
        //         count = group.length,
        //         valid = false,
        //         disabled = false;

        //     // Has to count up to make sure the focus gets applied to the top error
        //     for (var i = 0; i < count; i++) {
        //         if (group[i].getAttribute('disabled')) {
        //             disabled = true;
        //             valid = true;
        //         } else {
        //             if (group[i].checked) {
        //                 valid = true;
        //             } else {
        //                 if (disabled) {
        //                     valid = false;
        //                 }
        //             }
        //         }
        //     }

        //     // Has to count up to make sure the focus gets applied to the top error
        //     for (var i = 0; i < count; i++) {
        //         if (valid) {
        //             $(group[i]).removeAttr(this.invalid_attr).parent().removeClass(this.settings.error_class);
        //             $(group[i]).triggerHandler('valid');
        //         } else {
        //             $(group[i]).attr(this.invalid_attr, '').parent().addClass(this.settings.error_class);
        //             $(group[i]).triggerHandler('invalid');
        //         }
        //     }

        //     return valid;
        // },

        // valid_equal: function(el, required, parent) {
        //     var from = document.getElementById(el.getAttribute(this.add_namespace(this.attr_name() + '-equalto'))).value,
        //         to = el.value,
        //         valid = (from === to);

        //     if (valid) {
        //         $(el).removeAttr(this.invalid_attr);
        //         parent.removeClass(this.settings.error_class);
        //         if (label.length > 0 && settings.error_labels) {
        //             label.removeClass(this.settings.error_class);
        //         }
        //     } else {
        //         $(el).attr(this.invalid_attr, '');
        //         parent.addClass(this.settings.error_class);
        //         if (label.length > 0 && settings.error_labels) {
        //             label.addClass(this.settings.error_class);
        //         }
        //     }

        //     return valid;
        // },

        valid_oneof: function(el, required, parent, doNotValidateOthers) {
            var el = $(el),
                valid = el.filter(function() {
                    return ['radio', 'checkbox'].indexOf(this.type) > -1 ? this.checked : !!this.value.trim();
                }).length > 0;

            if (valid) {
                el.removeAttr(this.invalid_attr);
                parent.removeClass(this.settings.error_class);
            } else {
                el.attr(this.invalid_attr, '');
                parent.addClass(this.settings.error_class);
            }

            if (!doNotValidateOthers) {
                var _this = this;
                el.each(function() {
                    _this.valid_oneof.call(_this, this, null, parent, true);
                });
            }

            return valid;
        },

        reflow: function(scope, options) {
            var self = this,
                form = $('[' + this.attr_name() + ']'); //.attr('novalidate', 'novalidate');

            form.each(function() {
                self.events(this);
            });
        }
    };
}(jQuery, window, window.document));

