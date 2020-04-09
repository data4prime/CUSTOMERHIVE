<?php
namespace crocodicstudio\crudbooster\helpers;

class ModuleHelper  {

  /**
  *	Encode a column or table name
  *
  * @param string name as user input
  *
  * @return string cleaned name
  */
  public static function sql_name_encode($name) {
    //lower case only
    $name = strtolower( trim($name) );
    //one or multiple spaces to single underscore
    $name = preg_replace('/\s+/', '_',$name);
    //spaces to underscores
    // $name = str_replace(' ', '_', $name );
    //remove all special characthers except underscore
    return preg_replace('/[^A-Za-z0-9\_]/', '', $name);
  }

  /**
  *	Decode a column or table name into a more human friendly label
  *
  * @param string name as user input
  *
  * @return string cleaned name
  */
  public static function sql_name_decode($name) {
    //single underscore to spaces
    $name = str_replace('_', ' ',$name);
    //first letter of each word uppercase
    return ucwords($name);
  }

}
