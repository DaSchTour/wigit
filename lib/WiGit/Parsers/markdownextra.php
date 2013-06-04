<?php
namespace WiGit\Parsers {
	class MarkdownExtra implements Parser {
		function parse( $text ) {
			return \Michelf\MarkdownExtra::defaultTransform( $text );
		}
	}
}
