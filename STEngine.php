<?php
/**
 * STEngine.php — Saturn utility class (File handling)
 * Saturn — Simple PHP/Markdown blog generator
 * Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
 *
 * @package Saturn
 * @author Cesar Parent <cesar@cesarparent.com>
 * @copyright Copyright (c) 2014, Cesar Parent
 * @version 1.0-alpha1
 * @license https://github.com/cesarparent/saturn-generator/blob/master/LICENSE MIT License
 */

namespace Saturn;

define("SATURN_POST", 0);
define("SATURN_PAGE", 1);

use \Exception as Exception;

/**
 * Saturn engine class. provides abstraction methods to access posts and pages
 * stored on the blog
 */
class Engine
{
	/**
	 * @var mixed[] $blog the options of the blog
	 */
	public $blog;
	/**
	 * @var string $posts the directory in which posts are stored
	 */
	public static $posts = "/content/posts/";
	/**
	 * @var string $pages the directory in which posts are stored
	 */
	public static $pages = "/content/pages/";
	/**
	 * @var string $version Saturn's version number
	 */
	public $version = "1.0.0a1";

	/**
	 * Constructor. Loads the options file
	 *
	 * @return Engine a new instance of the Engine class
	 */
	public function __construct()
	{
		require(__DIR__."/STOptions.php");
		$this->blog = $options;
	}

	/*
	 ***************************************************************************
	 * Posts and Pages loading
	 ***************************************************************************
	 */

	/**
	 * Loads a post from an id and returns its content and metadata
	 * in an array.
	 *
	 * @param string $post_id the slug of the post to load
	 * @return mixed[]|false post if the post exists, false otherwise.
	 */
	public function load_post($post_id)
	{
		$filename = __DIR__.self::$posts.$post_id.".md";
		$post = $this->load_file($filename);
		if(!$post) return false;
		list($year, $month, $day, $slug) = explode("-", $post_id, 4);
		if(isset($post["date"]))
		{
			$post["date"] = strtotime($post["date"]);
		}
		else
		{
			$post["date"] = mktime("10", "00", "00", $month, $day, $year);
		}
		if(isset($post["tags"]) && $post["tags"] !== "")
		{
			$post["tags"] = preg_split("/[ *]?,[ *]?/", $post["tags"]);
		}
		else
		{
			$post["tags"] = [];
		}
		$post["lastmod"] = filemtime($filename);
		$post["permalink"] = "/".$year."/".$month."/".$slug;
		return $post;
	}

	/**
	 * Loads a static page from an id and returns its content and metadata
	 * in an array.
	 *
	 * @param string $page_id the slug of the page to load
	 * @return mixed[]|false page if the page exists, false otherwise
	 */
	public function load_page($page_id)
	{
		$filename = __DIR__.self::$pages.$page_id.".md";
		$page = $this->load_file($filename);
		if(!$page) return false;
		$page["date"] = filemtime($filename);
		$page["permalink"] = "/".$page_id;
		return $page;
	}

	/**
	 * Loads a post/page file, and parses its basic content to an array
	 *
	 * @param string $filename the path to the file to open
	 * @return mixed[]|false an array if the entry exists, false otherwise
	 */
	private function load_file($filename)
	{
		if(!file_exists($filename)) return false;
		$source = file_get_contents($filename);
		if(!$source) throw new Exception("Error reading ".$filename);
		list($headers,$content) = explode("\n\n", $source, 2);
		$post = $this->parse_headers($headers);
		$post["content"] = $content;
		return $post;
	}

	/*
	 ***************************************************************************
	 * Posts and Pages writing
	 ***************************************************************************
	 */

	/**
	 * Writes a new post file
	 *
	 * @param string $title the title of the post
	 * @param string[] $tags an array of tags for the psot
	 * @param string $content the content of the post
	 * @param int $date optional timestamp for the post. uses time() otherwise.
	 * @return string the unique id of the created post
	 */
	public function add_post($title, array $tags, $content, $date = null)
	{
		$metadata = [
			"title" => $title,
			"tags" => implode(", ", $tags),
		];
		if($date === null)
		{
			$metadata["date"] = time();
		}
		else
		{
			$metadata["date"] = $date;
		}
		return $this->write_file($metadata, $content, SATURN_POST);
	}

	/**
	 * Writes a new static page file
	 *
	 * @param string $title the title of the static page
	 * @param string $content the content of the static page
	 * @return string the unique id of the created page
	 */
	public function add_page($title, $content)
	{
		$metadata = [
			"title" => $title,
		];
		return $this->write_file($metadata, $content, SATURN_PAGE);
	}

