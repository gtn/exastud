<?php
// This file is part of Exabis Competencies
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Competencies is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

namespace block_exastud\common;

defined('MOODLE_INTERNAL') || die();

class url extends \moodle_url {
	public static function create($url, array $params = null, $anchor = null) {
		return new static($url, $params, $anchor);
	}

	/**
	 *
	 * @param array $overrideparams new attributes for object
	 * @return self
	 */
	public function copy(array $overrideparams = null) {
		$object = new static($this);
		if ($overrideparams) {
			$object->params($overrideparams);
		}

		return $object;
	}

	protected function merge_overrideparams(array $overrideparams = null) {
		$params = parent::merge_overrideparams($overrideparams);

		$overrideparams = (array)$overrideparams;
		foreach ($overrideparams as $key => $value) {
			if ($value === null) {
				unset($params[$key]);
			}
		}

		return $params;
	}

	public function params(array $params = null) {
		parent::params($params);

		$params = (array)$params;
		foreach ($params as $key => $value) {
			if ($value === null) {
				unset($this->params[$key]);
			}
		}

		return $this->params;
	}
}

abstract class event extends \core\event\base {

	protected static function prepareData(array &$data) {
		if (!isset($data['contextid']) && isset($data['courseid'])) {
			if ($data['courseid']) {
				$data['contextid'] = \context_course::instance($data['courseid'])->id;
			} else {
				$data['contextid'] = \context_system::instance()->id;
			}
		}
	}

	static function log(array $data) {
		static::prepareData($data);

		return static::create($data)->trigger();
	}
}

class moodle_exception extends \moodle_exception {
	function __construct($errorcode, $module = '', $link = '', $a = null, $debuginfo = null) {

		// try to get local error message (use namespace as $component)
		if (empty($module)) {
			if (get_string_manager()->string_exists($errorcode, _plugin_name())) {
				$module = _plugin_name();
			}
		}

		return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
	}
}

class SimpleXMLElement extends \SimpleXMLElement {
	/**
	 * Adds a child with $value inside CDATA
	 * @param string $name
	 * @param mixed $value
	 * @return SimpleXMLElement
	 */
	public function addChildWithCDATA($name, $value = null) {
		$new_child = $this->addChild($name);

		if ($new_child !== null) {
			$node = dom_import_simplexml($new_child);
			$no = $node->ownerDocument;
			$node->appendChild($no->createCDATASection($value));
		}

		return $new_child;
	}

	public static function create($rootElement) {
		return new static('<?xml version="1.0" encoding="UTF-8"?><'.$rootElement.' />');
	}

	public function addChildWithCDATAIfValue($name, $value = null) {
		if ($value) {
			return $this->addChildWithCDATA($name, $value);
		} else {
			return $this->addChild($name, $value);
		}
	}

	public function addChild($name, $value = null, $namespace = null) {
		if ($name instanceof SimpleXMLElement) {
			$newNode = $name;
			$node = dom_import_simplexml($this);
			$newNode = $node->ownerDocument->importNode(dom_import_simplexml($newNode), true);
			$node->appendChild($newNode);

			// return last child, this is the added child!
			$children = $this->children();

			return $children[$children->count() - 1];
		} else {
			return parent::addChild($name, $value, $namespace);
		}
	}

	public function asPrettyXML() {
		$dom = dom_import_simplexml($this)->ownerDocument;
		$dom->formatOutput = true;

		return $dom->saveXML();
	}
}

abstract class exadb extends \moodle_database {
	/**
	 * @param string $table
	 * @param array|object $data
	 * @param array|null $where
	 * @return null|bool|object
	 */
	public function update_record($table, $data, $where = null) {
	}

	public function insert_or_update_record($table, $data, $where = null) {
	}
}

class exadb_forwarder {
	function __call($func, $args) {
		global $DB;

		if (method_exists($DB, $func)) {
			// in exadb class
			return call_user_func_array([$DB, $func], $args);
		}

		throw new \coding_exception(" Call to undefined method g::\$DB->$func");
	}
}

class exadb_extender extends exadb_forwarder {

