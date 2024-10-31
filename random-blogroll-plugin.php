<?php
/*
Plugin Name: Random Blogroll
Version: 0.2.1
Plugin URI: http://www.gerd-riesselmann.net/categories/wordpress-plugins/
Description: Generates a randomized blogroll out of OPML feed subsriptions.
Author: Gerd Riesselmann
Author URI: http://www.gerd-riesselmann.net/
*/

/*
Copyright (C) 2005 Gerd Riesselmann

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

http://www.gnu.org/licenses/gpl.html
*/


/**
 * global subscription items collection 
 */
$rbr_items =& new rbr_LinkItems();
$rbr_formatter =& new rbr_DefaultFormatter();

define("RBR_CHACHE_FILE", "wp-content/rbr-cache/rbr_cache.inc");
define("RBR_DEBUGLEVEL", 0);


/**
 * Outputs the blogroll list
 *
 * @param String The OMPL URI
 * @param Integer The number of entries to display
 * @param Integer Frequency of updates in minutes
 * @param String The tag(s) to place before the generated anchor
 * @param String The tag(s) to place after the generated anchor
 */
function rbr_output($opmlURI, $num = 10, $freq = 60, $tagBefore = "<li>", $tagAfter = "</li>")
{
	clearstatcache();
	// Check if cache is outdated
	if ( !file_exists(RBR_CHACHE_FILE) || filemtime(RBR_CHACHE_FILE) < time() - $freq * 60)
	{
		rbr_rewriteCache($opmlURI, $num, $tagBefore, $tagAfter);
	}
	
	if ( file_exists(RBR_CHACHE_FILE) )
		include RBR_CHACHE_FILE;
	else
	{
		if (RBR_DEBUGLEVEL >= 1) echo "Writing cache file failed";
		echo "\n<!-- Randomized Blogroll: No chache file produced -->\n";
	}
}

// -----------------------------------------------
// Data structures
//
// Simple item and item collection classes.
//
// The collection class rbr_LinkItems can be iterated like this:
// for ($items->reset(); $item = $items->current(); $items->next())
//
// ------------------------------------------------


/**
 * Represents an item in the blogroll
 */
class rbr_LinkItem
{
	/**
	 * The Title
	 *
	 * @var String
	 */
	var $name = "";

	/**
	 * The Description
	 *
	 * @var String
	 */
	var $description = "";

	/**
	 * The URL
	 *
	 * @var String
	 */
	var $url = "";
	
	/**
	 * The feed URL
	 *
	 * @var String
	 */
	var $feedURL = "";

	/**
	 * Constructor
	 *
	 * @param String Name
	 * @param String Description
	 * @param String URL
	 */
	function rbr_LinkItem($name, $description, $url, $feedURL = "")
	{
		$this->name = $name;
		$this->description = $description;
		$this->url = $url;
		$this->feedURL = $feedURL;
	}

	/**
	 * Returns HTML code for link anchor
	 *
	 * @return String
	 */
	function getAnchorHTML()
	{
		return "<a href=\"".$this->url."\" title=\"".
		        htmlspecialchars($this->description, ENT_COMPAT).
		        "\" rel=\"bookmark\">".$this->name."</a>";
	}
}


/**
 * A collection of rbr_LinkItem
 */
class rbr_LinkItems
{
	/**
	 * The associative array holding the entries
	 *
	 * @var Array
	 */
	var $items = array();
	
	/**
	 * Add an entry
	 *
	 * Checks if the entry already exists and if so, does nothing.
	 *
	 * @param rbr_LinkItem
	 * @return void
	 */
	function add($item)
	{
		$key = $item->name;
		if ($key == "")
		{
			if (RBR_DEBUGLEVEL >= 1) echo "Empty name in adding item.<br/>";
			return;
		}
			
		if ( array_key_exists($key, $this->items) )
		{
			if (RBR_DEBUGLEVEL >= 1) echo "Key $key used twice in adding item.<br/>";
			return;
		}
			
		$this->items[$key] = &$item;
	}
	
	/**
	 * Returns blogroll item for given key (its title, actually)
	 *
	 * @param String The title of the item
	 * @return rbr_LinkItem
	 */
	function &get($key)
	{
		return $this->items[$key];
	}
	
	/**
	 * Returns the number of items in this collection
	 * 
	 * @return Integer
	 */
	function count()
	{
		return count($this->items);
	}
	
	/**
	 * Clears collection
	 */
	function clear()	 
	{
		$this->items = array();
	}
	
	/**
	 * Resets internal pointer for interation
	 *
	 * @return void
	 */
	function reset()
	{
		reset ($this->items);
	}
	
	/**
	 * Iterates to next item
	 *
	 * @return void
	 */
	function next()
	{
		next ($this->items);
	}
	
	/**
	 * Returns current item or FALSE, if there is none
	 *
	 * @return rbr_LinkItem FALSE if no current item
	 */
	function &current()
	{
		return  current ($this->items);
	}
	
	/**
	 * Extracts $num random items from items collection
	 *
	 * @param Integer The number of items to extract
	 * @return rbr_LinkItems New collection class
	 */
	 function &extractRandom($num)
	 {
	 	$ret =& new rbr_LinkItems();
	 	
	 	$numEntries = $this->count();
		if ($numEntries > $num)
		{
			// There is somethin to extract...
			if ( version_compare(PHP_VERSION, "4.2", "<") )
			{
				// Init random numbers
				mt_srand((double)microtime()*1000000);
			}

			$arrRandomIndexes = array_rand($this->items, $num);
			if (RBR_DEBUGLEVEL >= 3) echo "Writing cache. Count randomEntries: ".count($arrRandomIndexes)."<br/>";

			foreach($arrRandomIndexes as $key)
			{
				if (RBR_DEBUGLEVEL >= 3) echo "Random index: ".$key."</br>";
				$ret->add( $this->get($key) );
			}
		}
		else if ($numItems > 0)
		{
			// The number of items in this collection is less or equal then the
			// desired number of items. Make a copy of internal array
			$ret->items = $this->items;
		}
		else
		{
			if (RBR_DEBUGLEVEL >= 1) echo "Extracting from empty collection.<br/>";
		}
		
		return $ret;
	 }
}

