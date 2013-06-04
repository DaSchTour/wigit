<?php
namespace WiGit\Parsers {
	class Markdown implements Parser {
		function parse( $text ) {
			return \Michelf\Markdown::defaultTransform( $text );
		}
		function helpText() {
			return <<<'EOL'
			The following markdown is available:
				<ul>
					<li><code>[SomePage](?r=SomePage)</code>: Internal link to SomePage</li>
					<li><code>Section<br />=======</code>: Section headers</li>
					<li><code>Subsection<br />----------</code>: Subsection headers</li>
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
				For more markdown styles, see the 
				<a href="http://daringfireball.net/projects/markdown/syntax/">Markdown syntax reference</a>.
EOL;
		}
	}
}
