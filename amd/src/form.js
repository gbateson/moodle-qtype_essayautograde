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
 * javascript for Essay (autograde) edit form
 *
 * @module      qtype_essayautograde/form
 * @category    output
 * @copyright   2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.0
 */
define(["jquery"], function($) {

    /** @alias module:qtype_essayautograde/form */
    var FORM = {};

    // cache for standard width of TEXT input elements
    FORM.sizewidths = new Array();

    /**
     * initialize this AMD module
     */
    FORM.init = function() {
        FORM.init_target_phrases();
        FORM.init_add_button("addbands", "id_gradebands");
        FORM.init_add_button("addphrases", "id_targetphrases");
    };

    /**
     * Make the target phrase text boxes "expandable",
     * i.e. expand/contract to fit the width of the content
     */
    FORM.init_target_phrases = function() {
        $("input[id^=id_phrasematch_]").each(function(){
            $(this).keyup(function(){
                // get min width for a box with this "size"
                var sizewidth = 0;
                var size = $(this).attr("size");
                if (size) {
                    if (size in FORM.sizewidths) {
                        sizewidth = FORM.sizewidths[size];
                    } else {
                        var elm = document.createElement("INPUT");
                        $(elm).attr("size", size);
                        $(elm).css("width", "auto");
                        $(elm).hide().appendTo("BODY");
                        sizewidth = $(elm).outerWidth();
                        $(elm).remove();
                        FORM.sizewidths[size] = sizewidth;
                    }
                }
                // get required width for this text value
                var txt = document.createTextNode($(this).val());
                var elm = document.createElement("SPAN");
                $(elm).append(txt).hide().appendTo("BODY");
                var w = Math.max($(elm).width(), sizewidth);
                $(elm).remove();
                $(this).width(w);
            });
            $(this).triggerHandler("keyup");
        });
    };

    /**
     * modify an "add" button so that the page scrolls down
     * to the appropriate anchor when it reloads
     */
    FORM.init_add_button = function(name, anchor) {
        $("input[name=" + name + "]").click(function(){
            var url = $(this).closest("form").prop("action");
            url = url.replace(new RegExp("#.*$"), "");
            $(this).closest("form").prop("action", url + "#" + anchor);
        });
    };

    return FORM;
});
