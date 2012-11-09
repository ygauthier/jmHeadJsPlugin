jmHeadJsPlugin
==============

Installation
------------
Install the plugin via the following
command from the project root directory:

    $ ./symfony plugin:install jmHeadJsPlugin

Usage
-----
After installing the plugin edit your layout to include HeadJS in the head tag:

    <head>
      /* Some code here */
      <?php jm_include_headjs(); ?>
    </head> 

And at the end of your body tag simply include your java scripts like so:

      <?php jm_include_javascripts();?>
    </body>

Now you have a fully functioning application using HeadJS. If you are using Apostrophe CMS do not worry, as the plugin automatically handles minified and groups java scripts by checking the app_a_minify setting.

Currently overriding Apostrophe templates to use the head.ready function has to be handled manually but will be included in the next release in the plugin. Files that would have to manually overriden are:

  - a/templates/_globalJavaScripts.php
  - aMedia/templates/_addForm.php