// ------------------------------------------
// Formatters
//
// Formatters are used to customize the blogroll output.
// Formatters are classes providing one function which returns a string:
//
// function format(&$items, $tagBefore, $tagAfter)
//
// $items is an instance of rbr_LinkItems, actually a collection of blogroll items
// $tagBefore are the tags to place before the generated content of each blogroll item
// $tagAfter are the tags to place after the generated content of each blogroll item
//
// If you write your own formatter, you can set it by calling
// $brb_formatter = new MyCustomFormatter();
//
// One formatter is provided by default (rbr_DefaultFormatter). This does a simply output a set
// of anchors. Take this as a reference implementation
//
// -----------------------------------------

/**
 * Default Formatter.
 */
class rbr_DefaultFormatter
{
	/**
	 * Formats output
	 *
	 * @param rbr_LinkItems A collection of blogroll items
	 * @param String The tag(s) to place before the generated content of each blogroll item
	 * @param String The tag(s) to place after the generated anchor
	 * @return String
	 */
	function format(&$items, $tagBefore, $tagAfter)
	{
		$ret = "";
		
		for ($items->reset(); $item = $items->current(); $items->next())
		{
			$ret .= $tagBefore . $item->getAnchorHTML() . $tagAfter;
		}
		
		return $ret;
	}
}

// -----------------------------------------
// Helper Functions
// -----------------------------------------

/**
 * Updates the cache
 *
 * @param String The OMPL URI
 * @param Integer The number of entries to display
 * @param String The tag(s) to place before the generated anchor
 * @param String The tag(s) to place after the generated anchor
 */
function rbr_rewriteCache($opmlFile, $num, $tagBefore, $tagAfter)
{
	global $rbr_items;
	global $rbr_formatter;
	rbr_readOPML($opmlFile);

	// Write array to cache
	if ($handle = fopen(RBR_CHACHE_FILE, 'wb'))
	{
		if (RBR_DEBUGLEVEL >= 2) echo "Writing cache. Count rbr_entries: ".$rbr_items->count()."<br/>";		
		
		$extractedItems = $rbr_items->extractRandom($num);
		
		if ( $extractedItems->count() > 0)
		{
			fwrite($handle, $rbr_formatter->format($extractedItems, $tagBefore, $tagAfter));
		}
		else
		{
			fwrite($handle, $tagBefore."No blogroll entries found".$tagAfter);
		}	
		
		fclose($handle);
	}
	else
	{
		die("Not able to open file ".RBR_CACHE_FILE." for writing");
	}
}


/**
 * Processes OPML element. Invoked by XML parser
 *
 * @param int Parser ID
 * @param String Element Name
 * @param Array Atributes
 */
function rbr_opml_startElement($parser, $name, $attribs)
{
	if (RBR_DEBUGLEVEL >= 3) print "Processing element: ".$name."<br/>";
	global $rbr_items;;
		
	if ($name == "OUTLINE")
	{
		if (isset($attribs["TYPE"]) && $attribs["TYPE"] == "rss")
		{
			if (RBR_DEBUGLEVEL >= 3) print "Detecting outline<br/>";
			
			$title = "";
			$description = "";
			$url = "";
			$feedURL = "";
			
			if ( isset($attribs["TITLE"]) )
				$title = strip_tags($attribs["TITLE"]);
			else if ( isset($attribs["TEXT"]) )
				$title = strip_tags($attribs["TEXT"]);
							
 			if ( isset($attribs["HTMLURL"]) )
				$url = $attribs["HTMLURL"];

			if (isset($attribs["DESCRIPTION"]))
				$description = strip_tags($attribs["DESCRIPTION"]);

			if (isset($attribs["XMLURL"]))
				$feedURL = $attribs["XMLURL"];
				
			if (strlen($title) > 0 && strlen($url) > 0)
			{
				if (RBR_DEBUGLEVEL >= 3) echo "Creating entry instance: ".$title."<br/>";
				$rbr_items->add( new rbr_LinkItem($title, $description, $url, $feedURL) );
			}
		}
	}
}

/**
 * After processing OPML element. Invoked by XML parser
 */
function rbr_opml_endElement($parser, $name)
{
	// Does nothing
}

/**
 * Reads the OML file by invoking XML parser and fills global class $rbr_entries
 *
 * @param the OPML file's URI
 */
function rbr_readOPML($file)
{
	if (file_exists($file) == false)
		die($file." not found");
		
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
	xml_set_element_handler($xml_parser, "rbr_opml_startElement", "rbr_opml_endElement");
	if (!($fp = fopen($file, "r"))) {
	   die("could not open XML input");
	}

	while ($data = fread($fp, 4096)) {
	   if (!xml_parse($xml_parser, $data, feof($fp))) {
		   die(sprintf("XML error: %s at line %d",
					   xml_error_string(xml_get_error_code($xml_parser)),
					   xml_get_current_line_number($xml_parser)));
	   }
	}
	xml_parser_free($xml_parser);
}

?>