	/**
	 * Replace the data of the given post with the passed array
	 *
	 * @param int $type the post type (SATURN_POST or SATURN_PAGE)
	 * @param string $slug the slug of the entry to replace
	 * @param mixed[] $entry the entry's new content and metadata
	 * @return void
	 */
	public function edit_entry($type, $slug, array $entry)
	{
		if($type === SATURN_POST)
		{
			$filename = __DIR__.self::$posts.$slug.".md";
			$entry["date"] = date("Y-m-d H:i:s", $entry["date"]);
		}
		else if($type === SATURN_PAGE)
		{
			$filename = __DIR__.self::$pages.$slug.".md";
		}
		else
		{
			throw new Exception("Invalid entry type");
		}
		$content = $entry["content"];
		unset($entry["lastmod"]);
		unset($entry["content"]);
		unset($entry["permalink"]);

		$raw_data = $this->dump_headers($entry)."\n".$content;
		if(!file_put_contents($filename, $raw_data))
		{
			throw new Exception("Error while writing file '".$filename."'.");
		}
		return $filename;
	}

	/**
	 * Creates a filename and write a post or page file to the disk
	 *
	 * @param mixed[] $metadata the metadata array for the entry
	 * @param string $content the content of the entry
	 * @param int $type the post type (SATURN_POST or SATURN_PAGE)
	 * @return void
	 */
	private function write_file(array $metadata, $content, $type)
	{
		if($type === SATURN_POST)
		{
			$filename = date("Y-m-d-", $metadata["date"]);
			$metadata["date"] = date("Y-m-d H:i:s", $metadata["date"]);
		}
		else if($type === SATURN_PAGE)
		{
			$filename = "";
		}
		else
		{
			throw new Exception("Invalid entry type.");
		}
		$filename .= $this->slug_from_title($metadata["title"]);
		if(file_exists(__DIR__.self::$posts.$filename.".md"))
		{
			$suffix = 2;
			while(file_exists(__DIR__.self::$posts.$filename."-".$suffix.".md"))
			{
				$suffix++;
			}
			$filename = $filename."-".$suffix;
		}
		$raw_data = $this->dump_headers($metadata)."\n".$content;
		if(!file_put_contents(__DIR__.self::$posts.$filename.".md", $raw_data))
		{
			throw new Exception("Error while writing file '".$filename."'.");
		}
		return $filename;
	}

	/**
	 * Converts a title to a lowercase, url-safe string
	 *
	 * @param string $title the title to convert
	 * @return string a url-safe slug to use in filenames
	 */
	private function slug_from_title($title)
	{
		$slug = strtolower($title);
		$slug = preg_replace("/[^a-zA-Z0-9_-]+/", "-", $slug);
		$slug = preg_replace("/-$|^-/", "", $slug);
		return $slug;
	}

	/**
	 * Deletes a post or page source file
	 *
	 * @param int $type the type (SATURN_POST|SATURN_PAGE) of the entry
	 * @param string $id the unique ID of the entry
	 * @return boolean true if the post was deleted, false otherwise
	 */
	public function delete_entry($type, $id)
	{
		if($type === SATURN_POST)
		{
			$filename = __DIR__.self::$posts.$id.".md";
		}
		else if($type === SATURN_PAGE)
		{
			$filename = __DIR__.self::$pages.$id.".md";
		}
		if(!file_exists($filename)) return false;
		if(!unlink($filename)) throw new Exception("Error deleting ".$filename);
		return true;
	}

	/*
	 ***************************************************************************
	 * Headers parsing
	 ***************************************************************************
	 */

	/**
	 * Parses a HTTP-like headers string and returns a key/value array
	 *
	 * @param string $headers_string the headers
	 * @return mixed[] the keys and values contained in the headers
	 */
	private function parse_headers($headers_string)
	{
		$headers = [];
		$key = $value = "";
		foreach(explode(PHP_EOL, $headers_string) as $line)
		{
			if (strpos($line,": ") !== false) {
				list($key, $value) = explode(": ", $line, 2);
				$headers[$key] = $value;
			}
		}
		return $headers;
	}

	/**
	 * Dumps a key/value array as an HTTP-like headers string
	 *
	 * @param mixed[] $headers the array to dump
	 * @return string the string representation of the headers
	 */
	private function dump_headers(Array $headers)
	{
		$headers_string = "";
		foreach($headers as $key => $value)
		{
			if(!is_object($value) && !is_array($value))
			{
				$headers_string .= $key.": ".$value."\n";
			}
		}
		return $headers_string;
	}

	/**
	 * List entry files of a certain type
	 *
	 * @param int $type type of entries to list
	 * @param int $limit optional limit to the size of the list
	 * @return string[] a list of filenames (without extensions)
	 */
	public function slug_list($type, $limit = null) {
		$files = [];
		$length = 0;
		if($type === SATURN_POST)
		{
			$path = __DIR__.self::$posts;
		}
		else if($type === SATURN_PAGE)
		{
			$path = __DIR__.self::$pages;
		}
		else
		{
			throw new Exception("Invalid entry type");
		}
		if(($dir = opendir($path)) === false)
		{
			throw new Exception("Error while opening directory ".$dir);
		}
		while(($filename = readdir($dir)) !== false)
		{
			if($filename{0} == '.' ||
				pathinfo($filename, PATHINFO_EXTENSION) != "md") {
				continue;
			}
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$files[] = str_replace(".".$extension, "", $filename);
		}
		closedir($dir);
		rsort($files);
		return ($limit === null)? $files : array_splice($files, 0, $limit);
	}
}
