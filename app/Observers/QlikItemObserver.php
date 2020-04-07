<?php

namespace App\Observers;

use App\QlikItem;

class QlikItemObserver
{
    /**
     * Listen to the QlikItem deleting event.
     *
     * @param  QlikItem  $qlik_item
     * @return void
     */
    public function deleting(QlikItem $qlik_item)
    {
        var_dump($qlik_item);;
        return false;
    }
   public function delete(QlikItem $qlik_item)
   {
       var_dump($qlik_item);;
       return false;
   }
   public function deleted(QlikItem $qlik_item)
   {
       var_dump($qlik_item);;
       return false;
   }
}
