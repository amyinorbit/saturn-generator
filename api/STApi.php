<?php
/**
 * STApi.php — Saturn REST API server.
  * Extends RESTServer to provide REST endpoints for saturn blog servers.
 * Created on 2014-10-12 by Cesar Parent <cesar@cesarparent.com>
 */
namespace Saturn;

error_reporting(E_ALL);

include(__DIR__."/STRest.php");
include(__DIR__."/../../STEngine.php");
include(__DIR__."/../../STGenerator.php");

class SaturnServer extends RESTServer
{
	public $saturn;
    
    /**
     * Constructor. Setup the REST server object, and register handlers for each route.
     */
	public function __construct()
	{
		parent::__construct($_REQUEST);

		$this->saturn = new Engine();

		$this->base_url = $this->saturn->blog["url"]."/api/";

		$this->route("/posts", "GET", [$this, "get_posts"]);
		$this->route("/posts/<id>", "GET", [$this, "get_post"]);

		$this->route("/posts", "POST", [$this, "post_posts"]);
		$this->route("/posts/<id>", "POST", [$this, "post_post"]);

		$this->route("/posts/<id>", "DELETE", [$this, "delete_post"]);

		$this->route("/blog", "GET", [$this, "get_blog"]);
		$this->route("/blog", "PUT", [$this, "put_blog"]);
	}
    
    /**
     * Get a list of available posts.
     */
	public function get_posts()
	{
		$limit = 10;
		if(isset($this->data["limit"]))
		{
			$limit = intval($this->data["limit"]);
		}
		$this->response_data["posts"] = [];
		$slugs = $this->saturn->slug_list(SATURN_POST, $limit);
		foreach($slugs as $slug)
		{
			$post = $this->saturn->load_post($slug);
			unset($post["content"]);
			$post["href"] = $this->base_url."posts/".$slug;;
			$post["id"] = $slug;;
			array_push($this->response_data["posts"], $post);
		}
		$this->response_status = "success";
		$this->http_status = 200;
	}
    
    /**
     * Get the details of a single post.
     */
	public function get_post()
	{
		$post = $this->saturn->load_post($this->resource_id);
		if(!$post)
		{
			$this->http_status = 404;
			$this->response_status = "fail";
			$this->response_data["id"] = $this->resource_id." not found";
		}
		else
		{
			$this->http_status = 200;
			$this->response_status = "success";
			$this->response_data["post"] = $post;
		}
	}
    
    /**
     * Add a post if all the required data (content and title) are given.
     */
	public function post_posts()
	{
		if(!isset($this->data["title"]) || !isset($this->data["content"]))
		{
			$this->response_status = "fail";
			$this->http_status = 400;
			if(!isset($this->data["title"]))
			{
				$this->response_data["title"] = "A title is required";
			}
			if(!isset($this->data["content"]))
			{
				$this->response_data["content"] = "Content is required";
			}
			return;
		}
		$title = $this->data["title"];
		if(isset($this->data["tags"]))
		{
			$tags = preg_split("/[ *]?,[ *]?/", $this->data["tags"]);;
		}
		else
		{
			$tags = [];
		}
		$content = $this->data["content"];
		$slug = $this->saturn->add_post($title, $tags, $content);
		$this->response_status = "success";
		$this->http_status = 201;
		$this->response_data["href"] = $this->base_url."posts/".$slug;
		$this->response_data["id"] = $slug;
		header("Location: /posts/".$slug);
	}
    
    /**
     * Update a specific post.
     */
	public function post_post()
	{
		$post = $this->saturn->load_post($this->resource_id);
		if(!$post)
		{
			$this->http_status = 404;
			$this->response_status = "fail";
			$this->response_data["id"] = "'".$this->resource_id."' does not exist.";
			return;
		}
		if(!isset($this->data["title"]) &&
			!isset($this->data["tags"]) &&
			!isset($this->data["content"]))
		{
			$this->http_status = 400;
			$this->response_status = "fail";
			$this->response_data["post"] = "At least one change is required";
			return;
		}
		$this->response_data["changed_fields"] = [];
		foreach($this->data as $k => $v)
		{
			if(in_array($k, ["title", "content", "tags"]))
			{
				$post[$k] = $v;
				$this->response_data["changed-fields"][] = $k;
			}
		}
		$this->saturn->edit_entry(SATURN_POST, $this->resource_id, $post);
		$this->response_status = "success";
		$this->http_status = 200;
	}
    
    /**
     * Delete a post.
     */
	public function delete_post()
	{
		if($this->saturn->delete_entry(SATURN_POST, $this->resource_id))
		{
			$this->http_status = 200;
			$this->response_status = "success";
			$this->response_data = null;
		}
		else
		{
			$this->http_status = 404;
			$this->response_status = "fail";
			$this->response_data["id"] = "'".$this->resource_id."' does not exist.";
		}
	}
    
    /**
     * Get information on the blog (URL, Saturn Version).
     */
	public function get_blog()
	{
		$blog = [
			"url" => $this->saturn->blog["url"],
			"name" => $this->saturn->blog["title"],
			"saturn-version" => $this->saturn->version,
		];
		$this->response_status = "success";
		$this->http_status = 200;
		$this->response_data["blog"] = $blog;
	}
    
    /**
     * Trigger blog regeneration.
     */
	public function put_blog()
	{
		$generator = new \Saturn\Generator();
		$start_time = microtime(true);

		$generator->generate_home();
		$generator->generate_entries();
		$generator->generate_archive();
		$generator->generate_rss();
		$generator->generate_sitemap();
		$generator->generate_search_index();

		$duration = microtime(true) - $start_time;
		$this->response_data["message"] = sprintf("blog generated in %.03fs",
			$duration);
		$this->response_status = "success";
		$this->http_status = 200;
	}
}
?>
