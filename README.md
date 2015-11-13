### Syntax Highlightning Class for PHP

This is an modified and extended PHP syntax highlighting class originally published in a blog post called [Generic Syntax Highlighting with Regular Expressions](http://phoboslab.org/log/2007/08/generic-syntax-highlighting-with-regular-expressions "Generic Syntax Highlighting with Regular Expressions") by Dominic Szablewski.

**_Changes are:_**
* special functions for HTML, CSS and PHP
* added a bunch of keywords
* sorted keywords alphabetically
* added rule for PHP start end end tags

**_Usage:_**
Include the provided CSS file in your HTML header and do something like this:
```
$output = SyntaxHighlight::process($input [, $lang]);
```
where the optional `$lang` is one of
* "html"
* "php"
* "css"
If no language is specified genericg syntax highligting takes place.

**_Example:_**
```
<?php
include ("SyntaxHighlight.php");

$html = <<<HTML
<html>
<body>
    <?php echo content; ?>
</body>
</html>
HTML;

$colored = SyntaxHighlight::process($html, "html");
$even_more_colored = SyntaxHighlight::process($colored, "php");
?>
```
`$colored`:
```
<span class="html_tag">&lt;html <span class="html_attr">lang</span>=<span class="html_data">"en"</span><span class="html_tag">&gt;</span>
<span class="html_tag">&lt;body</span><span class="html_tag">&gt;</span>
    &lt;?php echo content; ?&gt;
<span class="html_tag">&lt;/body</span><span class="html_tag">&gt;</span>
<span class="html_tag">&lt;/html</span><span class="html_tag">&gt;</span>
```
`$even_more_colored`:
```
<span class="html_tag">&lt;html <span class="html_attr">lang</span>=<span class="html_data">"en"</span><span class="html_tag">&gt;</span>
<span class="html_tag">&lt;body</span><span class="html_tag">&gt;</span>
    <span class="D">&lt;?php</span> <span class="K">echo</span> content; <span class="D">?&gt;</span>
<span class="html_tag">&lt;/body</span><span class="html_tag">&gt;</span>
<span class="html_tag">&lt;/html</span><span class="html_tag">&gt;</span>
```
