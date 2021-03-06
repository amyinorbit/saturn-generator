<?php
/**
 * LNDsatellites.php - Entry satellites
 * London - Simple PHP/Markdown blog generator
 * Created on 2014-08-07 by Amy Parent <amy@amyparent.com>
 *
 * This file contains the satellites that posts are passed through before
 * being output to the blog
 *
 * satellites take the entry array and type as parameters, and should return
 * a valid entry array
 *
 * Only satellites registered with `Generator->register_satellite()` are executed
 * at generation time
 *
 * @package Saturn
 * @author Amy Parent <amy@amyparent.com>
 * @copyright Copyright (c) 2014, Amy Parent
 * @version 1.0-alpha1
 * @license https://github.com/cesarparent/saturn-generator/blob/master/LICENSE MIT License
*/

/*
 ***************************************************************************** *
 * Default satellites - Markdown, SmartyPants, wordcount/readingtime
 ***************************************************************************** *
 */

function satellite_wordcount($type, array $entry)
{
	$entry["wordcount"] = str_word_count(strip_tags($entry["content"]));
	$entry["readingtime"] = ceil($entry["wordcount"]/200);
	return $entry;
}

function satellite_markdown($type, array $entry)
{
	require_once(__DIR__."/libraries/Markdown.php");
	require_once(__DIR__."/libraries/MarkdownExtra.php");
	$parser = new \Michelf\MarkdownExtra;
	$parser->fn_id_prefix = strlen($entry["content"]);
	$entry["content"] = $parser->transform($entry["content"]);
	return $entry;
}

function satellite_smartypants($type, array $entry)
{
	require_once(__DIR__."/libraries/SmartyPants.php");
	$entry["content"]=\Michelf\SmartyPants::defaultTransform($entry["content"]);
	return $entry;
}

function satellite_sitemap($type, array $entry)
{
	$entry["priority"] = ($type === SATURN_POST)? 0.8 : 0.6;
	$entry["frequency"] = ($type === SATURN_POST)? "monthly" : "yearly";
	return $entry;
}
