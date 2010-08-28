<?php

function encode_array($args, $except='')
{
  if(!is_array($args)) return false;
  $c = 0;
  $out = '';

	foreach($except as $key=>$value){
		$args[$key] = $value;
	}
  foreach($args as $name => $value)
  {
    if($c++ != 0) $out .= '&';
    $out .= urlencode("$name").'=';
    if(is_array($value))
    {
      $out .= urlencode(serialize($value));
    }else{
      $out .= urlencode("$value");
    }
  }
  return $out;
}

/*
 * Prints the list of available pages
 */
function pagingList($amount, $getvalues) {
 	$ret = '';
        $i = 1;
        while($i <= $amount) {
                $ret.= ' <a href="?'.encode_array($getvalues, array('page'=>$i)).'">'.$i.'</a> ';
                $i++;
        }
                                                                
        return $ret;  
}
?>