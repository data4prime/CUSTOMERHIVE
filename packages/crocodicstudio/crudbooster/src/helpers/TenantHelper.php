<?php
namespace crocodicstudio\crudbooster\helpers;

use \App\Tenant;

class TenantHelper  {

  /**
  *	Encode a tenant name
  *
  * @param string name as user input
  *
  * @return string cleaned name
  */
  public static function domain_name_encode($name) {
    //lower case only
    $name = strtolower(trim($name));
    //delete spaces
    $name = preg_replace('/\s+/', '',$name);
    //lettere accentate
    $name = str_replace('à', 'a',$name);
    $name = str_replace('è', 'e',$name);
    $name = str_replace('é', 'e',$name);
    $name = str_replace('ì', 'i',$name);
    $name = str_replace('ò', 'o',$name);
    $name = str_replace('ù', 'u',$name);
    //remove all special characters
    $name = preg_replace('/[^A-Za-z0-9]/', '', $name);
    //if domain name $name already exists..
    $name = TenantHelper::unique_domain_name($name);
    return $name;
  }

  /**
  * if a tenant with domain name $name already exists,
  * then add a digit at the end of the domain name
  */
  public static function unique_domain_name($name){
    if(Tenant::where('domain_name',$name)->count()>0){
      //.. then ensure unique domain name
      preg_match('/(\d+)$/', $name, $matches);
      if(empty($matches[1])){
        $name .= '1';
      }
      else{
        $new_number = $matches[0]+1;
        $name_without_number = substr($name,0,strlen($matches[0])*-1);
        $name = $name_without_number.$new_number;
      }
      //recursive call
      $name = TenantHelper::unique_domain_name($name);
    }
    else{
      return $name;
    }
  }

}
