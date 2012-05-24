;(function($) {
    if (!$.depage) {
        $.depage = {};
    }
    
    $.depage.socialButtons = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.socialButtons", base);

        // {{{ init
        base.init = function(){
            base.options = $.extend({}, $.depage.socialButtons.defaultOptions, options);
            
            base.url = encodeURIComponent(base.options.location);
            if (base.options.title !== "") {
                base.title = encodeURIComponent(base.options.title);
            } else {
                base.title = encodeURIComponent(document.title);
            }

            $.each(base.options.services, function(i, name) {
                // normalize name
                name = name.toLowerCase();
                name = name.charAt(0).toUpperCase() + name.slice(1);
                
                var functionName = 'add' + name;

                if (typeof base[functionName] === 'function') {
                    base[functionName](name, location);
                } else if (window.console) {
                    console.log("social button type '" + name + "' unknown.");
                }
            });
        };
        // }}}
        
        // {{{ addSocialButton
        base.addSocialButton = function(name, link, text) {
            var html = "";

            html += "<a class=\"" + name + "\">";
            if (base.options.assetPath !== "") {
                // image link
                html += "<img src=\"" + base.options.assetPath + name.toLowerCase() + ".png\" alt=\"" + name + "\">";
            } else {
                // text link
                html += "<span class=\"" + name.toLowerCase() + "\">" + text + "</span>";
            }
            html += "</a>";

            var $link = $(html).appendTo(base.$el)

            if (link !== "") {
                $link.attr("href", link);
            }
            if (link.substr(0, 4) == "http")  {
                $link.attr("target", "_blank");
                $link.click(function() {
                    var w = 850;
                    var h = 500;
                    var options = "height=" + h + ",width=" + w + ",fullscreen=0,dependent=0,location=0,menubar=0,resizable=1,scrollbars=0,status=1,titlebar=0,toolbar=0";
                    
                    return !window.open(this.href, name, options);
                });
            }

            return $link;
        };
        // }}}
        
        // {{{ addTwitter
        base.addTwitter = function(name) {
            var link = "http://twitter.com/share?text=" + base.title + "&url=" + base.url;
            base.addSocialButton(name, link, "t");
        };
        // }}}
        // {{{ addFacebookshare
        base.addFacebookshare = function(name) {
            var link = "http://www.facebook.com/sharer.php?t=" + base.title + "&u=" + base.url;
            base.addSocialButton(name, link, "f");
        };
        // }}}
        // {{{ addFacebooklike
        base.addFacebooklike = function(name) {
            if ($.browser.msie && parseInt($.browser.version, 10) < 9) {
                // opacity not supported
                return;
            }
            var link = "";
            var iframe = "<span class=\"over\"><iframe id=\"facebook\" src=\"//www.facebook.com/plugins/like.php?href=" + base.url + "&amp;send=false&amp;layout=standard&amp;width=30&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;height=30\" scrolling=\"no\" frameborder=\"0\" style=\"border:none; overflow:hidden; width:30px; height:30px;\" allowTransparency=\"true\"></iframe><span>";

            base.addSocialButton(name, link, "♥");
            $(iframe).appendTo(base.$el);
        };
        // }}}
        // {{{ addGoogleplusshare
        base.addGoogleplusshare = function(name) {
            var link = "https://plusone.google.com/_/+1/confirm?url=" + base.url;
            base.addSocialButton(name, link, "+1");
        };
        // }}}
        // {{{ addLinkedin
        base.addLinkedin = function(name) {
            var link = "http://www.linkedin.com/cws/share?url=" + base.url;
            base.addSocialButton(name, link, "li");
        };
        // }}}
        // {{{ addDigg
        base.addDigg = function(name) {
            var link = "http://digg.com/submit?url=" + base.url;
            base.addSocialButton(name, link, "digg");
        };
        // }}}
        // {{{ addReddit
        base.addReddit = function(name) {
            var link = "http://www.reddit.com/submit?url=" + base.url;
            base.addSocialButton(name, link, "reddit");
        };
        // }}}
        // {{{ addMail
        base.addMail = function(name) {
            var link = "mailto:?subject=" + base.title + "&body=" + base.url;
            base.addSocialButton(name, link, "mail");
        };
        // }}}
        
        // Run initializer
        base.init();
    };
    
    /**
     * Options
     * 
     * @param assetPath - path to the asset-folder (with flash-player and images for buttons)
     * @param twitter - add twitter buztton
     * @param facebookShare - add facebook share button
     * @param googleplusShare - add google+ share button
     * @param facebookLike - add facebook like button
     */
    $.depage.socialButtons.defaultOptions = {
        assetPath: '',
        location: document.location.href,
        title: "",
        services: [
            'twitter',
            'facebookShare',
            'googleplusShare',
            'facebookLike',
            'mail',
            'linkedin',
            'digg',
            'reddit'
        ]
    };
    
    $.fn.depageSocialButtons = function(options) {
        return this.each(function(){
            (new $.depage.socialButtons(this, options));
        });
    };
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
