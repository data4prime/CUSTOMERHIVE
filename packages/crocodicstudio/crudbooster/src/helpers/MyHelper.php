<?php
namespace crocodicstudio\crudbooster\helpers;

use \Tremby\LaravelGitVersion\GitVersionHelper;

class MyHelper  {

  /**
  *	get current git version.
  * GitVersionHelper::getVersion returns dirty version of git describe with commit id
  * here i strip that from the end of the version number
  */
  public static function version() {
    $verbose_version = GitVersionHelper::getVersion();
    //$verbose_version = "";
    if(strpos($verbose_version,'-')<0){
      //no dirty part
      return $verbose_version;
    }
    //list($version, $garbage) = explode('-',$verbose_version);

    $exploded = explode('.',$verbose_version);

    if (isset($exploded[0])) {
      $version = $exploded[0];
    }  else {
      $version = '';
    }




    if (isset($exploded[1])) {
      $garbage = $exploded[1];
    } else {
      $garbage = '';
    }
    
    return $version;
  }

  public static function is_int($var)
  {
    if(filter_var($var, FILTER_VALIDATE_INT) === false AND (int)$var != $var)
    {
      return false;
    }
    return true;
  }

}
