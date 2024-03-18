<?php namespace App\Http\Controllers;

		use Session;
		use Request;
		use DB;
		use CRUDBooster;
        use crocodicstudio\crudbooster\controllers\AdminCmsUsersController;

		class ApiListUsersController extends \crocodicstudio\crudbooster\controllers\ApiController {
            public $controller = null;
		    function __construct() {
				$this->table       = "cms_users";
				$this->permalink   = "list_users";
				$this->method_type = "get";
                $this->controller = new AdminCmsUsersController();
		    }
		

		    public function hook_before(&$postdata) {
		        //This method will be execute before run the main process

		    }

		    public function hook_query(&$query) {
		        //This method is to customize the sql query

		    }

		    public function hook_after($postdata,&$result) {
		        //This method will be execute after run the main process

		    }

		}