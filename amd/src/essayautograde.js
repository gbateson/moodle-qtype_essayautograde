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
            ESSAY.setup_read_only_files();
            ESSAY.setup_response_heights();
        } else {
            ESSAY.setup_itemcounts();
            ESSAY.setup_responsesample(responsesample);
        }
    };

    ESSAY.setup_read_only_files = function() {
       STR.get_strings([
            {"key": "rotate",   "component": ESSAY.plugin},
            {"key": "scale",    "component": ESSAY.plugin},
            {"key": "overflow", "component": ESSAY.plugin},
            {"key": "auto",     "component": ESSAY.plugin},
            {"key": "hidden",   "component": ESSAY.plugin},
            {"key": "visible",  "component": ESSAY.plugin},
            {"key": "actions",  "component": "moodle"},
            {"key": "crop",     "component": ESSAY.plugin},
            {"key": "reset",    "component": "moodle"},
            {"key": "save",     "component": "moodle"}
        ]).done(function(s){
            var i = 0;
            ESSAY.str.rotate   = s[i++];
            ESSAY.str.scale    = s[i++];
            ESSAY.str.overflow = s[i++];
            ESSAY.str.auto     = s[i++];
            ESSAY.str.hidden   = s[i++];
            ESSAY.str.visible  = s[i++];
            ESSAY.str.actions  = s[i++];
            ESSAY.str.crop     = s[i++];
            ESSAY.str.reset    = s[i++];
            ESSAY.str.save     = s[i++];

            document.querySelectorAll(".attachments .read-only-file.image .img-responsive").forEach(function(img){
                if (img.dataset.buttons_added) {
                    return true;
                }
                img.dataset.buttons_added = true;

                // remove border, margin and padding from img
                img.classList.add("border-0");
                img.classList.add("m-0");
                img.classList.add("p-0");

                // Create a container DIV for the image
                var container = document.createElement("DIV");
                container.classList.add("border-0");
                container.classList.add("m-0");
                container.classList.add("p-0");
                container.classList.add("position-relative");
                container.classList.add("image-container");

                // Insert the container DIV into the document
                // and move the image into the container.
                img.parentNode.insertBefore(container, img);
                container.appendChild(img);

                // Set transition duration now, so that all
                // transform() transitions are smooth.
                img.style.transitionDuration = "1s";
                container.style.transitionDuration = "1s";

                if (img.complete) {
                    ESSAY.set_image_dimensions(img, container);
                } else {
                    img.onload = function(){
                        ESSAY.set_image_dimensions(this, this.parentNode);
                    };
                }

                // Create <div> for all buttonsets
                var buttonsets = document.createElement("DIV");
                buttonsets.classList.add("border-0");
                buttonsets.classList.add("m-0");
                buttonsets.classList.add("p-0");
                buttonsets.classList.add("buttonsets");

                // Create <div> for overflow buttonset
                var type = "overflow";
                var label = ESSAY.str[type];
                var buttonset = new Array();
                var values = new Array("visible", "auto", "hidden");
                for (var i in values) {
                    var value = values[i];
                    var txt = ESSAY.str[value];
                    var dataset = {"value": value};
                    buttonset.push(ESSAY.create_button(type, txt, dataset, ESSAY.transform_image));
                }

                // Add overflow buttonset to the buttonsets DIV
                buttonsets.appendChild(ESSAY.create_buttonset(type, label, buttonset));

                // Create <div> for rotate buttonset
                var type = "rotate";
                var label = ESSAY.str[type];
                var buttonset = new Array();
                var values = new Array(0, 90, 180, 270);
                for (var i in values) {
                    var value = values[i];
                    var txt = value + "\u00B0";
                    // "\u00B0" is a degrees symbol: °
                    var dataset = {"value": value};
                    buttonset.push(ESSAY.create_button(type, txt, dataset, ESSAY.transform_image));
                }

                // Add rotate buttonset to the buttonsets DIV
                buttonsets.appendChild(ESSAY.create_buttonset(type, label, buttonset));

                // Create <div> for scale buttonset
                var type = "scale";
                var label = ESSAY.str[type];
                var buttonset = new Array();
                var values = new Array(0.5, 1, 1.5, 2);
                for (var i in values) {
                    var value = values[i];
                    var txt = "\u00D7" + value;
                    // "\u00D7" is a multiplication sign: ×
                    var dataset = {"value": value};
                    buttonset.push(ESSAY.create_button(type, txt, dataset, ESSAY.transform_image));
                }

                // Add scale buttonset to the buttonsets DIV
                buttonsets.appendChild(ESSAY.create_buttonset(type, label, buttonset));

                // Create <div> for actions buttonset
                //var type = "actions";
                //var label = ESSAY.str[type];
                //var buttonset = new Array();
                //var values = new Array("crop", "reset", "save");
                //for (var i in values) {
                //    var value = values[i];
                //    var txt = ESSAY.str[value];
                //    var dataset = {"value": value};
                //    buttonset.push(ESSAY.create_button(type, txt, dataset, ESSAY.transform_image));
                //}

                // Add actions buttonset to the buttonsets DIV
                //buttonsets.appendChild(ESSAY.create_buttonset(type, label, buttonset));

                // Insert buttonsets after the IMG container
                container.parentNode.insertBefore(buttonsets, container.nextElementSibling);
            });
        });
    };

    ESSAY.set_image_dimensions = function(img, container) {

        // cache original height and width of img
        img.dataset.width = img.offsetWidth;
        img.dataset.height = img.offsetHeight;
        img.dataset.offset = (img.offsetWidth - img.offsetHeight);
        img.dataset.ratio = (img.offsetWidth / img.offsetHeight);

        // cache original height and width of container
        container.dataset.width = container.offsetWidth;
        container.dataset.height = container.offsetHeight;

        // Set the initial height and width of the img container so that
        // it doesn't collapse when we set the img to absolute positioning.
        container.style.height = container.dataset.height+ "px";
        container.style.width = container.dataset.width + "px";
        img.style.position = "absolute";

        img.setAttribute("draggable", "true");
        img.addEventListener("dragstart", ESSAY.image_dragstart, false);
        img.addEventListener("drag", ESSAY.image_drag, false);
        img.addEventListener("dragend", ESSAY.image_dragend, false);
    };

    ESSAY.image_dragstart = function(evt) {
        this.style.transitionDuration = "0s";
        // Cache the current coordinates of the mouse and image.
        this.dataset.evtPageY = evt.pageY;
        this.dataset.evtPageX = evt.pageX;
        this.dataset.imgOffsetTop = this.offsetTop;
        this.dataset.imgOffsetLeft = this.offsetLeft;
    };

    ESSAY.image_drag = function(evt) {
        if (evt.pageX && evt.pageY) {
            // Move the image by the same amount as the mouse
            // has moved from its original position
            var offsetY = (evt.pageY - this.dataset.evtPageY);
            var offsetX = (evt.pageX - this.dataset.evtPageX);
            var offsetTop = parseInt(this.dataset.imgOffsetTop);
            var offsetLeft = parseInt(this.dataset.imgOffsetLeft);
            this.style.top = (offsetTop + offsetY) + "px";
            this.style.left = (offsetLeft + offsetX) + "px";
        }
    };

    ESSAY.image_dragend = function(evt) {
        this.style.transitionDuration = "1s";
        if (evt.preventDefault) {
            evt.preventDefault();
        }
        if (evt.stopPropagation) {
            evt.stopPropagation();
        }
        return false;
    };

    ESSAY.create_buttonset = function(type, txt, buttons) {
        var buttonset = document.createElement("DIV");
        buttonset.classList.add("bg-secondary");
        buttonset.classList.add("rounded");
        buttonset.classList.add("mt-1");
        buttonset.classList.add("pl-1");
        buttonset.classList.add("buttonset");
        if (type) {
            buttonset.classList.add(type + "-buttonset");
        }
        if (txt) {
            var span = document.createElement("SPAN");
            span.classList.add("font-weight-bold");
            span.appendChild(document.createTextNode(txt + ":"));
            buttonset.appendChild(span);
        }
        if (buttons) {
            for (var i in buttons) {
                buttonset.appendChild(buttons[i]);
            }
        }
        return buttonset;
    };

    ESSAY.create_button = function(type, txt, dataset, fn) {
        var button = document.createElement("BUTTON");
        button.setAttribute("type", "button");
        button.classList.add("button");
        button.classList.add("button-light");
        button.classList.add("border");
        button.classList.add("rounded");
        button.classList.add("my-1");
        button.classList.add("ml-1");
        button.classList.add("mr-0");
        button.classList.add("px-2");
        if (type) {
            button.classList.add(type + "-button");
        }
        if (txt) {
            button.appendChild(document.createTextNode(txt));
        }
        if (dataset) {
            for (var name in dataset) {
                button.dataset[name] = dataset[name];
            }
        }
        if (fn) {
            button.addEventListener("click", fn, false);
        }
        return button;
    };

    ESSAY.transform_image = function() {
        var buttonset = this.parentNode;
        if (! buttonset.matches(".buttonset")) {
            return false;
        }

       // Deselect ALL buttons in this buttonset
        buttonset.querySelectorAll(".btn-info").forEach(function(button){
            ESSAY.deselect_button(button);
        });

        // Select (=highlight) this button.
        ESSAY.select_button(this);

        // locate buttonsets
        var buttonsets = buttonset.parentNode;
        if (! buttonsets.matches(".buttonsets")) {
            return false;
        }

        // locate image container
        var container = buttonsets.previousElementSibling;
        if (! container.matches(".image-container")) {
            return false;
        }

        // locate image
        var img = container.querySelector("img");
        if (! img) {
            return false;
        }

        var reset = (this.dataset.value == "reset");

        var overflow = "visible"; // default value of overflow
        var button = buttonsets.querySelector('.overflow-buttonset .btn-info');
        if (button) {
            if (reset) {
                button = ESSAY.set_default_button(button, "overflow", overflow);
            }
            overflow = button.dataset.value;
        }

        var rotate_angle = 0;
        var button = buttonsets.querySelector('.rotate-buttonset .btn-info');
        if (button) {
            if (reset) {
                button = ESSAY.set_default_button(button, "rotate", rotate_angle);
            }
            rotate_angle = button.dataset.value;
        }

        var scale_factor = 1;
        var button = buttonsets.querySelector('.scale-buttonset .btn-info');
        if (button) {
            if (reset) {
                button = ESSAY.set_default_button(button, "scale", scale_factor);
            }
            scale_factor = button.dataset.value;
        }

        var t = img.style.transform;
        t = t.replace(new RegExp(" *(rotate|scale|translate) *\\([^)]*\\)", "g"), "");
        if (rotate_angle) {
            t += " rotate(" + rotate_angle + "deg)";
        }

        if (rotate_angle == 90 || rotate_angle ==  270) {
            if (overflow == "visible") {
                scale_factor *= img.dataset.ratio;
            }
            var offset = (img.dataset.offset * scale_factor);
            if (rotate_angle == 270) {
                offset = (-offset);
            }
            t += " translate(" + (offset/2) + "px, " + (offset/2) + "px)";
        }

        img.style.transform = t.trim();
        img.style.maxWidth = "initial"; // override "100%"
        img.style.width = (img.dataset.width * scale_factor) + "px";

        var h = 0;
        if (rotate_angle == 90 || rotate_angle ==  270) {
            h = container.dataset.width;
        } else {
            h = container.dataset.height;
        }
        if (overflow == "visible" || scale_factor < 1) {
            h *= scale_factor;
        }

        container.style.overflow = overflow;
        container.style.height = h + "px";

        if (reset) {
            // deselect the RESET button
            ESSAY.deselect_button(this);
        }

    };

    ESSAY.select_button = function(button){
        button.classList.remove("btn-light");
        button.classList.add("btn-info");
    };

    ESSAY.deselect_button = function(button){
        button.classList.remove("btn-info");
        button.classList.add("btn-light");
    };

    ESSAY.set_default_button = function(button, type, value){
        if (button.dataset.value == value) {
            return button; // nothing to do
        }

        // Deselect the currently selected button.
        ESSAY.deselect_button(button);

        // Locate and highlight the default button.
        button = button.parentNode.querySelector('.' + type + '-button[data-value="' + value + '"]');
        if (button) {
            button.classList.remove("btn-light");
            button.classList.add("btn-info");
        }

        // Return default button
        return button;
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
                    var i = 0;
                    ESSAY.str.maxwordslabel   = s[i++];
                    ESSAY.str.maxwordswarning = s[i++];
                    ESSAY.str.minwordslabel   = s[i++];
                    ESSAY.str.minwordswarning = s[i++];
                    ESSAY.str.countwordslabel = s[i++];

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
            var i = 0;
            ESSAY.str.hidesample = s[i++];
            ESSAY.str.showsample = s[i++];
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

