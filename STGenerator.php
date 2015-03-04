<?php
/**
 * STGenerator.php - Generator main logic
 * Saturn - Simple PHP/Markdown blog generator
 * Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
 *
 * @package Saturn
 * @author Cesar Parent <cesar@cesarparent.com>
 * @copyright Copyright (c) 2014, Cesar Parent
 * @version 1.0-alpha1
 * @license https://github.com/cesarparent/saturn-generator/blob/master/LICENSE MIT License
*/

namespace Saturn;

require_once(__DIR__."/STEngine.php");
require_once(__DIR__."/STSatellites.php");

use \Exception as Exception;

/**
 * Saturn generator class. Provides methods to genereate posts, pages, index,
 * and other needed html pages for the blog.
 */
class Generator
{
	/**
	 * @var Engine $engine The Saturn engine instance used by the generator
	 */
	private $engine;
	/**
	 * @var callable[] $satellite satellites called when parsing posts
	 */
	private $satellites;
	/**
	 * @var string $out the output directory
	 */
	public $out;
	/**
	 * @var mixed[] $options the options of the blog
	 */
	public $options;
	/**
	 * @var string $templates the path to the templates directory
	 */
	public static $templates = "/templates/";

	/**
	 * Constructor. Loads the options, creates an Engine instance, and
	 * registers the default satellites
	 *
	 * @return Generator a new instance of Generator
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
		date_default_timezone_set($this->options["timezone"]);
	}

	/**
	 * Build the home page and dumps its html in the output folder
	 *
	 * @return void
	 */
	public function generate_home()
	{
		$entries = $this->entries_list(SATURN_POST,$this->options["maxposts"]);
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
	 * Build every posts page and dump their html in the output folder
	 *
	 * @return void
	 */
	public function generate_entries()
	{
		$posts = $this->entries_list(SATURN_POST);
		$pages = $this->entries_list(SATURN_PAGE);
		foreach ($posts as $post) {
			$this->generate_entry(SATURN_POST, $post);
		}
		foreach ($pages as $page) {
			$this->generate_entry(SATURN_PAGE, $page);
		}
	}

	/**
	 * write the posts archive html page to the disk
	 *
	 * @return void
	 */
	public function generate_archive()
	{
		$template = "archive";
		$blog = $this->options;
		$page = [
			"title" => $this->options["title"].": archive",
			"description" => "article's archive published here."
		];
		$entries = $this->entries_list(SATURN_POST);
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
	 * write the blog's rss file to the disk
	 *
	 * @return void
	 */
	public function generate_rss()
	{
		$blog = $this->options;
		$entries = $this->entries_list(SATURN_POST,$this->options["maxposts"]);
		ob_start();
		require(__DIR__.self::$templates."rss.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."/rss.xml", $output)) {
			throw new Exception("Error writing to '".$this->out."/rss.xml'.");
		}
	}

	/**
	 * write the blog's sitemap to the disk
	 *
	 * @return void
	 */
	public function generate_sitemap()
	{
		$blog = $this->options;
		$posts = $this->entries_list(SATURN_POST);
		$pages = $this->entries_list(SATURN_PAGE);
		ob_start();
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		require(__DIR__.self::$templates."sitemap-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."/sitemap.xml", $output)) {
			throw new Exception("Error writing to ".$this->out."/sitemap.xml.");
		}
	}

	/**
	 * generate a JSON search index with all the posts
	 *
	 * @return void
	 */
	public function generate_search_index()
	{
		$posts = $this->entries_list(SATURN_POST);
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
	 * Writes a single entry's html page to the disk
	 *
	 * @param int $type the type (SATURN_PAGE|SATURN_POST) to write;
	 * @param mixed[] $entry the entry array
	 */
	private function generate_entry($type, array $entry)
	{
		$blog = $this->options;
		$page = [
			"title" => $blog["title"].": ".$entry["title"],
			"description" => substr(strip_tags($entry["content"]), 0, 512)
		];
		$template = ($type === SATURN_POST)? "post" : "page";
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
	 * Returns an array of posts, ran through every satellites
	 *
	 * @param int $type the type (SATURN_PAGE|SATURN_POST) of entries
	 * @param int $limit the optional limit of entries to return
	 * @return array[] an list of entry arrays
	 */
	private function entries_list($type, $limit = null)
	{
		$entries = [];
		$slugs = $this->engine->slug_list($type, $limit);

		foreach($slugs as $slug)
		{
			if($type === SATURN_POST)
			{
				$entry = $this->engine->load_post($slug);
			}
			else
			{
				$entry = $this->engine->load_page($slug);
			}
			array_push($entries, $this->apply_satellites($type, $entry));
		}
		return $entries;
	}

	/**
	 * Add a satellite to be applied to posts while processing.
	 * satellites should take the entry type and array as their only parameters,
	 * and return a modified entry array
	 *
	 * @param callable $satellite takes an entry array and modifies it
	 * @return void
	 */
	public function register_satellite(callable $satellite)
	{
		array_push($this->satellites, $satellite);
	}

	/**
	 * Returns a list of add-on satellites (default satellites are not
	 * included)
	 *
	 * @return string[] a list of registered satellites
	 */
	public function list_satellites()
	{
		return $this->satellites;
	}

	/**
	 * Applies all registered satellites to an entry and returns it
	 *
	 * @param int $type the type (SATURN_PAGE|SATURN_POST) of entries
	 * @param mixed[] $entry the entry array
	 * @return mixed[] the satelliteed entry
	 */
	private function apply_satellites($type, array $entry)
	{
		foreach($this->satellites as $satellite)
		{
			$entry = call_user_func($satellite, $type, $entry);
		}
		return $entry;
	}
}
