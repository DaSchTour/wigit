<?php
namespace WiGit\Parsers {
	interface Parser {
		public function parse($text);
		public function helpText();
	}
}
