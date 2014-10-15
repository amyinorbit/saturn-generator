# Saturn Blog Generator

Version 1.0-alpha1 - Oct. 15th, 2014  
by César Parent \[<http://cesarparent.com>\]

_A lightweight Markdown/static blog generator written in PHP. _

## Why Saturn

I started writing Saturn because I wanted a simpler, cleaner engine than [Asteroid](https://github.com/cesarparent/Asteroid/). Saturn does not have a bloated API, can generate a blog on a server or on a local computer, and can be extended with [satellites](#extension).

The code is also much cleaner than that of Asteroid, which suffered from two years of fiddling and adding on top of old, badly-written functions and classes.

## Installation and Usage

Saturn needs at least PHP 5.4 to run.

Copy Saturn's directory wherever you need it (local computer, web server), fill in the fields in `STOptions.php`, and edit the templates to suit your taste. Calling or visiting `saturn.php` will trigger a re-generation of the blog. After the generation, you will have to copy any non-generated files (CSS, images) in the output directory.

## Posts and Pages

Posts and static pages are simple `.md` plain text files, located in `/content/posts` and `/content/pages`. Post's filename must follow the "Jekyll" convention: `yyyy-mm-dd-post-slug`, while pages only need the slug of the page.

A simple post would look like this:

~~~~txt
title: Some great title
[tags: comma, separated, tags]
[date: yyyy-mm-dd hh:mm:ss]

Some [Markdown](http://daringfireball.net/projects/markdown) content;
~~~~

`tags` and `date` are optionals. In case no date is given, Saturn will create one by parsing the filename.

## Extension

Saturn allows you to create _satellites_ (filters) through which posts and pages will be ran at generation time. Some default satellites are provided to make Saturn work (SmartyPants and Markdown for example);

A satellite must take `(int $type, $array $entry)` as parameters. `$type` will be either `SATURN_PAGE` or `SATURN_POST`. `$entry` will be a hash/array containing, at least, the following keys, along with any keys added in the post header:

~~~~php
$entry = [
    "title" => "The post's title",
    "tags" => ["tag1", "tag2", ...],
    "date" => unix timestamp,
    "content" => "Content, modified by previous satellites",
    "permalink" => "the permalink of the entry, relative to the blog's root",
    ...
];
~~~~

Satellites must return the modified version of this hash.

Satellites are registered in `saturn.php` before the calls to the main generation procedures using `$saturn->register_satellite("satellite_function_name")`.

## Acknowledgments

Saturn takes ideas from [Jekyll](http://jekyllrb.com), [Steven Frank's Laguna](https://github.com/panicsteve/laguna-blog/), and blog posts by [Brent Simmons](http://inessential.com).

The default satellites rely on [Markdown](http://daringfireball.net/projects/markdown/) by John Gruber, and [PHP Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) and [PHP SmartyPants](https://michelf.ca/projects/php-smartypants/) by Michel Fortin.

**PHP Markdown Extra**

> PHP Markdown Lib Copyright © 2004-2013 Michel Fortin http://michelf.ca/ All rights reserved.
>
> Based on Markdown Copyright © 2003-2005 John Gruber http://daringfireball.net/ All rights reserved.

**PHP SmartyPants**

> Copyright (c) 2004-2013 Michel Fortin All rights reserved. Based on SmartyPants Copyright (c) 2003-2004 John Gruber All rights reserved.

## License

Saturn is released under the MIT license. You can read the full license in the `LICENSE` file, but it basically means that you can use, edit, and redistribute Saturn however you want, as long as you keep the copyright files and the attribution at the beginning of the files. Share!
