<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Log;

use crocodicstudio\crudbooster\helpers\LicenseHelper;
use App\Services\ConnectorService;

class GetLicense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:GetLicense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get license every hour';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
            //$url = Config::get('license-connector.license_server_url') . '/api/api-license/license-server/license';

        LicenseHelper::writeLicense();


        return Command::SUCCESS;
    }
}
