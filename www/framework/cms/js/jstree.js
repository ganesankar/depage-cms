/**
 * @require framework/cms/js/depage.jstree.js
 *
 * Global function allows tree to be init with required options
 *
 * TODO consider refactor or at least namespacing
 *
 * @param tree
 */
// init the tree

$(function($){

    $('.jstree-container').each(function(){

        /**
         * Init depage tree
         */
        $(this).depageTree({
            /**
             * Plugins
             *
             * The list of plugins to include
             */
            plugins : [
                //"select_created_nodes",
                //"pedantic_html_data", // @todo check if still needed
                //"dnd_placeholder", // @todo check dnd vs dnd_palceholder

                "themes",
                "ui",
                "dnd",
                "typesfromurl",
                "hotkeys",
                "contextmenu",
                "nodeinfo",
                "dblclickrename",
                "tooltips",
                "add_marker",
                "deltaupdates",
                "toolbar",

                /**
                 * custom doctype handlers
                 */
                "doctype_page"
            ],

            /**
             * Plugin configuration
             */
            ui : {
                // TODO:
                "initially_select" : ($(this).attr("data-selected-nodes") || "").split(" ")
            },

            /**
             * Core
             */
            core : {
                animation : 0,
                initially_open : ($(this).attr("data-open-nodes") || "").split(" "),
                copy_node : function() {alert('hello');}
            },

            /**
             * Themes
             */
            themes : {
                "theme" : "default",
                "url" : $(this).attr("data-theme")
            },

            /**
             * Delta Updates
             */
            deltaupdates : {
                "webSocketURL" : $(this).attr("data-delta-updates-websocket-url"),
                "fallbackPollURL" : $(this).attr("data-delta-updates-fallback-poll-url"),
                "postURL" : $(this).attr("data-delta-updates-post-url")
            },

            /**
             * Hotkeys
             */
            hotkeys : {
                "up" : function() {
                    $.jstree.keyUp.apply(this);
                },
                "ctrl+up" : function () {
                    $.jstree.keyUp.apply(this);
                    return false;
                },
                "shift+up" : function () {
                    $.jstree.keyUp.apply(this);
                    return false;
                },
                "down" : function(){
                    $.jstree.keyDown.apply(this);
                    return false;
                },
                "ctrl+down" : function () {
                    $.jstree.keyDown.apply(this);
                    return false;
                },
                "shift+down" : function () {
                    $.jstree.keyDown.apply(this);
                    return false;
                },
                "left" : function () {
                    $.jstree.keyLeft.apply(this);
                    return false;
                },
                "ctrl+left" : function () {
                    $.jstree.keyLeft.apply(this);
                    return false;
                },
                "shift+left" : function () {
                    $.jstree.keyLeft.apply(this);
                    return false;
                },
                "right" : function () {
                    $.jstree.keyRight.apply(this);
                    return false;
                },
                "ctrl+right" : function () {
                    $.jstree.keyRight.apply(this);
                    return false;
                },
                "shift+right" : function () {
                    $.jstree.keyRight.apply(this);
                    return false;
                },
                "del" : function () {
                    var node = $(this.data.ui.selected[0] || this.data.ui.hovered[0]);

                    var offset = node.offset();

                    $.jstree.confirmDelete(offset.left, offset.top, function(){
                        $.jstree.contextDelete(node);
                    });
                },
                "return" : function() {
                    // @todo bind enter key to prevent default so that we dont leave input on enter
                    var node = this;
                    setTimeout(function () { node.edit(); }, 300);
                    return false;
                }
            },

            /**
             * Context Menu
             */
            contextmenu : {
                items : function (obj) {

                    var default_items = {
                        "rename" : {
                            "_disabled"         : !this.check('rename_node', obj, this.get_parent()),
                            "separator_before"  : false,
                            "separator_after"   : false,
                            "label"             : "Rename",
                            "action"            : function (data) {
                                $.jstree.contextRename(data);
                            }
                        },
                        "remove" : {
                            "_disabled"          : !this.check('delete_node', obj, this.get_parent()),
                            "separator_before"  : false,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Delete",
                            "action"            : function (data) {
                                $.jstree.contextDelete(data);
                            }
                        },
                        "ccp" : {
                            "separator_before"  : true,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Edit",
                            "action"            : false,
                            "submenu" : {
                                "cut" : {
                                    "_disabled"         : !this.check('cut_node', obj, this.get_parent()),
                                    "separator_before"  : false,
                                    "separator_after"   : false,
                                    "label"             : "Cut",
                                    "action"            : function (data) {
                                        $.jstree.contextCut(data);
                                    }
                                },
                                "copy" : {
                                    "_disabled"         : !this.check('copy_node', obj, this.get_parent()),
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Copy",
                                    "action"            : function (data) {
                                        $.jstree.contextCopy(data);
                                    }
                                },
                                "paste" : {
                                    "separator_before"  : false,
                                    "icon"              : false,
                                    "separator_after"   : false,
                                    "label"             : "Paste",
                                    "_disabled"         : typeof(this.can_paste) === "undefined" ? false : !(this.can_paste()),
                                    "action"            : function (data) {
                                        $.jstree.contextPaste(data);
                                    }
                                }
                            }
                        }
                    };

                    // add the create menu based on the available nodes fetched in typesfromurl
                    if(typeof(this.get_settings()['typesfromurl']) !== "undefined") {

                        var type_settings = this.get_settings()['typesfromurl'];

                        var type = obj.attr(type_settings.type_attr);
                        var available_nodes = type_settings.valid_children[type];

                        default_items = $.extend($.jstree.buildCreateMenu(available_nodes), default_items);

                    } else {
                        // TODO default create menu
                    }

                    return default_items;
                }
            },

            /**
             * Toolbar
             */
            toolbar : {
                items : function(obj) {
                    return {
                        "create" : {
                            "label"             : "Create",
                            "separator_before"  : false,
                            "separator_after"   : true,
                            "_disabled"         : !this.check('create_node', obj, this.get_parent()),
                            "action"            : function(obj) {

                                var node = $(".jstree-clicked");
                                var offset = obj.offset();

                                var data = {
                                    "reference" : node,
                                    "element"   : node,
                                    position    : {
                                        "x"     : offset.left,
                                        "y"     : offset.top
                                    }
                                };

                                if (data.reference.length) {
                                    var inst = $.jstree._reference(data.reference);

                                    // build the create menu based on the available nodes fetched in typesfromurl

                                    if(typeof(inst.get_settings()['typesfromurl']) !== "undefined") {

                                        var type_settings = inst.get_settings()['typesfromurl'];

                                        var type = data.reference.parent().attr(type_settings.type_attr);
                                        var available_nodes = type_settings.valid_children[type];

                                        var create_menu = $.jstree.buildCreateMenu(available_nodes);

                                        $.vakata.context.show(data.reference, data.position, create_menu.create.submenu);

                                    } else {
                                        // TODO default create menu
                                    }
                                }
                            }
                        },
                        "remove" : {
                            "label"             : "Delete",
                            "_disabled"         : !this.check('delete_node', obj, this.get_parent()),
                            "action"            : function () {
                                var data = { "reference" : $(".jstree-clicked") };
                                if (data.reference.length) {
                                    $.jstree.contextDelete(data);
                                }
                            }
                        },
                        "duplicate" : {
                            "label"             : "Duplicate",
                            "_disabled"         : !this.check('duplicate_node', obj, this.get_parent()),
                            "action"            : function () {
                                var obj = $(".jstree-clicked").parent("li");
                                if (obj.length){
                                    var inst = $.jstree._reference(obj);

                                    var data = { "reference" : obj };

                                    $.jstree.contextDuplicate(data);
                                }
                            }
                        }
                    }
                }
            }
        });
    });
});