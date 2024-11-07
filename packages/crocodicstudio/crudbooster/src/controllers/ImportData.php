<?php

namespace crocodicstudio\crudbooster\controllers;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ImportData implements ToCollection
{
    public function collection(Collection $rows)
    {
        
    }
}
