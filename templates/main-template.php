<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=$page["title"]; ?></title>

    <meta name="description" content="<?=$page["description"]; ?>" />
    <link rel="stylesheet" type="text/css" media="screen" href="/_assets/css/style.css"/>
    <meta name="viewport" content = "width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="alternate" type="application/rss+xml" title="blog-title's RSS" href="<?=$blog["url"]; ?>/rss.xml" />
    <!--[if lt IE 9]>
           <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>
<body>
    <header id="banner">
        <h1 id="blog-title"><a href="<?=$blog["url"]; ?>"><?=$blog["title"]; ?></a></h1>
        <p id="colophon"><?=$blog["description"]; ?></a>.</p>
        <nav id="blog-nav">
            <ul>
                <li><a href="<?=$blog["url"]; ?>/a-static-page/">static page</a></li>
                <li><a href="<?=$blog["url"]; ?>/archive/">archive</a></li>
            </ul>
        </nav>
    </header><!-- header#baner -->

    <section id="main-content">

        <!-- index template
        loops through the posts
        -->
        <? if($template == "home"): ?>
            <? foreach($entries as $entry): ?>
                <article class="entry">

                    <header class="entry-header">
                        <h1><a href="<?=$blog["url"].$entry["permalink"]; ?>"><?=$entry["title"]; ?></a></h1>
                        <aside class="entry-meta">›&nbsp;<?=date("Y-m-d H:i:s", $entry["date"]); ?> // <?=$entry["readingtime"]; ?>&nbsp;min read</aside>
                    </header><!-- header.entry-header -->

                    <article class="entry-content">

                        <? $parts = explode("<!--more-->", $entry["content"], 2); ?>
                        <?=$parts[0]; ?>
                        <? if(count($parts) > 1): ?>
                        <p>
                            <a title="read on" class="more-link" href="<?=$blog["url"].$entry["permalink"]; ?>">continue&nbsp;›</a>
                        </p>
                        <? endif; ?>
                    </article><!-- article.entry-content -->

                </article><!-- article.entry -->
            <? endforeach; ?>
            <a id="to-archive" class="more-link" href="<?=$blog["url"]; ?>/archive/">Continue to archive</a>
        <? endif; ?>

        <!-- post template
        lays out the current entry
        -->
        <? if($template == "post"): ?>
            <article class="entry">

                <header class="entry-header">
                    <h1><?=$entry["title"]; ?></h1>
                    <aside class="entry-meta">›&nbsp;<?=date("Y-m-d", $entry["date"]); ?> // <?=$entry["readingtime"]; ?>&nbsp;min read</aside>
                </header><!-- header.entry-header -->

                <article class="entry-content">
                    <?=$entry["content"]; ?>
                </article><!-- article.entry-content -->

            </article><!-- article.entry -->
        <? endif; ?>

        <!-- page template
        lays out the current static page
        -->
        <? if($template == "page"): ?>
            <article class="entry page">
                <header class="entry-header">
                    <h1><?=$entry["title"]; ?></h1>
                </header><!-- header.entry-header -->
                <article class="entry-content">
                    <?=$entry["content"]; ?>
                </article>
            </article>
        <? endif; ?>

        <!-- archive template
        lays out the complete list of posts over the years
        -->
        <? if($template == "archive"): ?>
            <? $previousYear = null; ?>
            <section id="archive">
                <h1>Archive</h1>
                <? foreach($entries as $entry): ?>
                    <? $currentYear = date('Y', $entry["date"]); ?>
                    <? if ($previousYear == null || $previousYear != $currentYear): ?>
                    <h2 class="archive-year" id="<?=$currentYear; ?>"><?=$currentYear; ?></h2>
                    <? $previousYear = $currentYear; ?>
                    <? endif; ?>
                    <article class="entry archive-entry">
                        <header class="entry-header">
                            <h1><a href="<?=$blog["url"].$entry["permalink"]; ?>"><?=$entry["title"]; ?></a></h1>
                            <aside class="entry-meta"><?=date("Y-m-d", $entry["date"]); ?></aside>
                        </header>
                    </article>
                <? endforeach; ?>
            </section>
        <? endif; ?>

    </section><!-- section#main-content -->
</body>
</html>
