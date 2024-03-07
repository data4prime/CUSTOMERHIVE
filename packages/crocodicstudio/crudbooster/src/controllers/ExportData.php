<?php

namespace crocodicstudio\crudbooster\controllers;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\View as LaravelView;

class ExportData implements FromView, ShouldAutoSize
{
    protected $response;
    protected $filename;
    protected $paperorientation;

    public function __construct($response, $filename, $paperorientation)
    {
        $this->response = $response;
        $this->filename = $filename;
        $this->paperorientation = $paperorientation;
    }

    public function view(): View
    {
        return LaravelView::make('crudbooster::export', [
            'response' => $this->response,
        ]);
    }
}
