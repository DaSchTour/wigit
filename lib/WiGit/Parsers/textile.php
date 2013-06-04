<?php
namespace WiGit\Parsers {
	class Textile implements Parser {
		public $parser;
		function __construct() {
			include_once('classTextile.php');
			$this->parser = new \Textile();
		}
		function parse( $text ) {
			// Linkify
			$text = preg_replace('@([^:])(https?://([-\w\.]+)+(:\d+)?(/([%-\w/_\.]*(\?\S+)?)?)?)@', '$1<a href="$2">$2</a>', $text);

			// WikiLinkify
			$text = preg_replace('@\[([A-Z]\w+)\]@', '<a href="' . $SCRIPT_URL . '/$1">$1</a>', $text);
			$text = preg_replace('@\[([A-Z]\w+)\|([\w\s]+)\]@', '<a href="' . $SCRIPT_URL . '/$1">$2</a>', $text);
			return $this->parser->TextileThis( $text );
		}
		function helpText() {
			return <<<'EOL'
			Besides normal HTML
				code (e.g. <code>&lt;b&gt;Bold&lt;/b&gt;</code>), the following 
				markup is available as well:
				<ul>
					<li><code>[SomePage]</code>: Internal link to SomePage</li>
					<li><code>h1. Section</code>, <code>h2. Subsection</code>: 
						Section headers</li>
					<li><code># Item</code>, <code>## Second-level item</code>:
						Enumerated list</li>
					<li><code>* Item</code>, <code>** Second-level item</code>:
						Itemized list</li>
					<li><code>"Some URL":http://someurl.com</code>: External links</li>
					<li><code>!/path/to/image.jpg!</code>: Embedded images</li>
					<li><code>_Emphasised text_, *strong text*, ??citations??, @code@,
						+Inserted text+, -Removed text-</code> &rarr; 
						<em>Emphasized text</em>, <strong>strong text</strong>,
						<cite>citation</cite>, <ins>inserted text</ins>, <del>removed
						text</del></li>
					<li><code>H~2~O, A^2^</code> &rarr; H<sub>2</sub>O, A<sup>2</sup></li>
					<li><code>Abbr(Abbreviation)</code> &rarr; <acronym tytle="Abbreviation">Abbr</acronym></li>
					<li><code>|Cell 1|Cell 2|</code>: Tables</li>
					<li><code>%{color:red}Red% text</code> &rarr; <span style='color:red'>Red</span> text</li>
				</ul>
				For more markup styles, see the 
				<a href="http://hobix.com/textile/">Textile reference</a>.
EOL;
		}
	}
}
