<?php
error_reporting(0);
$kbxml = "kb.xml";
if ( get_magic_quotes_gpc() != 0 ) {
	if ( !empty($_GET) ) {
		recstripslashes($_GET);
	}
	if ( !empty($_POST) ) {
		recstripslashes($_POST);
	}
	if ( !empty($_REQUEST) ) {
		recstripslashes($_REQUEST);
	}
}
if ( isset($_REQUEST['action']) ) {
	$kb = new kb($kbxml);
	$kb->action($_REQUEST['action']);
	echo '{failure:true}';
	exit;
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="extjs/resources/css/ext-all.css" />
		<!--link rel="stylesheet" type="text/css" href="extjs/examples/ux/css/RowEditor.css" /-->
		<title>Knowledge Base</title>
		<style type="text/css">
			.icon-item-add {
				background-image: url(add.png) !important;
			}
			.icon-item-delete {
				background-image: url(del.png) !important;
			}
			pre.kb, code.kb {
				color: #338;
				background: #f8f8ff;
				max-height: 200px;
				overflow:hidden;
				overflow-x: hidden;
				overflow-y: auto; 
			}
			p.kb {
				background: #fafafa;
				max-height: 200px;
				overflow:hidden;
				overflow-x: hidden;
				overflow-y: auto;
			}
		</style>
	</head>
	<body>
		<script type="text/javascript" src="extjs/adapter/ext/ext-base.js"></script>
		<script type="text/javascript" src="extjs/ext-all.js"></script>
		<!--script type="text/javascript" src="extjs/examples/ux/RowEditor.js"></script-->
		<script type="text/javascript" src="kb.js"></script>
	</body>
</html>
<?php

/**
 *
 */
class kb {

	/**
	 *
	 * @var int 
	 */
	public $versions_saved = 4;

	/**
	 *
	 * @var string
	 */
	protected $filename = "";

	/**
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 *
	 * @var array
	 */
	protected $tags = array();

	/**
	 *
	 * @var string
	 */
	protected $parsedata = "";

	/**
	 *
	 * @var int
	 */
	protected $maxint = 0;

	/**
	 *
	 * @var string
	 */
	protected $parseitem = array();

	/**
	 *
	 * @param string $filename
	 */
	public function kb($filename) {
		$this->filename = $filename;
		if ( !file_exists($filename) ) {
			$this->save();
		}
		$this->load();
	}

	/**
	 * 
	 */
	public function load() {
		$this->items = array();
		$parser = xml_parser_create('UTF-8');
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'startXML', 'endXML');
		xml_set_character_data_handler($parser, 'charXML');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parse($parser, file_get_contents($this->filename));
		$this->tags = array_keys($this->tags);
		sort($this->tags);
	}

	/**
	 *
	 * @param <type> $parser
	 * @param <type> $name
	 * @param <type> $attr 
	 */
	protected function startXML($parser, $name, $attr) {
		if ( $name == 'item' ) {
			$this->parseitem = array(
				'created' => '0000-00-00',
				'modified' => '0000-00-00',
				'title' => '',
				'text' => '',
				'id' => '',
				'tags' => array(),
			);
		}
		$this->parsedata = '';
	}

	/**
	 *
	 * @param <type> $parser
	 * @param <type> $name
	 */
	protected function endXML($parser, $name) {
		if ( $name == 'tag' ) {
			$this->parseitem['tags'][] = $this->parsedata;
			$this->tags[$this->parsedata] = 1;
		}
		elseif ( $name == 'item' ) {
			$this->items[$this->parseitem['id']] = $this->parseitem;
			if ( $this->maxint < $this->parseitem['id'] ) {
				$this->maxint = $this->parseitem['id'];
			}
		}
		elseif ( in_array($name, array('modified', 'created', 'title', 'text', 'id')) ) {
			$this->parseitem[$name] = $this->parsedata;
		}
	}

	/**
	 *
	 * @param <type> $parser
	 * @param <type> $data
	 */
	protected function charXML($parser, $data) {
		if ( trim($data) != '' ) {
			$this->parsedata .= $data;
		}
	}

	/**
	 * 
	 */
	public function save() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?><kb>';
		$xml .= "<items>\n";
		usort($this->items, "sortitem");
		foreach ( $this->items as $item ) {
			$xml .= "<item>\n";
			$xml .= '  <id>' . $item['id'] . "</id>\n";
			$xml .= '  <created>' . $item['created'] . "</created>\n";
			$xml .= '  <modified>' . $item['modified'] . "</modified>\n";
			$xml .= '  <title><![CDATA[' . $item['title'] . "]]></title>\n";
			$xml .= '  <text><![CDATA[' . $item['text'] . "]]></text>\n";
			$xml .= "  <tags>\n";
			sort($item['tags']);
			foreach ( $item['tags'] as $tag ) {
				$xml .= '    <tag><![CDATA[' . $tag . "]]></tag>\n";
			}
			$xml .= "  </tags>\n";
			$xml .= "</item>\n";
		}
		$xml .= '</items>';
		$xml .= '</kb>';
		if ( file_exists($this->filename) ) {
			copy($this->filename, $this->filename . '.' . date('Ymd.His') . '.bak');
		}
		$saved = glob($this->filename . '*');
		if ( is_array($saved) ) {
			if ( count($saved) > $this->versions_saved ) {
				for ( $i = 0; $i < count($saved) - $this->versions_saved; $i++ ) {
					@unlink($saved[$i]);
				}
			}
		}
		file_put_contents($this->filename, $xml);
	}

	/**
	 *
	 * @param string $action 
	 */
	public function action($action) {
		switch ( $action ) {
			case 'gettags' : $this->_gettags();
				break;
			case 'getitems' : $this->_getitems();
				break;
			case 'delitem' : $this->_delitem();
				break;
			case 'additem' : $this->_additem();
				break;
			case 'moditem' : $this->_moditem();
				break;
			default: exit;
		}
	}

	/**
	 *
	 */
	protected function _delitem() {
		if ( isset($_REQUEST['items']) ) {
			$id = trim(trim($_REQUEST['items']), '"');
			if ( is_numeric($id) && isset($this->items[$id]) ) {
				unset($this->items[$id]);
				$this->save();
				echo '{success:true}';
				exit;
			}
		}
	}

	/**
	 *
	 */
	protected function _moditem() {
		if ( isset($_REQUEST['items']) ) {
			$json = json_decode($_REQUEST['items'], true);
			if ( is_array($json) && isset($json['id']) && isset($this->items[$json['id']]) ) {
				$item = $this->items[$json['id']];
				$item['modified'] = date('Y-m-d');
				$item['tags'] = array();
				if ( isset($json['title']) ) {
					$item['title'] = $json['title'];
				}
				if ( isset($json['text']) ) {
					$item['text'] = $json['text'];
				}
				if ( isset($json['tags']) && !empty($json['tags']) ) {
					if ( is_array($json['tags']) ) {
						$json['tags'] = implode(',', $json['tags']);
					}
					$item['tags'] = array_map('trim', explode(',', $json['tags']));
				}
				if ( !empty($item['title']) ) {
					$this->items[$item['id']] = $item;
					$this->save();
					echo json_encode(array('success' => true, 'items' => $item));
					exit;
				}
			}
		}
	}

	/**
	 *
	 */
	protected function _additem() {
		$item = array(
			'created' => date('Y-m-d'),
			'modified' => date('Y-m-d'),
			'title' => '',
			'text' => '',
			'id' => $this->maxint + 1,
			'tags' => array(),
		);
		if ( isset($_REQUEST['items']) ) {
			$json = json_decode($_REQUEST['items'], true);
			if ( is_array($json) ) {
				if ( isset($json['title']) ) {
					$item['title'] = $json['title'];
				}
				if ( isset($json['text']) ) {
					$item['text'] = $json['text'];
				}
				if ( isset($json['tags']) && !empty($json['tags']) ) {
					if ( is_array($json['tags']) ) {
						$json['tags'] = implode(',', $json['tags']);
					}
					$item['tags'] = array_map('trim', explode(',', $json['tags']));
				}
			}
			if ( !empty($item['title']) ) {
				$this->items[$item['id']] = $item;
				$this->save();
				echo json_encode(array('success' => true, 'items' => $item));
				exit;
			}
		}
	}

	/**
	 *
	 */
	protected function _gettags() {
		$a = array();
		foreach ( $this->tags as $tag ) {
			$a[] = array('tag' => $tag);
		}
		echo json_encode($a);
		exit;
	}

	/**
	 *
	 */
	protected function _getitems() {
		$q = strtolower(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');
		$tags = strtolower(isset($_REQUEST['tags']) ? "\t" . $_REQUEST['tags'] . "\t" : '');
		$result = array();
		if ( !empty($q) || !empty($tags) ) {
			foreach ( $this->items as $item ) {
				$c1 = strtolower($item['title']);
				$c2 = strtolower($item['text']);
				$ok = false;
				if ( empty($item['tags']) ) {
					$ok = true;
				}
				else {
					foreach ( $item['tags'] as $tag ) {
						if ( strpos($tags, "\t" . strtolower($tag) . "\t") !== false ) {
							if ( empty($q) ) {
								$ok = true;
							}
							elseif ( strpos($c1, $q) !== false ) {
								$ok = true;
							}
							elseif ( strpos($c2, $q) !== false ) {
								$ok = true;
							}
							elseif ( strpos($tag, $q) !== false ) {
								$ok = true;
							}
						}
					}
				}
				if ( $ok ) {
					$result[] = $item;
				}
			}
		}
		echo json_encode(array('items' => $result));
		exit;
	}

}

function sortitem($a, $b) {
	$a = strtolower($a['title']);
	$b = strtolower($b['title']);
	return strcmp($a, $b);
}

function recstripslashes(& $array) {
	if ( is_array($array) ) {
		foreach ( $array as $key => $value ) {
			if ( is_array($value) ) {
				recstripslashes($array[$key]);
			}
			else {
				$array[$key] = stripslashes($value);
			}
		}
	}
	else {
		$array = stripslashes($array);
	}
}
?>