	/**
	 * @param string $table
	 * @param array|object $data
	 * @param array|null $where
	 * @return null|bool|object
	 */
	public function update_record($table, $data, $where = null) {
		if ($where === null) {
			return parent::update_record($table, $data);
		}

		$where = (array)$where;
		$data = (array)$data;

		if ($dbItem = $this->get_record($table, $where)) {
			if ($data) {
				$data['id'] = $dbItem->id;
				parent::update_record($table, (object)$data);
			}

			return (object)($data + (array)$dbItem);
		}

		return null;
	}

	/**
	 * @param $table
	 * @param $data
	 * @param null $where
	 * @return object
	 * @throws moodle_exception
	 */
	public function insert_or_update_record($table, $data, $where = null) {
		$data = (array)$data;

		if ($dbItem = $this->get_record($table, $where !== null ? $where : $data)) {
			if (empty($data)) {
				throw new moodle_exception('$data is empty');
			}

			$data['id'] = $dbItem->id;
			$this->update_record($table, (object)$data);

			return (object)($data + (array)$dbItem);
		} else {
			unset($data['id']);
			if ($where !== null) {
				$data = $data + $where; // first the values of $data, then of $where, but don't override $data
			}
			$id = $this->insert_record($table, (object)$data);
			$data['id'] = $id;

			return (object)$data;
		}
	}
}

class param {
	public static function clean_object($values, $definition) {
		if (!is_object($values) && !is_array($values)) {
			return null;
		}

		// some value => type
		$ret = new \stdClass;
		$values = (object)$values;
		$definition = (array)$definition;

		foreach ($definition as $key => $valueType) {
			$value = isset($values->$key) ? $values->$key : null;

			$ret->$key = static::_clean($value, $valueType);
		}

		return $ret;
	}

	public static function clean_array($values, $definition) {
		$definition = (array)$definition;

		if (is_object($values)) {
			$values = (array)$values;
		} elseif (!is_array($values)) {
			return array();
		}

		$keyType = key($definition);
		$valueType = reset($definition);

		// allow clean_array(PARAM_TEXT): which means PARAM_INT=>PARAM_TEXT
		if ($keyType === 0) {
			$keyType = PARAM_SEQUENCE;
		}

		if ($keyType !== PARAM_INT && $keyType !== PARAM_TEXT && $keyType !== PARAM_SEQUENCE) {
			print_error('wrong key type: '.$keyType);
		}

		$ret = array();
		foreach ($values as $key => $value) {
			$value = static::_clean($value, $valueType);
			if ($value === null) continue;

			if ($keyType == PARAM_SEQUENCE) {
				$ret[] = $value;
			} else {
				$ret[clean_param($key, $keyType)] = $value;
			}
		}

		return $ret;
	}

	protected static function _clean($value, $definition) {
		if (is_object($definition)) {
			return static::clean_object($value, $definition);
		} elseif (is_array($definition)) {
			return static::clean_array($value, $definition);
		} else {
			return clean_param($value, $definition);
		}
	}

	public static function get_param($parname) {
		// POST has precedence.
		if (isset($_POST[$parname])) {
			return $_POST[$parname];
		} else if (isset($_GET[$parname])) {
			return $_GET[$parname];
		} else {
			return null;
		}
	}

	public static function get_required_param($parname) {
		$param = static::get_param($parname);

		if ($param === null) {
			throw new moodle_exception('param not found: '.$parname);
		}

		return $param;
	}

	public static function optional_array($parname, $definition) {
		$param = static::get_param($parname);

		if ($param === null) {
			return array();
		} else {
			return static::clean_array($param, $definition);
		}
	}

	public static function required_array($parname, $definition) {
		$param = static::get_required_param($parname);

		return static::clean_array($param, $definition);
	}

	public static function optional_object($parname, $definition) {
		$param = static::get_param($parname);

		if ($param === null) {
			return null;
		} else {
			return static::clean_object($param, $definition);
		}
	}

	public static function required_object($parname, $definition) {
		$param = static::get_required_param($parname);

		return static::clean_object($param, $definition);
	}

	public static function required_json($parname, $definition = null) {
		$data = required_param($parname, PARAM_RAW);

		$data = json_decode($data, true);
		if ($data === null) {
			print_error('missingparam', '', '', $parname);
		}

		if ($definition === null) {
			return $data;
		} else {
			return static::_clean($data, $definition);
		}
	}
}

