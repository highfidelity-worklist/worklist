<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
require('class/Session.class.php');

session::check();

function session_real_decode($str)
{
  $str = (string)$str;

  $endptr = strlen($str);
  $p = 0;

  $serialized = '';
  $items = 0;
  $level = 0;

  while ($p < $endptr) {
    $q = $p;
    while ($str[$q] != PS_DELIMITER)
      if (++$q >= $endptr) break 2;

    if ($str[$p] == PS_UNDEF_MARKER) {
      $p++;
      $has_value = false;
    } else {
      $has_value = true;
    }

    $name = substr($str, $p, $q - $p);
    $q++;

    $serialized .= 's:' . strlen($name) . ':"' . $name . '";';

    if ($has_value) {
      for (;;) {
	$p = $q;
	switch ($str[$q]) {
	case 'N': /* null */
	case 'b': /* boolean */
	case 'i': /* integer */
	case 'd': /* decimal */
	  do $q++;
	  while ( ($q < $endptr) && ($str[$q] != ';') );
	  $q++;
	  $serialized .= substr($str, $p, $q - $p);
	  if ($level == 0) break 2;
	  break;
	case 'R': /* reference  */
	  $q+= 2;
	  for ($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
	  $q++;
	  $serialized .= 'R:' . ($id + 1) . ';'; /* increment pointer because of outer array */
	  if ($level == 0) break 2;
	  break;
	case 's': /* string */
	  $q+=2;
	  for ($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
	  $q+=2;
	  $q+= (int)$length + 2;
	  $serialized .= substr($str, $p, $q - $p);
	  if ($level == 0) break 2;
	  break;
	case 'a': /* array */
	case 'O': /* object */
	  do $q++;
	  while ( ($q < $endptr) && ($str[$q] != '{') );
	  $q++;
	  $level++;
	  $serialized .= substr($str, $p, $q - $p);
	  break;
	case '}': /* end of array|object */
	  $q++;
	  $serialized .= substr($str, $p, $q - $p);
	  if (--$level == 0) break 2;
	  break;
	default:
	  return false;
	}
      }
    } else {
      $serialized .= 'N;';
      $q+= 2;
    }
    $items++;
    $p = $q;
  }
  return @unserialize( 'a:' . $items . ':{' . $serialized . '}' );
}
