<?php
/*
** LNDEngine.php — Generator utility class (File handling)
** London — Simple PHP/Markdown blog generator
** Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
*/

namespace London;

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
		$name_parts = explode("-", $post_id, 4);
		if(isset($post["date"]))
		{
			$post["date"] = strtotime($post["date"]);
		}
		else
		{
			$post["date"] = mktime("10", "00", "00", $name_parts[1], $name_parts[2], $name_parts[0]);
		}
		if(isset($post["lastmod"]))
		{
			$post["lastmod"] = strtotime($post["lastmod"]);
		}
		else
		{
			$post["lastmod"] = time();
		}
		$post["permalink"] = $this->blog["url"].date("/Y/m/", $post["date"]).$name_parts[3];
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
	**
	**
	**
	*/
	private function load_file($file_id)
	{
		$source = file_get_contents($file_id);
		if($source === false) throw new \Exception("Unable to open ".$file_id);
		
		list($headers,$content) = explode("\n\n", $source, 2);
		$post = $this->parse_headers($headers);
		$post["content"] = $content;
		return $post;
	}
	
	/**
	**
	**
	**
	*/
	private function parse_headers($headers_string)
	{
		$headers = [];
		$lines = explode(PHP_EOL, $headers_string);
		$key = $value = "";
		
		foreach($lines as $line)
		{
			list($key, $value) = explode(": ", $line, 2);
			$headers[$key] = $value;
		}
		return $headers;
	}
}