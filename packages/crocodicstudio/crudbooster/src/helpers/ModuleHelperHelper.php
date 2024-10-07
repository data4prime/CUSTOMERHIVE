<?php

namespace crocodicstudio\crudbooster\helpers;

use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Validator;


class ModuleHelperHelper
{

  public static function getUrl($mod) {

    if (isset($mod->id)) {
        $helper = DB::table('module_helpers')->where('module', $mod->id)->first();
    }

    if (isset($helper->url)) {
        return $helper->url;
    }

    return "";

  }


  public static function getUrlCV($mod) {

    if (isset($mod->id)) {
        $helper = DB::table($mod->table_name)->first();
    }

    if (isset($helper->url_help)) {
        return $helper->url_help;
    }

    return "";

  }

}
