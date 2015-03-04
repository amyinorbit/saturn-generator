<?php
/*
** saturn.php - Saturn main executable script
** Saturn - Simple PHP/Markdown blog generator
** Created on 2014-08-08 by Cesar Parent <cesar@cesarparent.com>
*/

require_once(__DIR__."/STGenerator.php");
use \Saturn\Generator as Generator;
$saturn = new Generator;
$satellites = $saturn->list_satellites();

echo "\nSaturn Blog Generator v1.0\n";
echo "Written by Cesar Parent <http://cesarparent.com>\n---\n";
echo "Generating blog '".$saturn->options["title"]."'\n";
echo "Output:\t'".$saturn->out."'\n\n";

$start_time = microtime(true);
/*
** Register any satellites here:

$saturn->register_satellite("some_satellite");
*/

try
{
	echo "Copying static files...\t\t\t";
	$saturn->copy_static_files();
	echo "done\n";
	echo "Generating home page...\t\t\t";
	$saturn->generate_home();
	echo "done\n";
	echo "Generating posts and static pages...\t";
	$saturn->generate_entries();
	echo "done\n";
	echo "Generating archive page...\t\t";
	$saturn->generate_archive();
	echo "done\n";
	echo "Generating RSS feed...\t\t\t";
	$saturn->generate_rss();
	echo "done\n";
	echo "Generating sitemap feed...\t\t";
	$saturn->generate_sitemap();
	echo "done\n";
	echo "Generating JSON search index...\t\t";
	$saturn->generate_search_index();
	echo "done\n";
	
	$duration = microtime(true) - $start_time;
	printf("\nFinished generating blog in %.03fs\n\n", $duration);
}
catch(Exception $e)
{
	echo "Error\n";
	echo "---\nError details: ".$e->getMessage()."\n\n";
	echo "Enabled Satellites:\n";
	foreach($satellites as $sat)
	{
		echo "\t".$sat."\n";
	}
	$duration = microtime(true) - $start_time;
	printf("\nTerminating after %.03fs\n\n", $duration);
}



?>