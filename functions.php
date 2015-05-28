<?php
/**
 * Created by PhpStorm.
 * User: Sijmen
 * Date: 19-5-15
 * Time: 20:52
 */

function parseResponseResults($str) {
    $arr = array();

    $startArrayParse = false;

    $element = "";

    $strlen = strlen($str);

    for ($i = 0; $i < $strlen; $i++) {
        $char = $str[$i];
        if ($char == '[') {
            $startArrayParse = true;
            continue;
        } else if ($char == ']') {
            array_push($arr, $element);
            $startArrayParse = false;
        }
        if ($startArrayParse == true) {
            // Ignore these characters
            if ($char == '"') {
                continue;
            }
            // Go to next value
            if ($char == ',') {
                array_push($arr, $element);
                $element = "";
                continue;
            }
            $element .= $char;
        }
    }
    return $arr;
}

function printArray($arr) {
    if (!is_array($arr)) {
        return "";
    }
    $arr_str = "";
    recursiveArrayToString($arr, $arr_str);
    return $arr_str;
}

function recursiveArrayToString($arr, &$str) {
    $str .= "<ul>";
    foreach($arr as $key => $value) {
        $str .= "<li>" . $key;
        if (is_array($value)) {
            recursiveArrayToString($value, $str);
        } else {
            $str .= ": " . $value;
        }
        $str .= "</li>";
    }
    $str .= "</ul>";
}

function addSameAsStatements($uri, $sameAs, $arr, &$uri_arr) {
    if (!is_array($arr) || count($arr) < 1) {
        return "";
    }
    foreach($arr as $key => $value) {
        if ($key == $uri) {
            foreach($value as $key2 => $value2) {
                if ($key == $sameAs) {
                    foreach($value2 as $value3) {
                        if (!in_array($value3['value'], $uri_arr)) {
                            $uri_arr[] = $value3['value'];
                        }
                    }
                }
            }
        }
    }
}

function getPName($uri) {
	$offset = 0;
	for ($i = strlen($uri) - 1; $i >= 0; $i--) {
		if ($uri[$i] == '#' || $uri[$i] == '/') {
			return substr($uri, $offset);
		} else {
			$offset--;
		}
	}
	return '';
}
