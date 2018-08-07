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
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.0
 */
define(["jquery"], function($) {

    /** @alias module:qtype_essayautograde/form */
    var FORM = {};

    /*
     * initialize this AMD module
     */
    FORM.init = function() {
        // Make the target phrase text boxes "expandable",
        // i.e. they adjust to fit the width of the content
        $("input[id^=id_phrasematch_]").each(function(){
            $(this).data("minwidth", $(this).outerWidth());
            $(this).keyup(function(){
                var txt = document.createTextNode($(this).val());
                var elm = document.createElement("SPAN");
                $(elm).append(txt).hide().appendTo("BODY");
                var w = $(elm).width();
                $(elm).remove();
                var minwidth = $(this).data("minwidth");
                $(this).width(Math.max(w, minwidth));
            });
            $(this).triggerHandler("keyup");
        });
    };

    return FORM;
});
