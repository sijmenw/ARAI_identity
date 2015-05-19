<?php
/**
 * Created by PhpStorm.
 * User: Sijmen
 * Date: 19-5-15
 * Time: 20:52
 */

function parseResponseAmount($str) {
    $i = 0;

    $startAmountParse = false;

    $amount = "";

    $strlen = strlen($str);

    while ($i++ < $strlen) {
        $char = $str[$i];
        if ($char == ':') {
            $startAmountParse = true;
            continue;
        } else if ($char == ',') {
            $startAmountParse = false;
            break;
        }
        if ($startAmountParse == true) {
            $amount .= $char;
        }
    }
    return intval($amount);
}

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