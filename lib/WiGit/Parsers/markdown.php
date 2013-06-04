<?php
namespace WiGit\Parsers {
	class Markdown implements Parser {
		function parse( $text ) {
			return \Michelf\Markdown::defaultTransform( $text );
		}
	}
}
