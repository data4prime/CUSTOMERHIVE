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
    list($version, $garbage) = explode('-',$verbose_version);
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