/**
 * @property string $wwwroot moodle url
 * @property string $dirroot moodle path
 * @property string $libdir lib path
 */
class _globals_dummy_CFG {
}

class globals {
	/**
	 * @var exadb
	 */
	public static $DB;

	/**
	 * @var \moodle_page
	 */
	public static $PAGE;

	/**
	 * @var \core_renderer
	 */
	public static $OUTPUT;

	/**
	 * @var \stdClass
	 */
	public static $COURSE;

	/**
	 * @var \stdClass
	 */
	public static $USER;

	/**
	 * @var \stdClass
	 */
	public static $SITE;

	/**
	 * @var _globals_dummy_CFG
	 */
	public static $CFG;

	public static function init() {
		global $PAGE, $OUTPUT, $COURSE, $USER, $CFG, $SITE;
		globals::$DB = new exadb_extender();
		globals::$PAGE =& $PAGE;
		globals::$OUTPUT =& $OUTPUT;
		globals::$COURSE =& $COURSE;
		globals::$USER =& $USER;
		globals::$CFG =& $CFG;
		globals::$SITE =& $SITE;
	}
}

globals::init();

function _plugin_name() {
	return preg_replace('!\\\\.*$!', '', __NAMESPACE__); // the \\\\ syntax matches a \ (backslash)!
}

call_user_func(function() {
	if (!globals::$CFG->debugdeveloper) {
		return;
	}

	$lang = current_language();
	$langDir = dirname(__DIR__).'/lang';
	$totalFile = $langDir.'/total.php';
	$langFile = $langDir.'/'.$lang.'/'._plugin_name().'.php';

	if (file_exists($totalFile) && file_exists($langFile) && ($time = filemtime($totalFile)) != filemtime($langFile) && is_writable($langFile)) {
		// regenerate
		$totalLanguages = require $totalFile;

		// get copyright
		$copyright = file_get_contents($totalFile);
		if (!preg_match('!(//.*\r?\n)+!', $copyright, $matches)) {
			throw new moodle_exception('copyright not found');
		} else {
			$copyright = $matches[0];
		}

		$byLang = [];

		foreach ($totalLanguages as $key => $langs) {
			if (!$langs) {
				$byLang['de'][$key] = null;
				$byLang['en'][$key] = null;
				continue;
			}
			foreach ($langs as $lang => $value) {
				$byLang[$lang][$key] = $value;
			}
		}

		foreach ($byLang as $lang => $strings) {
			$output = '<?php'."\n{$copyright}\n";

			foreach ($strings as $key=>$value) {
				if (strpos($key, '===') === 0) {
					// group
					$output .= "\n\n// ".trim($key, ' =')."\n";
				} else {
					$output .= '$string['.var_export($key, true).'] = '.var_export($value, true).";\n";
				}
			}

			// add local.config languages if present
			if (file_exists(dirname(__DIR__)."/local.config/lang.".$lang.".php")){
				$output .= '

// load local langstrings
if (file_exists(__DIR__."/../../local.config/lang.".basename(__DIR__).".php")){
	require __DIR__."/../../local.config/lang.".basename(__DIR__).".php";
}
';
			}

			file_put_contents($langDir.'/'.$lang.'/'._plugin_name().'.php', $output);
			touch($langDir.'/'.$lang.'/'._plugin_name().'.php', $time);
		}
	}
});

/**
 * get a language string from current plugin or else from global language strings
 * @param $identifier
 * @param null $component
 * @param null $a
 * @return string
 */
function get_string($identifier, $component = null, $a = null) {
	$manager = get_string_manager();

	if ($component === null)
		$component = _plugin_name();

	if ($manager->string_exists($identifier, $component))
		return $manager->get_string($identifier, $component, $a);

	return $manager->get_string($identifier, '', $a);
}

function print_error($errorcode, $module = 'error', $link = '', $a = null, $debuginfo = null) {
	throw new moodle_exception($errorcode, $module, $link, $a, $debuginfo);
}

function _t_check_identifier($string) {
	if (preg_match('!^([^:]+):(.*)$!s', $string, $matches))
		return $matches;
	else
		return null;
}

