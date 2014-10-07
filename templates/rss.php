<rss version="2.0">
	<channel>
		<title><?php echo $blog["title"]; ?></title>
		<link><?php echo $blog["url"]; ?></link>
		<description>
			<?=$blog["title"]; ?>: <?=$blog["description"]; ?>
		</description>
		<language>fr-fr</language>
		<? foreach($entries as $entry): ?>
		<item>
			<title><![CDATA[<?=$entry["title"]; ?>]]></title>
			<link><?=$blog["url"].$entry["permalink"]; ?></link>
			<guid isPermaLink="false"><?=md5($blog["url"].$entry["permalink"]); ?></guid>
			<pubDate><?=date("D, d M Y 10:00:00 O", $entry["date"]); ?></pubDate>
			<description><![CDATA[<?=$entry["content"] ?>]]></description>
		</item>
		<? endforeach; ?>
	</channel>
</rss>