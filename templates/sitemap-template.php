<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
<?php foreach($posts as $entry): ?>
	<url>
		<loc><?php echo $blog["url"].$entry["permalink"]; ?></loc>
		<lastmod><?php echo date("Y-m-d", $entry["date"]); ?></lastmod>
		<changefreq><?php echo $entry["frequency"]; ?></changefreq>
		<priority><?php echo $entry["priority"]; ?></priority>
	</url>
<?php endforeach; ?>
<?php foreach($pages as $entry): ?>
	<url>
		<loc><?php echo $blog["url"].$entry["permalink"]; ?></loc>
		<lastmod><?php echo date("Y-m-d", $entry["date"]); ?></lastmod>
		<changefreq><?php echo $entry["frequency"]; ?></changefreq>
		<priority><?php echo $entry["priority"]; ?></priority>
	</url>
<?php endforeach; ?>
	<url>
		<loc><?php echo $blog["url"]."/"; ?></loc>
		<lastmod><?php echo date("Y-m-d"); ?></lastmod>
		<changefreq>daily</changefreq>
		<priority>0.6</priority>
	</url>
	<url>
		<loc><?php echo $blog["url"]."/archive/"; ?></loc>
		<lastmod><?php echo date("Y-m-d"); ?></lastmod>
		<changefreq>daily</changefreq>
		<priority>0.8</priority>
	</url>
</urlset>