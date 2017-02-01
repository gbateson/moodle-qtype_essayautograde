============================================
The Essay (autograde) question type for Moodle >= 3.1
============================================

The essay (autograde) question type allows an essay question response to be given
a preliminary grade that is generated automatically based on one or more of the
following characteristics of the response.

    * the number of words in the response

    * the number of characters in the response

    * the presence of one or more target phrases in the response

The automatic grade can be overridden by the teacher later.

Additionally, the teacher can set up grading bands that offer a non-linear grading
scheme. In such a scheme, the grade awarded is that of the grading band in which
the word/character count falls.

============================================
To INSTALL or UPDATE this plugin
============================================

    1. get the files for this plugin from any one of the following locations:

        (a) GIT: https://github.com/gbateson/moodle-qtype_essayautograde.git
        (b) zip: the Moodle.org -> Plugins repository (search for "essayautograde")
        (c) zip: http://bateson.kanazawa-gu.ac.jp/zip/plugins_qtype_essayautograde.zip

       If you are installing from a zip file, unzip the zip file, to create a folder
       called "essayautograde" and upload or move this folder into the "question/type" folder
       on your Moodle >= 3.1 site, to create a new folder at "question/type/essayautograde".

    2. log in to Moodle as administrator to initiate install/upgrade

        if install/upgrade does not begin automatically, you can initiate it manually
        by navigating to the following link:
        Administration -> Site administration -> Notifications

============================================
Further information
============================================

    For more information and online discussion forums, please visit:
    https://github.com/gbateson/moodle-qtype_essayautograde
