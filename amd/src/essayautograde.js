// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the term of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * load the EnglishCentral player
 *
 * @module      qtype_essayautograde/view
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.0
 */
define(["jquery", "core/str"], function($, STR) {

    /** @alias module:qtype_essayautograde/view */
    var ESSAY = {};

    // cache the plugin name
    ESSAY.plugin = "qtype_essayautograde";

    ESSAY.itemtype = "";
    ESSAY.itemmatch = "";
    ESSAY.editortype = "";

    ESSAY.editormaxtries = 50;
    ESSAY.editorinterval = 100; // 100 ms

    /*
     * initialize this AMD module
     */
    ESSAY.init = function(readonly, itemtype, editortype) {

        // get RegExp expression for this item type
        var itemmatch = "";
        switch (itemtype) {
            case "chars": itemmatch = "."; break;
            case "words": itemmatch = "\\w+"; break;
            case "sentences": itemmatch = "[^\\.]+[\\.]"; break;
            case "paragraphs": itemmatch = "[^\\r\\n]+[\\r\\n]*"; break;
        }
        // take a look at https://github.com/RadLikeWhoa/Countable/blob/master/Countable.js
        // for more ideas on how to count chars, words, sentences, and paragraphs

        ESSAY.itemtype = itemtype;
        ESSAY.itemmatch = new RegExp(itemmatch, "g");
        ESSAY.editortype = editortype;

        if (readonly) {
            ESSAY.setup_response_heights();
        } else {
            ESSAY.setup_itemcounts();
        }
    };

    ESSAY.setup_response_heights = function() {
       $("textarea.qtype_essay_response").each(function(){
           $(this).height(1);
           $(this).height(this.scrollHeight);
       });
    };

    ESSAY.setup_itemcounts = function() {
        $(".qtype_essay_response").each(function(){
            var id = ESSAY.get_itemcount_id(this);
            var editorloaded = $.Deferred();
            ESSAY.check_editor(this, editorloaded);
            $.when(editorloaded).done($.proxy(function(){
                ESSAY.create_itemcount(this, id);
                ESSAY.setup_itemcount(this, id);
            }, this, id));
        });

    };

    ESSAY.check_editor = function(response, editorloaded) {
        var selector = "";
        switch (ESSAY.editortype) {
            case "atto": selector = "[contenteditable=true]"; break;
            case "tinymce": selector = "iframe"; break;
        }
        if (selector=="") {
            // textarea - or unknown !!
            editorloaded.resolve();
        } else {
            var editorchecker = setInterval(function() {
                if ($(response).find(selector).length) {
                    clearInterval(editorchecker);
                    editorloaded.resolve();
                }
            }, ESSAY.editorinterval);
        }
    };

    ESSAY.create_itemcount = function(response, id) {
        if (document.getElementById(id)===null) {
            var p = document.createElement("P");
            p.setAttribute("id", id);
            p.setAttribute("class", "itemcount");
            response.parentNode.insertBefore(p, response.nextSibling);
        }
    };

    ESSAY.setup_itemcount = function(response, id) {
        var editable = ESSAY.get_editable_element(response);
        if (editable) {
            $(editable).keyup(function(){
                ESSAY.show_itemcount(this, id);
            });
            ESSAY.show_itemcount(editable, id);
        }
    };

    ESSAY.get_editable_element = function(response) {
        // search for plain text editor
        if ($(response).prop("tagName")=="TEXTAREA") {
            return response;
        }
        // search for Atto editor
        var editable = $(response).find("[contenteditable=true]");
        if (editable.length) {
            return editable.get(0);
        }
        // search for MCE editor
        var i = response.getElementsByTagName("IFRAME");
        if (i.length) {
            i = i[0];
            var d = (i.contentWindow || i.contentDocument);
            if (d.document) {
                d = d.document;
            }
            if (d.body && d.body.isContentEditable) {
                return d.body;
            }
        }
        // search for disabled text editor
        var editable = $(response).find("textarea");
        if (editable.length) {
            return editable.get(0);
        }
        // shouldn't happen !!
        return null;
    };

    ESSAY.get_textarea = function(response) {
        if ($(response).prop("tagName")=="TEXTAREA") {
            return response;
        }
        return $(response).find("textarea").get(0);
    };

    ESSAY.get_textarea_name = function(response) {
        var textarea = ESSAY.get_textarea(response);
        return $(textarea).attr("name");
    };

    ESSAY.get_itemcount_id = function(response) {
        var name = ESSAY.get_textarea_name(response);
        return "id_" + name + "_itemcount";
    };

    ESSAY.escape = function(id) {
        var regexp = new RegExp("(:|\\.|\\[|\\]|,|=|@)", "g");
        return "#" + id.replace(regexp, "\\$1");
    };

    ESSAY.show_itemcount = function(response, id) {
        if ($(response).prop("tagName")=="TEXTAREA") {
            var itemcount = $(response).val().match(ESSAY.itemmatch);
        } else {
            var itemcount = $(response).text().match(ESSAY.itemmatch);
        }
        if (itemcount) {
            itemcount = itemcount.length;
        } else {
            itemcount = 0;
        }

        // fetch descriptor string
        STR.get_strings([
            {"key": ESSAY.itemtype, "component": ESSAY.plugin}
        ]).done(function(s) {
            $(ESSAY.escape(id)).text(s[0] + ": " + itemcount);
        });
    };

    return ESSAY;
});
