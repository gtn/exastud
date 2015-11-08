<?php

namespace block_exastud;
/* functions common accross exabis plugins */

defined('MOODLE_INTERNAL') || die();

class url extends \moodle_url {
    /**
     * 
     * @param array $overrideparams new attributes for object
     * @return self
     */
    public function copy(array $overrideparams = null) {
        $class = get_class();
        $object = new $class($this);
        if ($overrideparams) {
            $object->params($overrideparams);
        }
        return $object;
    }
}

class exception extends \moodle_exception {
    function __construct($errorcode, $module='', $link='', $a=NULL, $debuginfo=null) {

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
     * @param unknown $name
     * @param unknown $value
     */
    public function addChildWithCDATA($name, $value = NULL) {
        $new_child = $this->addChild($name);

        if ($new_child !== NULL) {
            $node = dom_import_simplexml($new_child);
            $no   = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($value));
        }

        return $new_child;
    }

    public static function create($rootElement) {
        return new self('<?xml version="1.0" encoding="UTF-8"?><'.$rootElement.' />');
    }

    public function addChildWithCDATAIfValue($name, $value = NULL) {
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

            // return last children, this is the added child!
            $children = $this->children();
            return $children[count($children)-1];
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

class db {
    public static function update_record($table, $data, $where) {
        global $DB;

        $where = (array)$where;
        $data = (array)$data;

        if ($dbItem = $DB->get_record($table, $where)) {
            if ($data) {
                $data['id'] = $dbItem->id;
                $DB->update_record($table, $data);
            }

            return (object)($data + $where);
        }

        return null;
    }

    public static function insert_or_update_record($table, $data, $where = null) {
        global $DB;

        $data = (array)$data;

        if ($dbItem = $DB->get_record($table, $where !== null ? $where : $data)) {
            if ($data) {
                $data['id'] = $dbItem->id;
                $DB->update_record($table, $data);
            }
        } else {
            unset($data['id']);
            if ($where !== null) {
                $data = $data + $where; // first the values of $data, then of $where, but don't override $data
            }
            $id = $DB->insert_record($table, $data);
            $data['id'] = $id;
        }

        return (object)$data;
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

        if (count($definition) != 1) {
            print_error('no array definition');
        }
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
        foreach ($values as $key=>$value) {
            $value = static::_clean($value, $valueType, true);
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

    public static function optional_array($parname, array $definition) {
        $param = static::get_param($parname);

        if ($param === null) {
            return array();
        } else {
            return static::clean_array($param, $definition);
        }
    }

    public static function required_array($parname, array $definition) {
        $param = static::get_param($parname);

        if ($param === null) {
            print_error('param not found: '.$parname);
        } else {
            return static::clean_array($param, $definition);
        }
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
        $param = static::get_param($parname);

        if ($param === null) {
            print_error('param not found: '.$parname);
        } else {
            return static::clean_object($param, $definition);
        }
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

function _plugin_name() {
    return preg_replace('!\\\\.*$!', '', __NAMESPACE__); // the \\\\ syntax matches a \ (backslash)!
}

/**
 * Returns a localized string.
 * This method is neccessary because a project based evaluation is available in the current exastud
 * version, which requires a different naming.
 */
function get_string($identifier, $component = null, $a = null, $lazyload = false) {
    $manager = get_string_manager();

    if ($component === null)
        $component = _plugin_name();

    if ($manager->string_exists($identifier, $component))
        return $manager->get_string($identifier, $component, $a);

    return $manager->get_string($identifier, '', $a);
}

function _t_check_identifier($string) {
    if (preg_match('!^([^:]+):(.*)$!', $string, $matches))
        return $matches;
    else
        return null;
}
function _t_parse_string($string, $a) {
    // copy from moodle/lib/classes/string_manager_standard.php
    // Process array's and objects (except lang_strings).
    if (is_array($a) or (is_object($a) && !($a instanceof lang_string))) {
        $a = (array)$a;
        $search = array();
        $replace = array();
        foreach ($a as $key => $value) {
            if (is_int($key)) {
                // We do not support numeric keys - sorry!
                continue;
            }
            if (is_array($value) or (is_object($value) && !($value instanceof lang_string))) {
                // We support just string or lang_string as value.
                continue;
            }
            $search[]  = '{$a->'.$key.'}';
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
/*
 * translator function
 */
function t() {
    
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
    } elseif ($languagestrings) {
        return _t_parse_string(reset($languagestrings), $a);
    } else {
        return _t_parse_string($identifier, $a);
    }
}
