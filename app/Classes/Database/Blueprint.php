<?php

namespace App\Classes\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint
{
  // TODO #RAMA tentativo di creare una funzione callback per creare le colonne dinamicamente
  // si è rivelata non necessaria ma la tengo per riutilizzare l'idea di chiamare dalla custom class Blueprint
  // una funzione del genitore passando il nome del metodo originale come parametro del metodo custom
  // potrebbe essere utile per aggiungere comportamenti prima della chimata
  // public function call_dynamic_method($method_name) {
  //   echo 'test MyBlueprint'.$method_name;exit;
  // }

   /**
    * Add default columns to the table.
    *
    * MySQL automatically saves creation and update datetimes
    *
    */
    public function defaults(): void
    {
      $this->increments('id');
      $this->unsignedInteger('group')->nullable();
      $this->unsignedInteger('tenant')->nullable();
      $this->dateTime('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
      $this->unsignedInteger('created_by')->nullable();
      $this->dateTime('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
      $this->unsignedInteger('updated_by')->nullable();
      $this->dateTime('deleted_at')->nullable();
      $this->unsignedInteger('deleted_by')->nullable();
    }
}
