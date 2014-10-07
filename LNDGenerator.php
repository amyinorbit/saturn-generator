<?php
/*
** LNDGenerator.php — Generator main logic
** London — Simple PHP/Markdown blog generator
** Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
*/

namespace London;

require_once(__DIR__."/LNDEngine.php");

class Generator
{
	private $engine;
	private $options;
	private $filters;
	private $out;
	public static $templates = "/templates/";
	
	/**
	** Constructor. Loads the optios and creates an Engine instance
	**
	** @return \London\Generator a new instance of Generator
	*/
	public function __construct()
	{
		require(__DIR__."/LNDOptions.php");
		$this->options = $options;
		$this->engine = new \London\Engine();
		$this->filters = [];
		$this->out = __DIR__."/".$this->options["output_dir"]."/";
	}
	
	/**
	** Build the home page
	**
	**
	**
	*/
	public function generate_home()
	{
		$posts = $this->filtered_list(LONDON_POST,$this->options["maxposts"]);
		$blog = $this->options;
		$template = "home";
		$page = [
			"title" => $this->options["title"],
			"description" => $this->options["description"],
		];
		ob_start();
		require(__DIR__.self::$templates."main-template.php");
		$output = ob_get_clean();
		if(!file_put_contents($this->out."index.html", $output)) {
			throw new Exception("Error writing to '".$this->out."index.html'.");
		}
	}
	
	/**
	** Add a filter to be applied to posts while processing. Filter should take
	** An entry aray as their only parameter, and return a modified
	** entry array
	**
	** @param callable $filter takes an entry array and modifies it
	** @return void
	*/
	public function register_filter(callable $filter)
	{
		array_push($this->filters, $filter);
	}
	
	/**
	**
	**
	**
	*/
	private function filtered_list($type, $limit = null)
	{
		$entries = [];
		$slugs = $this->engine->slug_list($type,
			$this->options["maxpost"]);
		if($type === LONDON_POST)
		{
			foreach($slugs as $slug)
			{
				$entry = $this->engine->load_post($slug);
				$entries[] = $this->apply_filters($entry);
			}
		}
		else
		{
			foreach($slugs as $slug)
			{
				$entry = $this->engine->load_page($slug);
				$entries[] = $this->apply_filters($entry);
			}
		}
		return $entries;
	}
	
	/**
	**
	**
	**
	*/
	private function apply_filters(array $entry)
	{
		foreach($this->filters as $filter)
		{
			$entry = $filter($entry);
		}
		return $entry;
	}
}


