<?php namespace JATSParser;

require_once("Par.inc.php");
require_once("Listing.inc.php");
require_once("Table.inc.php");
require_once("Figure.inc.php");

use JATSParser\Table as Table;
use JATSParser\Figure as Figure;
use JATSParser\Listing as Listing;
use JATSParser\Par as Par;

class Section implements JATSElement {

	/* section title */
	private $title;

	/* type os a section: 1, 2, 3, 4 -> what means section, subsection, subsubsection, etc. */
	private $type;

	private $content;

	private $hasSections;

	private $childSectionsTitles = array();

	function __construct(\DOMElement $section) {

		$xpath = Document::getXpath();

		$this->extractTitle($section, $xpath);
		$this->extractType($section, $xpath);
		$this->ifHasSections($section, $xpath);
		$this->extractContent($section,$xpath);
	}

	public function getTitle() : string {
		return $this->title;
	}

	public function getContent() : array {
		return $this->content;
	}

	public function getType() : int {
		return $this->type;
	}

	public function hasSections() : bool {
		return $this->hasSections;
	}

	private function extractTitle(\DOMElement $section, \DOMXPath $xpath) {
		$titleElements = $xpath->query("title[1]", $section);
		if ($titleElements->length > 0) {
			foreach ($titleElements as $titleElement) {
				$this->title = $titleElement->nodeValue;
			}
		}
	}

	private function extractType(\DOMElement $section, \DOMXPath $xpath) {
		$parentElements = $xpath->query("parent::sec", $section);
		if (!is_null($parentElements)) {
			$this->type += 1;
			foreach ($parentElements as $parentElement) {
				$this->extractType($parentElement, $xpath);
			}
		}
	}

	private function ifHasSections (\DOMElement $section, \DOMXPath $xpath) {
		$childSections = $xpath->query("sec", $section);
		if ($childSections->length > 0) {
			$this->hasSections = true;
		} else {
			$this->hasSections = false;
		}
		$sectionsTitles = $xpath->query("sec/title", $section);
		foreach ($sectionsTitles as $sectionsTitle) {
			$this->childSectionsTitles[] = $sectionsTitle->textContent;
		}
	}

	private function extractContent (\DOMElement $section, \DOMXPath $xpath) {
		$content = array();
		$sectionNodes = $xpath->evaluate("./node()", $section);
		foreach ($sectionNodes as $sectionElement) {
			switch ($sectionElement->nodeName) {
				case "p":
					$par = new Par($sectionElement);
					$content[] = $par;
					break;
				case "list":
					$list = new Listing($sectionElement);
					$content[] = $list;
					break;
				case "table-wrap":
					$table = new Table($sectionElement);
					$content[] = $table;
					break;
				case "fig":
					$figure = new Figure($sectionElement);
					$content[] = $figure;
					break;
			}
		}
		$this->content = $content;
	}

}