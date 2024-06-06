@if($command=='layout')

<div id='{{$componentID}}' class='border-box'>

    <div class="small-box [color]">
  <div id="chart1"></div>
  <div id="chart2"></div>

  <script type="text/javascript" >
			var selState;
			var query;
			var filters;
			var config = {
				host: "data4primesaas.eu.qlikcloud.com", 
				prefix: "/", 
				port: 443, 
				isSecure: true,
				webIntegrationId: '9G9Lt4S--4o5Vj5BLq4HGEqVRpvP_Djj',
                paths: {
                    "qlik": "js/qlik"
                    }
			};
						const baseUrl = ( config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;
                console.log(baseUrl);
/*
				require.config({
						baseUrl: baseUrl + 'resources',
						webIntegrationId: config.webIntegrationId			
			});


			require( ["js/qlik"], function ( qlik ) {


				qlik.setLanguage("en");

				var app = qlik.openApp('5a174d39-0d26-4871-bbe9-583252deaeb2', config);
                console.log("DOPOO OPEN APP");
				


				
			});
*/	
  </script>

        <!--<div class='inner inner-box'>
            <h3>[sql]</h3>
            <p>[name]</p>
        </div>
        <div class="icon">
            <i class="ion [icon]"></i>
        </div>
        <a href="[link]" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>-->
    </div>

    <!--<div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Qlik Widget'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>-->
</div>
@elseif($command=='configuration')
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="form-group">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="form-group">
        <label>Icon By Ionicons</label>
        <input class="form-control" required name='config[icon]' type='text' value='{{@$config->icon}}' />
        E.g : ion-bag . You can find more icon, checkout at <a target='_blank'
            href='http://ionicons.com/'>ionicons.com</a>
    </div>

    <div class="form-group">
        <label>Color</label>
        <select class='form-control' required name='config[color]'>
            <option {{(@$config->color == 'bg-green')?"selected":""}} value='bg-green'>Green</option>
            <option {{(@$config->color == 'bg-red')?"selected":""}} value='bg-red'>Red</option>
            <option {{(@$config->color == 'bg-aqua')?"selected":""}} value='bg-aqua'>Aqua</option>
            <option {{(@$config->color == 'bg-yellow')?"selected":""}} value='bg-yellow'>Yellow</option>
        </select>
    </div>

    <div class="form-group">
        <label>Link</label>
        <input class="form-control" required name='config[link]' type='text' value='{{@$config->link}}' />
    </div>

    <div class="form-group">
        <label>Count (SQL QUERY)</label>
        <textarea name='config[sql]' rows="5" class='form-control'>{{@$config->sql}}</textarea>
        <div class="help-block">Make sure the sql query are correct unless the widget will be broken. Mak sure give the
            alias name each column. You may use
            alias [SESSION_NAME] to get the session
        </div>
    </div>

</form>
@elseif($command=='showFunction')
<?php
    if ($key == 'sql') {
        try {
            $sessions = Session::all();
            foreach ($sessions as $key => $val) {
                if (gettype($val) == gettype($value)) {
                    $value = str_replace("[".$key."]", $val, $value);
                }
                
            }
            echo reset(DB::select(DB::raw($value))[0]);
        } catch (\Exception $e) {
            echo 'ERROR';
        }
    } else {
        echo $value;
    }

    ?>
@endif