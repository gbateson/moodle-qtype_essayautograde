// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
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
 * support for the mdl35+ mobile app. PHP calls this from within
 * classes/output/mobile.php
 * This file is the equivalent of 
 * qtype/YOURQTYPENAME/classes/YOURQTYPENAME.ts in the core app
 * e.g.
 * https://github.com/moodlehq/moodlemobile2/blob/v3.5.0/src/addon/qtype/ddwtos/classes/ddwtos.ts
 */

var that = this;
var result = {
    componentInit: function() {

        // Check that "this.question" was provided.
        if (! this.question) {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Create a temporary div to ease extraction of parts of the provided html.
        var div = document.createElement('div');
        div.innerHTML = this.question.html;

        // Replace Moodle's correct/incorrect classes, feedback and icons with mobile versions.
        that.CoreQuestionHelperProvider.replaceCorrectnessClasses(div);
        that.CoreQuestionHelperProvider.replaceFeedbackClasses(div);
        that.CoreQuestionHelperProvider.treatCorrectnessIcons(div);

        // Get useful parts of the provided question html data.
        var text = div.querySelector('.qtext');
        if (text) {
            this.question.text = text.innerHTML;
        }

        var textarea = div.querySelector('.answer textarea');
        if (textarea === null) {
            // review or check
            textarea = div.querySelector('.answer .qtype_essay_response');
        }
        if (textarea) {
            textarea.style.borderRadius = '4px';
            textarea.style.padding = '6px 12px';
            if (textarea.matches('.readonly')) {
                textarea.style.border = '2px #b8dce2 solid'; // light blue
                textarea.style.backgroundColor = '#e7f3f5'; // lighter blue
            } else {
                textarea.style.backgroundColor = '#edf6f7'; // lightest blue
            }
            this.question.textarea = textarea.outerHTML;
        }

        var itemcount = div.querySelector('.itemcount');
        if (itemcount) {

            // Replace bootstrap styles with inline styles because
            // adding styles to 'mobile/styles_app.css' doesn't seem to be effective :-(

            itemcount.querySelectorAll('p').forEach(function(p){
                if (p.classList.contains('mt-2')) {
                    p.classList.remove('mt-2');
                    p.style.marginTop = '0.5rem';
                }
                if (p.classList.contains('mb-0')) {
                    p.classList.remove('mb-0');
                    p.style.marginBottom = '0';
                }
                if (p.classList.contains('my-0')) {
                    p.classList.remove('my-0');
                    p.style.marginBottom = '0';
                    p.style.marginTop = '0';
                }
            });

            // Fix background and text color on "wordswarning" span.
            var elm = itemcount.querySelector(".wordswarning");
            if (elm) {
                elm.classList.remove('rounded');
                elm.style.borderRadius = '0.25rem';

                elm.classList.remove('bg-danger');
                elm.style.backgroundColor = '#ca3120';
                
                elm.classList.remove('text-light');
                elm.style.color = '#f8f9fa';

                elm.classList.remove('ml-2');
                elm.style.marginLeft = '0.5rem';

                elm.classList.remove('px-2');
                elm.classList.remove('py-1');
                elm.style.padding = '0.25rem 0.5rem';

                if (elm.classList.contains('d-none')) {
                    elm.classList.remove('d-none');
                    elm.style.display = 'none';
                }
            }

            this.question.itemcount = itemcount.outerHTML;
        }

        /**
         * questionRendered
         */
        this.questionRendered = function(){

            var textarea = this.componentContainer.querySelector('textarea');
            var itemcount = this.componentContainer.querySelector('.itemcount');
            if (textarea && itemcount) {

                var p = this.CoreLangProvider;
                var minwordswarning = this.get_plugin_string(p, 'qtype_essayautograde', 'minwordswarning');
                var maxwordswarning = this.get_plugin_string(p, 'qtype_essayautograde', 'maxwordswarning');

                var countwords = itemcount.querySelector('.countwords');
                var countwordsvalue = countwords.querySelector('.value');
                var wordswarning = countwords.querySelector('.wordswarning');

                var itemtype = itemcount.dataset.itemtype;
                var minwords = parseInt(itemcount.dataset.minwords);
                var maxwords = parseInt(itemcount.dataset.maxwords);

                var itemmatch = '';
                switch (itemtype) {
                    case 'chars': itemmatch = '.'; break;
                    case 'words': itemmatch = '\\w+'; break;
                    case 'sentences': itemmatch = '[^\\.?!]+[\\.?!]'; break;
                    case 'paragraphs': itemmatch = '[^\\r\\n]+[\\r\\n]*'; break;
                }

                if (itemmatch) {
                    itemmatch = new RegExp(itemmatch, 'g');
                    textarea.addEventListener('keyup', function() {
                        var warning = '';
                        var count = 0;
                        if (textarea.value) {
                            count = textarea.value.match(itemmatch).length;
                            if (minwords && (count < minwords)) {
                                warning = minwordswarning;
                            }
                            if (maxwords && (count > maxwords)) {
                                warning = maxwordswarning;
                            }
                        }
                        countwordsvalue.innerText = count;
                        wordswarning.innerText = warning;
                        if (warning == '') {
                            wordswarning.style.display = 'none';
                        } else {
                            wordswarning.style.display = 'inline';
                        }
                    });
                }
            }
        };

        /**
         * get_plugin_string
         *
         * @param {object} p a reference to this.CoreLangProvider
         * @param {string} component a full plugin name
         * @param {string} name of the required string
         */
        this.get_plugin_string = function(p, component, name) {
            return this.get_string(p, 'plugin', component, name);
        };

        /**
         * get_string
         *
         * @param {object} p a reference to this.CoreLangProvider
         * @param {string} type, either "core" or "plugin"
         * @param {string} component a full plugin name
         * @param {string} name of the required string
         */
        this.get_string = function(p, type, component, name) {
            var langs = new Array(p.getCurrentLanguage(),
                                  p.getParentLanguage(),
                                  p.getFallbackLanguage(),
                                  p.getDefaultLanguage());
            for (var i = 0; i < langs.length; i++) {
                var s = this.get_lang_string(p, langs[i], type, component, name);
                if (s) {
                    return s;
                }
            }
            return '';
        };

        /**
         * get_lang_string
         *
         * @param {object} p a reference to this.CoreLangProvider
         * @param {string} lang, the required language
         * @param {string} type, either "core" or "plugin"
         * @param {string} component a full plugin name
         * @param {string} name of the required string
         */
        this.get_lang_string = function(p, lang, type, component, name) {
            var strings = p.sitePluginsStrings;
            var n = type + '.' + component + '.' + name;
            if (strings[lang] && strings[lang][n]) {
                return strings[lang][n]['value'];
            }
        };

        if (text && textarea) {
            return true;
        }

        // Oops, the expected elements were not found !!
        return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
    }
};

// This next line is required as is (because of an eval step that puts this result object into the global scope).
result;
