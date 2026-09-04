<?php

namespace App\Http\Controllers\System;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Illuminate\Support\Facades\View as LaravelView;

class ExportData implements FromView, ShouldAutoSize, WithCustomCsvSettings
{
    protected $response;
    protected $filename;
    protected $paperorientation;
    protected $csvDelimiter;
    protected $csvEnclosure;

    public function __construct($response, $filename, $paperorientation, $csvDelimiter = ',', $csvEnclosure = '"')
    {
        $this->response = $response;
        $this->filename = $filename;
        $this->paperorientation = $paperorientation;
        $this->csvDelimiter = $csvDelimiter;
        $this->csvEnclosure = $csvEnclosure;
    }

    public function view(): View
    {
        return LaravelView::make('crudbooster::export', [
            'response' => $this->response,
        ]);
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->csvDelimiter,
            'enclosure' => $this->csvEnclosure,
        ];
    }
}
