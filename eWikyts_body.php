<?php
class eWikyts extends SpecialPage {
	private $langs;
	private $basestrings;
	private $strings;
	private $header;
	private $footer;
	private $currentlang;

        function eWikyts() {
		SpecialPage::SpecialPage("eWikyts");
		if (function_exists('wfLoadExtensionMessages'))
			wfLoadExtensionMessages('eWikyts');

		$this->langs = array();
		$this->basestrings = array();
		$this->strings = array();
        }
 
        function execute( $par ) {
                global $wgRequest, $wgOut, $wgTitle, $wgWikytsTitle;
 
                $this->setHeaders();

		self::parseWikiArray($wgWikytsTitle . "/langs", $this, "fillLangs");

		$this->langChooser();
		if ($this->currentlang == "") return;

		self::parseWikiArray($wgWikytsTitle . "/en", $this, "fillBaseStrings");

		self::parseWikiArray($wgWikytsTitle . "/" . $this->currentlang, $this, "fillStrings");

		if (($wikytsidx = $wgRequest->getArray("wikytsidx" )) != NULL) {
			$this->editForm($wikytsidx);
		} else {
			if ($wgRequest->getVal("wikytssave")) {
				$values = $wgRequest->getValues();
				foreach($values as $name => $value) {
					if(substr($name, 0, 12) == "wikytsstring") {
						$idx = (int) substr($name, 12);
						$this->strings[strtolower($this->basestrings[$idx])] = "<nowiki>$value</nowiki>";
					}
				}
				$this->save($this->currentlang);
			}
			$this->listStrings();
		}
        }

	function editForm($wikytsidx) {
		global $wgOut, $wgTitle;

		$wgOut->addHTML( "<form method='POST'>" );
		$wgOut->addHTML( "<table style='width:100%'>" );
		foreach($wikytsidx as $idx) {
			$value = $this->strings[strtolower($this->basestrings[$idx])];
			if (preg_match(',^<nowiki>(.*)<\/nowiki>$,', $value, $matches))
				$value = $matches[1];
			$wgOut->addHTML( "<tr><td>" . $wgOut->parse($this->basestrings[$idx]) . "</td>");
			$wgOut->addHTML( "<td style='width:70%'><input name='wikytsstring$idx' style='width:100%' value=\"" .
				str_replace('"', '&quot;', $value) . "\"/></td></tr>" );
		}
		$wgOut->addHTML( "</table>" );
		$wgOut->addHTML( "<input type='submit' name='wikytssave' value='Save'/>" );
		$wgOut->addHTML( " <a href=\"" . $wgTitle->getlocalUrl("wikytslang=" . $this->currentlang) . "\">Cancel</a>" );
		$wgOut->addHTML( "</form>" );
	}

	function langChooser() {
		global $wgOut, $wgTitle, $wgRequest;

		$this->currentlang =  $wgRequest->getVal("wikytslang");

		$wgOut->addHTML( "<select onchange=\"location=this.value;\">" );
		$wgOut->addHTML( "<option value=\"" . $wgTitle->getlocalUrl() . "\">...</option>" );
		foreach($this->langs as $lang) {
			$wgOut->addHTML( "<option " );
			if ($lang == $this->currentlang) {
				$wgOut->addHTML( "selected " );
			}
			$wgOut->addHTML( "value=\"" . $wgTitle->getlocalUrl("wikytslang=$lang" ) . "\">" . $lang . "</option>" );
		}
		$wgOut->addHTML( "</select>" );
	}

	function listStrings() {
		global $wgOut, $wgTitle;

		$wgOut->addHTML( '<script type="text/javascript">
			function selectall() {
				var inputs = document.getElementsByName("wikytsidx[]");
				for(var i = 0;i < inputs.length;i++)
					inputs[i].checked = "checked";
			}
			function selectnone() {
				var inputs = document.getElementsByName("wikytsidx[]");
				for(var i = 0;i < inputs.length;i++)
					inputs[i].checked = "";
			}
		</script>' );

		$wgOut->addHTML( "<form method='POST'>" );

		$wgOut->addHTML( 'Select: <a href="javascript:selectall();">all</a> / <a href="javascript:selectnone();">none</a>' );
		$wgOut->addHTML( "<input type='submit' value='Edit selected'/>" );

		$wgOut->addHTML( "<table>" );
		for($i = 0;$i < count($this->basestrings);$i++) {
			$wgOut->addHTML( "<tr>" );
			$wgOut->addHTML( "<td><input name=\"wikytsidx[]\" type='checkbox' value='$i'/></td>" );
			$wgOut->addHTML( "<td>" . $wgOut->parse($this->basestrings[$i]) . "</td>" );
			$wgOut->addHTML( "<td>" . $wgOut->parse($this->strings[strtolower($this->basestrings[$i])]) . "</td>");
			$wgOut->addHTML( "</tr>" );
		}
		$wgOut->addHTML( "</table>" );

		$wgOut->addHTML( "</form>" );
	}

	function save() {
		global $wgWikytsTitle;

		$data = "{|\n";
		$data .= " !en!!" . $this->currentlang . "\n";

		foreach($this->basestrings as $string) {
			$data .= " |-\n";
			$data .= " |" . $string . "||" . $this->strings[strtolower($string)] . "\n";
		}
		$data .= " |}";

		$title = Title::newFromText($wgWikytsTitle . "/" . $this->currentlang); 
		$article = new Article($title);
		$article->doEdit($data, "Wikyts: Updated " . $this->currentlang . " translation");
	}

	function fillLangs($type, $linidx, $colidx, $value) {
		if (($type != "|") or ($colidx != 0)) return;

		$this->langs[] = $value;
	}

	function fillBaseStrings($type, $linidx, $colidx, $value) {
		if (($type != "|") or ($colidx != 0)) return;

		$this->basestrings[] = $value;
	}

	function fillStrings($type, $linidx, $colidx, $value) {
		if ($type != "|") return;

		switch($colidx) {
			case 0:
				$this->currentkey = $value;
				break;
			case 1:
				$this->strings[strtolower($this->currentkey)] = $value;
				break;
		}
	}

	static function parseWikiArray($page, $object, $method) {
		$title = Title::newFromText($page); 
		$article = new Article($title);
		$content = $article->getContent();

		$start = strpos($content, "{|") + 2;
		$end = strpos($content, "|}");
		$array = substr($content, $start, $end - $start);
		$lines = explode("|-", $array);

		$i = 0;
		foreach($lines as $tmp) {
			$line = trim($tmp);
			$type = $line[0];
			$line = trim(substr($line, 1));

			$cells = explode($type . $type, $line);
			for($j = 0;$j < count($cells);$j++) {
				$cell = trim($cells[$j]);
				call_user_func(array($object, $method), $type, $i, $j, $cell);
			}
			$i++;
		}
	}
}

