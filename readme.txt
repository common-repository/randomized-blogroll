Randomized Blogroll Plugin Readme

The plugin was tested on WordPress 1.2.2 and the latest Beta of WordPress 1.5 
on both Windows and Linux.

A) Installation

1. Copy the file "random-blogroll-plugin.php" to the folder "wp-content/plugins"
2. Create a folder named "rbr-cache" in the "wp-content" directory. Make sure it 
   is writable by the server (chmod 0777). 
3. Upload the OPML file that contains your subsriptions to the server (e.g. to 
   "wp-content"). If your subscription is provided by a server like technorati.com, 
   you can skip this step. Read section C of this readme, though.
4. Hack your site to display the blogroll.

   For WordPress 1.2.x:

   Add the following lines to your "index.php", right where the other links are 
   located. 
   Beneath the section entitled archives would be a good place.
	 <?php 
	 if (function_exists("rbr_output"))
	 { 
	 ?>
	   <li id="blogroll"><?php _e('Blogs I read'); ?>
	   <ul>
	   <?php rbr_output(OPML-FILE-NAME); ?>
	   </ul>
	   </li>
	 <?php 
	 } 
	 ?>

   For WordPress 1.3 and higher:

   The links are located in the file "wp-content/themes/[theme name]/sidebar.php".
   If you use the classic theme, add the same code like above, for example before 
   the section entitled "Meta" to the file "wp-content/themes/classic/sidebar.php".
   
   If you use the default theme, the code must be slighly modified to fit the theme:
  	  <?php 
  	  if (function_exists("rbr_output"))
	  { 
	  ?>
	    <li id="blogroll"><h2><?php _e('Blogs I read'); ?></h2>
	    <ul>
	    <? rbr_output(OPML-FILE-NAME); ?>
	    </ul>
	    </li>
	  <?php 
	  } 
	  ?>
   You can place it before the "Meta" section in the file "wp-content/themes/default/sidebar.php".

5. Replace OPML-FILE-NAME with the path to the OPML file you uploaded before or the URL 
   if your subscriptions are taken from another server.
6. Enable the plugin in your WordPress administration environment.


B) Details

The function rbr_output takes the following parameters:

OPML-FILE-NAME: The name of your opml file to be read or its URL. E.g. "wp-content/export.xml" 
or "http://some-blogroll-provider/my-subscriptions.xml".
NUMBER-OF-ENTRIES: Optional. The number of entries to display, default is 10.
REFRESH-TIME-IN-MINUTES: Optional. Defines after how many minutes, the randomized roll should be 
updated. Default is 60 minutes.
START-TAG: Optional. Anything you would like to see before each generated anchor tag. Default is "<li>".
END-TAG: Optional. Anything you would like to see after each generated anchor tag. Default is "</li>".


C) Bugs And Inconveniences

The plugin requires your OPML file to provide the attributes "htmlUrl" and either "title" or 
"text" for each of your subscriptions. It was successfully testes with plugins from SharpReader 
and BlogLines.com.

Unfortunately some feed readers (like FeedReader) don't provide the "htmlUrl" attribute and OPML 
files exported from this applications are not processed. If the plugin generates an empty list 
saying "No blogroll entries found", your OPML file may not be as complete as it could be. 

In this case import your OPML into another feed reader and reexport it, for example using SharpReader 
(http://www.sharpreader.net/), which is free. 

If you take the subsriptions from a server like technorati.com and an error occurs saying the file 
could not be found, this possibly is because you are not allowed to access files on other servers. 
This is due the the PHP init parameter allow_url_open set to 0. Contact your administrator in this 
case or download the subscription file and upload it on your server.

If you find any other bugs, please report them at http://dev.wp-plugins.org/newticket