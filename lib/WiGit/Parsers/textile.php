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
	}
}
