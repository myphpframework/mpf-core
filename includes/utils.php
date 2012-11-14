<?php

function array_merge_recursive_simple() {
  if (func_num_args() < 2) {
    trigger_error(__FUNCTION__ . ' needs two or more array arguments', E_USER_WARNING);
    return;
  }

  $arrays = func_get_args();
  $merged = array();
  while ($arrays) {
    $array = array_shift($arrays);
    if (!is_array($array)) {
      trigger_error(__FUNCTION__ . ' encountered a non array argument', E_USER_WARNING);
      return;
    }
    
    if (!$array) {
      continue;
    }
    
    foreach ($array as $key => $value) {
      if (is_string($key)) {
        if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
          $merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
        }
        else {
          $merged[$key] = $value;
        }
      }
      else {
        $merged[] = $value;
      }
    }
  }
  return $merged;
}

function arrayToObject($array) {
  foreach($array as $key => $value){
    if(is_array($value)){
      $array[$key] = arrayToObject($value);
    }
  }

  return (object) $array;
}


function mpf_parse_ini_file ($file) {
  $currentSection = '';
  $config = array();
  foreach (file($file) as $lineNumber => $line) {
    if ($line == '') {
      continue;
    }
    
    preg_match('/\[\s{0,}([a-z]{4,}\s:\s[a-z]{4,})\s{0,}\]/i', $line, $matches);
    if (!empty($matches) && $currentSection != $matches[1]) {
      $currentSection = trim($matches[1]);
      $config[ $currentSection ] = array();
    }
    
    preg_match('/\[\s{0,}([a-z]{4,})\s{0,}\]/i', $line, $matches);
    if (!empty($matches) && $currentSection != $matches[1]) {
      $currentSection = trim($matches[1]);
      $config[ $currentSection ] = array();
    }
    
    preg_match('/^([a-z0-9\._]*)\s\=\s(.*)$/i', $line, $matches);
    if (!empty($matches)) {
      $value = trim($matches[2]);
      if (is_numeric($value)) {
        $tmp = (int)$value;
        if ((float)$value != (float)$tmp) {
          $value = (float)$value;
        }
        else {
          $value = (int)$value;
        }
      }
      else {
        $value = (string)$value;
        if (in_array($value, array('false', 'False', 'FALSE', false), true)) {
          $value = false;
        }
        elseif (in_array($value, array('true', 'True', 'TRUE', true), true)) {
          $value = true;
        }

        if (in_array($value, array('null', 'Null', 'NULL', null), true)) {
          $value = null;
        }
      }

      $config[ $currentSection ][ trim($matches[1]) ] = $value;
    }
  }
  return $config;
}