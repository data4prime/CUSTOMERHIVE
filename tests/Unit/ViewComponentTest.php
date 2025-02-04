<?php

/*
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
*/

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use crocodicstudio\crudbooster\controllers\QlikAppController;
use crocodicstudio\crudbooster\controllers\StatisticBuilderController;
use crocodicstudio\crudbooster\helpers\QlikHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use stdClass;
use crocodicstudio\crudbooster\helpers\CRUDBooster;


class ViewComponentTest extends TestCase
{
    use RefreshDatabase;
    protected $controller;
    protected $crudBoosterMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create instance of the controller
        $this->controller = new StatisticBuilderController();
        
        // Create a mock for CRUDBooster
        $this->crudBoosterMock = $this->createMock(CRUDBooster::class);
        $this->app->instance(CRUDBooster::class, $this->crudBoosterMock);
        
        // Set up the expectation for myId
        $this->crudBoosterMock->method('myId')->willReturn(1);
    }

    public function testGetViewComponentWithValidMashupAndConfig()
    {

        $componentMock = new stdClass();
        $componentMock->componentID = 1;
        $componentMock->component_name = 'test_component';
        $componentMock->area_name = 'test_area';
        $componentMock->config = json_encode([
            'mashups' => 1,
            'object' => 'test_object'
        ]);

        $mashupMock = new stdClass();
        $mashupMock->id = 1;
        $mashupMock->conf = 'test_conf';

        $confDbMock = new stdClass();
        $confDbMock->id = 'test_id';
        $confDbMock->type = 'SAAS';
        $confDbMock->config = json_encode(['key' => 'value']);


        DB::shouldReceive('table')
            ->with('cms_statistic_components')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('componentID', 1)
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn($componentMock);

        DB::shouldReceive('table')
            ->with('qlik_apps')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('id', 1)
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn($mashupMock);


        DB::shouldReceive('table')
            ->with('qlik_confs')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('id', 'test_conf')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn($confDbMock);

        $confMock = new stdClass();
        $confMock->id = 'test_id';
        $confMock->type = 'SAAS';

        $mashupConf = new stdClass();
        $mashupConf->id = 'test_id';
        $mashupConf->type = 'SAAS';


        $this->mock('alias:' . HelpersQlikHelper::class, function ($mock) {
            $mock->shouldReceive('getJWTToken')
                ->with(1, 'test_id')
                ->andReturn('test_token');
        });
        $this->mock('alias:' . QlikAppController::class, function ($mock) use ($mashupConf, $mashupMock, $confMock) {
            $mock->shouldReceive('getConf')
                ->with(1)
                ->andReturn($mashupConf);
            $mock->shouldReceive('getMashupFromCompID')
                ->with(1)
                ->andReturn($mashupMock);
        });

        View::shouldReceive('render')->andReturn('<div>Test Component</div>');
        $response = $this->controller->getViewComponent(1);
        $this->assertIsObject($response->original);
        $this->assertEquals(1, $response->original->componentID);
        $this->assertObjectHasAttribute('layout', $response->original);
        $this->assertObjectHasAttribute('config', $response->original);
        $this->assertObjectHasAttribute('conf', $response->original);

    }

/*
    public function testGetViewComponentWithInvalidConfig()
    {
        // Mock component with invalid config
        $componentMock = new stdClass();
        $componentMock->componentID = 1;
        $componentMock->component_name = 'test_component';
        $componentMock->area_name = 'test_area';
        $componentMock->config = 'invalid_json';

        DB::shouldReceive('table')
            ->with('cms_statistic_components')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('componentID', 1)
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn($componentMock);

        // Mock View facade
        View::shouldReceive('render')->andReturn('<div>Test Component</div>');

        // Call the method using the controller instance
        $response = $this->controller->getViewComponent(1);

        // Assert response
        $this->assertIsObject($response->original);
        $this->assertEquals(1, $response->original->componentID);
        $this->assertObjectHasAttribute('config', $response->original);
        $this->assertEquals(0, $response->original->config->mashups);
        $this->assertEquals(0, $response->original->config->object);
    }
*/
/*
    public function testGetViewComponentWithoutMashup()
    {
        // Mock component without mashup
        $componentMock = new stdClass();
        $componentMock->componentID = 1;
        $componentMock->component_name = 'test_component';
        $componentMock->area_name = 'test_area';
        $componentMock->config = json_encode([
            'object' => 'test_object'
        ]);

        DB::shouldReceive('table')
            ->with('cms_statistic_components')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('componentID', 1)
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn($componentMock);

        // Mock View facade
        View::shouldReceive('render')->andReturn('<div>Test Component</div>');

        // Call the method using the controller instance
        $response = $this->controller->getViewComponent(1);

        // Assert response
        $this->assertIsObject($response->original);
        $this->assertEquals(1, $response->original->componentID);
        $this->assertObjectHasAttribute('config', $response->original);
        $this->assertNull($response->original->conf);
    }
*/
}