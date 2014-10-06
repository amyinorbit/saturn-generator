<?php
/*
** LNDEngine.php — Generator utility class (File handling)
** London — Simple PHP/Markdown blog generator
** Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
*/

namespace London;

define("LONDON_POST", 0);
define("LONDON_PAGE", 1);

class Engine
{
	private $blog; // options array
	public static $posts = "/content/posts/";
	public static $pages = "/content/pages/";
	
	/**
	** Contructor. Loads the options file
	**
	** @return Engine a new instance of the Engine class
	*/
	public function __construct()
	{
		require_once(__DIR__."/LNDOptions.php");
		$this->blog = $options;
	}
	
	/*
	****************************************************************************
	** Posts and Pages loading
	****************************************************************************
	*/
	
	/**
	** Loads a post from an id and returns its content and metadata
	** in an hash.
	**
	** @param string $post_id the slug of the post to load
	** @return hash $post the loaded post.
	*/
	public function load_post($post_id)
	{
		$filename = __DIR__.self::$posts.$post_id.".md";
		$post = $this->load_file($filename);
		list($year, $month, $day, $slug) = explode("-", $post_id, 4);
		if(isset($post["date"]))
		{
			$post["date"] = strtotime($post["date"]);
		}
		else
		{
			$post["date"] = mktime("10", "00", "00", $month, $day, $year);
		}
		if(isset($post["tags"]))
		{
			$post["tags"] = preg_split("/[ *]?,[ *]?/", $post["tags"]);
		}
		else
		{
			$post["tags"] = [];
		}
		$post["lastmod"] = filemtime($filename);
		$post["permalink"] = $this->blog["url"]."/".$year."/".$month."/".$slug;
		return $post;
	}
	
	/**
	** Loads a static page from an id and returns its content and metadata
	** in an hash.
	**
	** @param string $page_id the slug of the page to load
	** @return hash $page the loaded page.
	*/
	public function load_page($page_id)
	{
		$filename = __DIR__.self::$pages.$page_id.".md";
		$page = $this->load($filename);
		$page["date"] = filemtime($filename);
		return $page;
	}
	
	/**
	** Loads a post/page file, and parses its basic content to an hash
	**
	** @param string $filename the path to the file to open
	** @return hash an array containing the parsed content and the metadata.
	*/
	private function load_file($filename)
	{
		$source = file_get_contents($filename);
		if($source === false) throw new \Exception("Unable to open ".$filename);
		list($headers,$content) = explode("\n\n", $source, 2);
		$post = $this->parse_headers($headers);
		$post["content"] = $content;
		return $post;
	}
	
	/*
	****************************************************************************
	** Posts and Pages writing
	****************************************************************************
	*/
	
	/**
	** Writes a new post file
	**
	** @param string $title the title of the post
	** @param string[] $tags an array of tags for the psot
	** @param string $content the content of the post
	** @param int $date optional timestamp for the post. uses time() otherwise.
	** @return void
	*/
	public function add_post($title, $tags, $content, $date = null)
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
		$this->write_file($metadata, $content, LONDON_POST);
	}
	
	/**
	** Writes a new static page file
	**
	** @param string $title the title of the static page
	** @param string $content the content of the static page
	** @return void
	*/
	public function add_page($title, $content)
	{
		$metadata = [
			"title" => $title,
		];
		$this->write_file($metadata, $content, LONDON_PAGE);
	}
	
	/**
	** Creates a filename and write a post or page file to the disk
	**
	** @param hash $metadata the metadata array for the entry
	** @param string $content the content of the entry
	** @param int $type the post type (LONDON_POST or LONDON_PAGE)
	** @return void
	*/
	private function write_file($metadata, $content, $type)
	{
		if($type === LONDON_POST)
		{
			$filename = __DIR__.self::$posts.date("Y-m-d-", $metadata["date"]);
			$metadata["date"] = date("Y-m-d H:i:s", $metadata["date"]);
		}
		else if($type === LONDON_PAGE)
		{
			$filename = __DIR__.self::$pages;
		}
		else
		{
			throw new \Exception("Invalid entry type.");
		}
		$filename .= $this->slug_from_title($metadata["title"]);
		if(file_exists($filename.".md"))
		{
			$suffix = 2;
			while(file_exists($filename."-".$suffix.".md"))
			{
				$suffix++;
			}
			$filename = $filename."-".$suffix;
		}
		$raw_data = $this->dump_headers($metadata)."\n".$content;
		if(!file_put_contents($filename.".md", $raw_data))
		{
			throw new \Exception("Error while writing file '".$filename."'.");
		}
	}
	
	/**
	** Converts a title to a lowercase, url-safe string
	**
	** @param string $title the title to convert
	** @return string a url-safe slug to use in filenames
	*/
	private function slug_from_title($title)
	{
		$slug = strtolower($title);
		$slug = preg_replace("/[^a-zA-Z0-9_-]+/", "-", $slug);
		$slug = preg_replace("/-$|^-/", "", $slug);
		return $slug;
	}
	
	
	/*
	****************************************************************************
	** Headers parsing
	****************************************************************************
	*/
	
	/**
	** Parses a HTTP-like headers string and returns a key/value hash
	**
	** @param string $headers_string the headers
	** @return hash the keys and values contained in the headers
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
	** Dumps a key/value hash as an HTTP-like headers string
	**
	** @param hash $headers the array to dump
	** @return string the string representation of the headers
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
}