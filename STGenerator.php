<?php
/*
** STGenerator.php - Generator main logic
** Saturn - Simple PHP/Markdown blog generator
** Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
*/

namespace Saturn;

require_once(__DIR__."/STEngine.php");
require_once(__DIR__."/STSatellites.php");

use \Exception as Exception;

class Generator
{
	private $engine;
	private $options;
	private $satellites;
	private $out;
	public static $templates = "/templates/";
	
	/**
	** Constructor. Loads the options, creates an Engine instance, and
	** registers the default satellites
	**
	** @return \London\Generator a new instance of Generator
	*/
	public function __construct()
	{
		require(__DIR__."/STOptions.php");
		$this->options = $options;
		$this->engine = new \Saturn\Engine();
		$this->satellites = [];
		$this->out = __DIR__."/".$this->options["output_dir"];
		if(!is_dir($this->out))
		{
			mkdir($this->out, 0777, true);
		}
		$this->register_satellite("satellite_markdown");
		$this->register_satellite("satellite_smartypants");
		$this->register_satellite("satellite_wordcount");
		$this->register_satellite("satellite_sitemap");
	}
	
	/**
	** Build the home page and dumps its html in the output folder
	**
	** @return void
	*/
	public function generate_home()
	{
		$entries = $this->entries_list(LONDON_POST,$this->options["maxposts"]);
		$blog = $this->options;
		$template = "home";
		$page = [
			"title" => $this->options["title"],
			"description" => $this->options["description"],
		];
		ob_start();
		require(__DIR__.self::$templates."main-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."/index.html", $output)) {
			throw new Exception("Error writing to '".$this->out."/index.html'.");
		}
	}
	
	/**
	** Build every posts page and dump their html in the output folder
	**
	** @return void
	*/
	public function generate_entries()
	{
		$posts = $this->entries_list(LONDON_POST);
		$pages = $this->entries_list(LONDON_PAGE);
		foreach ($posts as $post) {
			$this->generate_entry(LONDON_POST, $post);
		}
		foreach ($pages as $page) {
			$this->generate_entry(LONDON_PAGE, $page);
		}
	}
	
	/**
	** write the posts archive html page to the disk
	**
	** @return void
	*/
	public function generate_archive()
	{
		$template = "archive";
		$blog = $this->options;
		$page = [
			"title" => $this->options["title"].": archive",
			"description" => "article's archive published here."
		];
		$entries = $this->entries_list(LONDON_POST);
		$out = $this->out."/archive";
		if(!is_dir($out))
		{
			mkdir($out, 0777, true);
		}
		ob_start();
		require(__DIR__.self::$templates."main-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($out."/index.html", $output)) {
			throw new Exception("Error writing to '".$out."/index.html'.");
		}
	}
	
	/**
	** write the blog's rss file to the disk
	**
	** @return void
	*/
	public function generate_rss()
	{
		$blog = $this->options;
		$entries = $this->entries_list(LONDON_POST,$this->options["maxposts"]);
		ob_start();
		require(__DIR__.self::$templates."rss.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."/rss.xml", $output)) {
			throw new Exception("Error writing to '".$this->out."/rss.xml'.");
		}
	}
	
	/**
	** write the blog's sitemap to the disk
	**
	** @return void
	*/
	public function generate_sitemap()
	{
		$blog = $this->options;
		$posts = $this->entries_list(LONDON_POST);
		$pages = $this->entries_list(LONDON_PAGE);
		ob_start();
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		require(__DIR__.self::$templates."sitemap-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."/sitemap.xml", $output)) {
			throw new Exception("Error writing to ".$this->out."/sitemap.xml.");
		}
	}
	
	/**
	** generate a JSON search index with all the posts
	**
	** @return void
	*/
	public function generate_search_index()
	{
		$posts = $this->entries_list(LONDON_POST);
		$index = [];
		foreach($posts as $post)
		{
			$post_index = [
				"title" => $post["title"],
				"url" => $this->options["url"].$post["permalink"],
				"date" => date("Y-m-d", $post["date"]),
				"tags" => $post["tags"],
			];
			array_push($index, $post_index);
		}
		$json_index = json_encode($index);
		if(!file_put_contents($this->out."/search.json", $json_index)) {
			throw new Exception("Error writing to ".$this->out."/search.json.");
		}
	}
	
	/**
	** Writes a single entry's html page to the disk
	**
	** @param int $type the type (LONDON_PAGE|LONDON_POST) to write;
	** @param hash $entry the entry array
	*/
	private function generate_entry($type, array $entry)
	{
		$blog = $this->options;
		$page = [
			"title" => $blog["title"].": ".$entry["title"],
			"description" => substr(strip_tags($entry["content"]), 0, 512)
		];
		$template = ($type === LONDON_POST)? "post" : "page";
		$out = $this->out.$entry["permalink"];
		if(!is_dir($out))
		{
			mkdir($out, 0777, true);
		}
		ob_start();
		require(__DIR__.self::$templates."main-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($out."/index.html", $output)) {
			throw new Exception("Error writing to '".$out."/index.html'.");
		}
	}
	
	/**
	** Returns an array of posts, ran through every satellites
	**
	** @param int $type the type (LONDON_PAGE|LONDON_POST) of entries
	** @param int $limit the optional limit of entries to return
	** @return hash[] an list of entry arrays
	*/
	private function entries_list($type, $limit = null)
	{
		$entries = [];
		$slugs = $this->engine->slug_list($type,
			$this->options["maxposts"]);
		
		foreach($slugs as $slug)
		{
			if($type === LONDON_POST)
			{
				$entry = $this->engine->load_post($slug);
			}
			else
			{
				$entry = $this->engine->load_page($slug);
			}
			$entries[] = $this->apply_satellites($type, $entry);
		}
		return $entries;
	}
	
	/**
	** Add a satellite to be applied to posts while processing. satellite should take
	** An entry aray as their only parameter, and return a modified
	** entry array
	**
	** @param callable $satellite takes an entry array and modifies it
	** @return void
	*/
	public function register_satellite(callable $satellite)
	{
		array_push($this->satellites, $satellite);
	}
	
	/**
	** Applies all registered satellites to an entry and returns it
	**
	** @param int $type the type (LONDON_PAGE|LONDON_POST) of entries
	** @param hash $entry the entry hash
	** @return hash the satelliteed entry
	*/
	private function apply_satellites($type, array $entry)
	{
		foreach($this->satellites as $satellite)
		{
			$entry = $satellite($type, $entry);
		}
		return $entry;
	}
}


