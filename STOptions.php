<?php
/**
 * STOptions.php - Generator Options
 * Saturn - Simple PHP/Markdown blog generator
 * Created on 2014-08-05 by Cesar Parent <cesar@cesarparent.com>
 *
 * @package Saturn
 * @author Cesar Parent <cesar@cesarparent.com>
 * @copyright Copyright (c) 2014, Cesar Parent
 * @version 1.0-alpha1
 * @license https://github.com/cesarparent/saturn-generator/blob/master/LICENSE MIT License
 */

/**
 * @var mixed[] $options an array holding the blog's options
 */
$options = Array(
	"title" => "London Generator", // the name of your blog
	"url" => "http://localhost:8888", // the url of your blog without the trailing slash
	"description" => "London is a simple static blog generator, made by Cesar Parent.", // a short description
	"output_dir" => "output", // the output directory
	"maxposts" => 3, // the maximum  number of posts on the homepage and RSS
	"timezone" => "Europe/London",
	"language" => "en_GB"
);
?>
