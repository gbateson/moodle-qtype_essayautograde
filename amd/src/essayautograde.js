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
 * load the Essay (auto-grade) question
 *
 * @module      qtype_essayautograde/view
 * @category    output
 * @copyright   2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.0
 */
define(["jquery", "core/str"], function($, STR) {

    /** @alias module:qtype_essayautograde/view */
    var ESSAY = {};

    // cache the plugin name and string cache
    ESSAY.plugin = "qtype_essayautograde";
    ESSAY.str = {};

    ESSAY.itemtype = "";
    ESSAY.itemmatch = "";

    ESSAY.minwords = 0;
    ESSAY.maxwords = 0;

    ESSAY.editortype = "";

    ESSAY.editormaxtries = 50;
    ESSAY.editorinterval = 100; // 100 ms

    ESSAY.responsesample = "";
    ESSAY.responseoriginal = "";

    /*
     * initialize this AMD module
     */
    ESSAY.init = function(readonly, itemtype, minwords, maxwords, editortype, responsesample) {

        // get RegExp expression for this item type
        var itemmatch = "";
        switch (itemtype) {
            case "chars": itemmatch = "."; break;
            case "words": itemmatch = "\\w+"; break;
            case "sentences": itemmatch = "[^\\.?!]+[\\.?!]"; break;
            case "paragraphs": itemmatch = "[^\\r\\n]+[\\r\\n]*"; break;
        }
        // take a look at https://github.com/RadLikeWhoa/Countable/blob/master/Countable.js
        // for more ideas on how to count chars, words, sentences, and paragraphs

        ESSAY.itemtype = itemtype;
        ESSAY.itemmatch = new RegExp(itemmatch, "g");

        ESSAY.minwords = minwords;
        ESSAY.maxwords = maxwords;

        ESSAY.editortype = editortype;

        if (readonly) {
            ESSAY.setup_response_heights();
        } else {
            ESSAY.setup_itemcounts();
            ESSAY.setup_responsesample(responsesample);
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
            var div = document.createElement("DIV");
            div.setAttribute("id", id);
            div.setAttribute("class", "itemcount");
            if (ESSAY.itemtype == "words") {
                STR.get_strings([
                    {"key": "maxwordslabel",   "component": ESSAY.plugin},
                    {"key": "maxwordswarning", "component": ESSAY.plugin},
                    {"key": "minwordslabel",   "component": ESSAY.plugin},
                    {"key": "minwordswarning", "component": ESSAY.plugin},
                    {"key": "countwordslabel", "component": ESSAY.plugin}
                ]).done(function(s){
                    ESSAY.str.maxwordslabel = s[0];
                    ESSAY.str.maxwordswarning = s[1];
                    ESSAY.str.minwordslabel = s[2];
                    ESSAY.str.minwordswarning = s[3];
                    ESSAY.str.countwordslabel = s[4];

                    // cache the CSS classes for the warnings about min/max words
                    var wordswarning = "wordswarning rounded bg-danger text-light ml-2 px-2 py-1 d-none";

                    var b = document.createElement("B");
                    b.setAttribute("class", "label");
                    b.appendChild(document.createTextNode(ESSAY.str.countwordslabel + ": "));

                    var i = document.createElement("I");
                    i.setAttribute("class", "value");
                    i.appendChild(document.createTextNode("0"));

                    var s = document.createElement("SPAN");
                    s.setAttribute("class", wordswarning);

                    var p = document.createElement("P");
                    p.setAttribute("class", "countwords mt-2 mb-0");
                    p.appendChild(b);
                    p.appendChild(i);
                    p.appendChild(s);
                    div.appendChild(p);

                    if (ESSAY.minwords) {
                        b = document.createElement("B");
                        b.setAttribute("class", "label");
                        b.appendChild(document.createTextNode(ESSAY.str.minwordslabel + ": "));

                        i = document.createElement("I");
                        i.setAttribute("class", "value");
                        i.appendChild(document.createTextNode(ESSAY.minwords));

                        p = document.createElement("P");
                        p.setAttribute("class", "minwords my-0");
                        p.appendChild(b);
                        p.appendChild(i);

                        div.appendChild(p);
                    }

                    if (ESSAY.maxwords) {
                        b = document.createElement("B");
                        b.setAttribute("class", "label");
                        b.appendChild(document.createTextNode(ESSAY.str.maxwordslabel + ": "));

                        i = document.createElement("I");
                        i.setAttribute("class", "value");
                        i.appendChild(document.createTextNode(ESSAY.maxwords));

                        p = document.createElement("P");
                        p.setAttribute("class", "maxwords my-0");
                        p.appendChild(b);
                        p.appendChild(i);

                        div.appendChild(p);
                    }
                });
            } else {
                STR.get_strings([
                    {"key": ESSAY.itemtype, "component": ESSAY.plugin}
                ]).done(function(s) {
                    ESSAY.str.countitems = s[0];

                    var b = document.createElement("B");
                    b.setAttribute("class", "label");
                    b.appendChild(document.createTextNode(ESSAY.str.countitems + ": "));

                    var i = document.createElement("I");
                    i.setAttribute("class", "value");
                    i.appendChild(document.createTextNode("0"));

                    var p = document.createElement("P");
                    p.setAttribute("class", "countitems my-0");
                    p.appendChild(b);
                    p.appendChild(i);

                    div.appendChild(p);
                });
            }
            response.parentNode.insertBefore(div, response.nextSibling);
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

    ESSAY.escaped_id = function(id) {
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
        id = ESSAY.escaped_id(id);
        if (ESSAY.itemtype == "words") {
            $(id + " .countwords .value").text(itemcount);

            var wordswarning = "";

            if (itemcount) {
                if (ESSAY.minwords && ESSAY.minwords > itemcount) {
                    wordswarning = ESSAY.str.minwordswarning;
                }
                if (ESSAY.maxwords && ESSAY.maxwords < itemcount) {
                    wordswarning = ESSAY.str.maxwordswarning;
                }
            }

            var elm = document.querySelector(id + " .countwords .wordswarning");
            if (elm) {
                elm.innerText = wordswarning;
                if (wordswarning == "") {
                    elm.classList.add("d-none");
                } else {
                    elm.classList.remove("d-none");
                }
            }
        } else {
            $(id + " .countitems .value").text(itemcount);
        }
    };

    ESSAY.setup_responsesample = function(txt) {
        if (txt=="") {
            return;
        }
        ESSAY.responsesample = txt;
        STR.get_strings([
            {"key": "hidesample", "component": ESSAY.plugin},
            {"key": "showsample", "component": ESSAY.plugin}
        ]).done(function(s) {
            ESSAY.str.hidesample = s[0];
            ESSAY.str.showsample = s[1];
            var last = $(".qtext").find("p:not(:empty), div:not(:empty)");
            if (last.length) {
                last = last.last();
            } else {
                last = $(".qtext");
            }

            last.append($("<span></span>").click(function(){
                var newtxt = "",
                    oldtxt = "",
                    saveresponse = false;
                if ($(this).hasClass("showsample")) {
                    $(this).removeClass("showsample")
                           .addClass("hidesample")
                           .text(ESSAY.str.hidesample);
                    newtxt = ESSAY.responsesample;
                    saveresponse = true;
                } else {
                    $(this).removeClass("hidesample")
                           .addClass("showsample")
                           .text(ESSAY.str.showsample);
                    newtxt = ESSAY.responseoriginal;
                }

                // Locate response element
                var editor = null;
                var qtext = $(this).closest(".qtext");
                if (ESSAY.editortype=="audio" || ESSAY.editortype=="video") {
                    editor = qtext.find(".audiovideo_response_prompt");
                } else {
                    var r = qtext.next(".ablock").find(".answer .qtype_essay_response");
                    if (r.is("[name$='_answer']")) {
                        // Plain text (i.e. no editor)
                        editor = r;
                    } else {
                        // Atto
                        editor = r.find("[contenteditable=true]");
                        if (editor.length==0) {
                            // TinyMCE
                            editor = r.find("iframe").contents().find("[contenteditable=true]");
                            if (editor.length==0) {
                                // Plain text editor
                                editor = r.find("[name$='_answer']");
                            }
                        }
                    }
                }

                if (editor===null || editor.length==0) {
                    return false; // shouldn't happen !!
                }

                if (editor.prop("tagName")=="TEXTAREA") {
                    oldtxt = editor.val();
                    editor.val(newtxt).keyup();
                } else {
                    oldtxt = editor.text();
                    editor.text(newtxt).keyup();
                }

                if (saveresponse) {
                    ESSAY.responseoriginal = oldtxt;
                }
                return true;
            }).trigger("click"));
        });
    };

    return ESSAY;
});