function _t_parse_string($string, $a) {
	// copy from moodle/lib/classes/string_manager_standard.php
	// Process array's and objects (except lang_strings).
	if (is_array($a) or (is_object($a) && !($a instanceof \lang_string))) {
		$a = (array)$a;
		$search = array();
		$replace = array();
		foreach ($a as $key => $value) {
			if (is_int($key)) {
				// We do not support numeric keys - sorry!
				continue;
			}
			if (is_array($value) or (is_object($value) && !($value instanceof \lang_string))) {
				// We support just string or lang_string as value.
				continue;
			}
			$search[] = '{$a->'.$key.'}';
			$replace[] = (string)$value;
		}
		if ($search) {
			$string = str_replace($search, $replace, $string);
		}
	} else {
		$string = str_replace('{$a}', (string)$a, $string);
	}

	return $string;
}

/**
 * translator function
 */
function trans() {

	$origArgs = $args = func_get_args();

	$languagestrings = null;
	$identifier = '';
	$a = null;

	if (empty($args)) {
		print_error('no args');
	}

	$arg = array_shift($args);
	if (is_string($arg) && !_t_check_identifier($arg)) {
		$identifier = $arg;

		$arg = array_shift($args);
	}

	if ($arg === null) {
		// just id submitted
		$languagestrings = array();
	} elseif (is_array($arg)) {
		$languagestrings = $arg;
	} elseif (is_string($arg) && $matches = _t_check_identifier($arg)) {
		$languagestrings = array($matches[1] => $matches[2]);
	} else {
		print_error('wrong args: '.print_r($origArgs, true));
	}

	if (!empty($args)) {
		$a = array_shift($args);
	}

	// parse $languagestrings
	foreach ($languagestrings as $lang => $string) {
		if (is_number($lang)) {
			if ($matches = _t_check_identifier($string)) {
				$languagestrings[$matches[1]] = $matches[2];
			} else {
				print_error('wrong language string: '.$origArgs);
			}
		}
	}

	if (!empty($args)) {
		print_error('too many args: '.print_r($origArgs, true));
	}

	$lang = current_language();
	if (isset($languagestrings[$lang])) {
		return _t_parse_string($languagestrings[$lang], $a);
	}

	$manager = get_string_manager();
	$component = _plugin_name();
	$identifier = reset($languagestrings);
	$identifier = key($languagestrings).':'.$identifier;

	if ($manager->string_exists($identifier, $component)) {
		return $manager->get_string($identifier, $component, $a);
	}
	$identifier = reset($languagestrings);
	if ($manager->string_exists($identifier, $component)) {
		return $manager->get_string($identifier, $component, $a);
	}

	if ($languagestrings) {
		return _t_parse_string(reset($languagestrings), $a);
	} else {
		return _t_parse_string($identifier, $a);
	}
}

/**
 * exporting all classes and functions from the common namespace to the plugin namespace
 * the whole part below is done, so eclipse knows the common classes and functions
 */
namespace block_exastud;

function _should_export_class($classname) {
	return !class_exists('\\'.__NAMESPACE__.'\\'.$classname);
}

function _export_function($function) {
	if (!function_exists('\\'.__NAMESPACE__.'\\'.$function)) {
		eval('
			namespace '.__NAMESPACE__.' {
				function '.$function.'() {
					return call_user_func_array(\'\\'.__NAMESPACE__.'\common\\'.$function.'\', func_get_args());
				}
			}
		');
		// return call_user_func_array(__CLASS__.'\common\\'.__FUNCTION__, func_get_args());
	}

	return false;
}

// export classnames, if not already existing
if (_should_export_class('event')) {
	abstract class event extends common\event {
	}
}
if (_should_export_class('moodle_exception')) {
	class moodle_exception extends common\moodle_exception {
	}
}
if (_should_export_class('globals')) {
	class globals extends common\globals {
	}
}
if (_should_export_class('param')) {
	class param extends common\param {
	}
}
if (_should_export_class('SimpleXMLElement')) {
	class SimpleXMLElement extends common\SimpleXMLElement {
	}
}
if (_should_export_class('url')) {
	class url extends common\url {
	}
}

if (_export_function('get_string')) {
	function get_string($identifier, $component = null, $a = null) {
	}
}
if (_export_function('print_error')) {
	function print_error($errorcode, $module = 'error', $link = '', $a = null, $debuginfo = null) {
	}
}
if (_export_function('trans')) {
	function trans() {
	}
}
