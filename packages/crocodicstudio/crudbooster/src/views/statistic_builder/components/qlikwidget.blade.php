@if($command=='layout')

<div id='{{$componentID}}' class='border-box'>

    <div class="small-box [color]">
  <div id="chart1"></div>
  <div id="chart2"></div>
  
  <script type="text/javascript">
	var selState;
			var query;
			var filters;
			var config = {
				host: "sense.izsvenezie.it", //the address of your Qlik Engine Instance
				prefix: "/pub/", //or the virtual proxy to be used. for example "/anonymous/"
				port: 443, //or the port to be used if different from the default port  
				isSecure: true //should be true if connecting over HTTPS
				//webIntegrationId: 'web-integration-id-here' //only needed in SaaS editions
			};
			
			const baseUrl = ( config.isSecure ? 'https://' : 'http://' ) + config.host + (config.port ? ':' + config.port : '') + config.prefix;
				require.config({
						baseUrl: baseUrl + 'resources'
						// webIntegrationId: config.webIntegrationId// only needed in SaaS editions				
			});
			
			/* Require */
			require( ["js/qlik"], function ( qlik ) {
				qlik.setOnError( function ( error ) {
					$( '#popupText' ).append( error.message + "<br>" );
					$( '#popup' ).fadeIn( 1000 );
				} );
				$( "#closePopup" ).click( function () {
					$( '#popup' ).hide();
				} );
			
				/* language setup */
				qlik.setLanguage("en");
				
				
				/* header bar */
				function showHide(id) {
					if(id == 'details'){
							$('#details').show();
							$('#general').hide();
							qlik.resize();
					}

					if(id == 'general'){

							$('#general').show();
							$('#details').hide();
							qlik.resize();
					}
				}
				
				$( "#gene" ).click( function () {
					showHide('general');
					$( "#gene" ).addClass( "active" );
					$( "#epid" ).removeClass( "active" );
				});
				
				$( "#epid" ).click( function () {
					showHide('details');
					$( "#gene" ).removeClass( "active" );
					$( "#epid" ).addClass( "active" );
				});
				// ------------------------------------------
				

				/* To the top button */
				function goToEvent(id = 'html, body') {
					$(id).animate({scrollTop:0}, 300);
				};
				
				$( "#btnToTop" ).click( function () {
					goToEvent();
				});
				
				$(window).scroll(function() {
					if ($(window).scrollTop() > 300) {
						$( "#btnToTopContainer" ).fadeIn();
					} else {
						$( "#btnToTopContainer" ).fadeOut();
					}
				});
				//-------------------------------------------
				
				
				// app production = EURL_new
				var app = qlik.openApp('135b6165-a615-4713-a91f-915bcc8f15c5', config);
				
				/* activate bookmark/filter */
				app.bookmark.apply('cdedf34d-2029-42f8-a71b-2a68ff3c622b');

				/* active filters */
				app.getObject($('#currentselection'), 'CurrentSelections');

				/*app.visualization.get('JMuYXc').then(function(vis){
					vis.show("QVfilters");
				});*/

				// date picker
				app.visualization.get('kzBwZq').then(function(vis){
					vis.show("datepicker");
				});

				app.visualization.get('AKWPbq').then(function(vis){
					vis.show("epidemic");
				});	

				/* Curve epidemio  */
				// import qlik object from api
				app.visualization.get('697af384-3180-47d0-88ca-8eeea7fecc57').then(function(vis){
					vis.show("QVV01");
					qlik.resize();
				});
			
				app.visualization.get('934a8559-0b32-4743-ac2a-4df2b53b1403').then(function(vis){
					vis.show("QV03");
					qlik.resize();
				});

				app.visualization.get('47c13784-b94f-4660-aac9-9bfa805c1715').then(function(vis){
					vis.show("QV04");
					qlik.resize();
				});
			
				/* General  */
				//Creation example
				/*app.visualization.create('table',["gid", "=date(date_confirm,'YYYY-MM-DD')","subtype", "epi_unit", "country", "specie"], {title:"HPAI Outbreaks"}).then(function(bar){
					bar.show('QV05');
				});*/
				app.visualization.get('5978fbde-528e-4ec2-ba68-0c3b714155e0').then(function(vis){
					vis.show("QV05");
					qlik.resize();
				});
				
				app.visualization.get('wzmTWj').then(function(vis){
					vis.show("QV06");
					qlik.resize();
				});
				
				app.visualization.get('rmFnzs').then(function(vis){
					vis.show("KPI01");
					qlik.resize();
				});
				
				app.visualization.get('ErxRAu').then(function(vis){
					vis.show("KPI02");
					qlik.resize();
				});
				
				app.visualization.get('PKuFdkX').then(function(vis){
					vis.show("KPI03");
					qlik.resize();
				});
				
				app.visualization.get('aBHkxmp').then(function(vis){
					vis.show("KPI04");
					qlik.resize();
				});
				
				app.visualization.get('pNkqPzN').then(function(vis){
					vis.show("WILD01");
					qlik.resize();
				});
				
				app.visualization.get('GkyjPE').then(function(vis){
					vis.show("WILD02");
					qlik.resize();
				});
				
				app.visualization.get('ae041feb-cd7b-46e5-98de-58c03d05f9a1').then(function(vis){
					vis.show("FARM01");
					qlik.resize();
				});
				
				app.visualization.get('100e49a3-2f1b-4984-b820-e5c76b7b3c46').then(function(vis){
					vis.show("FARM02");
					qlik.resize();
				});
								
				/*Reset filter*/
				$( "#btn-reset" ).click( function () {
					app.clearAll();
				} );
				

				app.visualization.create(
						"listbox",
						[
							"epidemic"
						],
						{
						"showTitles": true,
						"title": "Epidemic"
						}
					).then(function(vis1){
						vis1.show("f_epidemic");
					});
									
					app.visualization.create(
						"listbox",
						[
							"subtype"
						],
						{
						"showTitles": true,
						"title": "Subtype"
						}
					).then(function(vis2){
						vis2.show("f_subtype");
					});
					
					app.visualization.create(
						"listbox",
						[
							"country"
						],
						{
						"showTitles": true,
						"title": "Country"
						}
					).then(function(vis3){
						vis3.show("f_country");
					});
					
					app.visualization.create(
						"listbox",
						[
							"epi_unit"
						],
						{
						"showTitles": true,
						"title": "Epidemilogical Unit"
						}
					).then(function(vis4){
						vis4.show("f_epiunit");
					});
					
					//da sostituiro con species
					//=if(epi_unit='Wild birds',specie,'-')
					app.visualization.create(
						"listbox",
						[
							"specie"
						],
						{
						"showTitles": true,
						"title": "Species"
						}
					).then(function(vis5){
						vis5.show("f_species");
					});
					
					app.visualization.create(
						"listbox",
						[
							"Year" //date_confirm.autoCalendar.Year
						],
						{
						"showTitles": true,
						"title": "Year"
						}
					).then(function(vis6){
						vis6.show("f_dateconfirm_year");
					});
					
					app.visualization.create(
						"listbox",
						[
							"Month" //date_confirm.autoCalendar.Month
						],
						{
						"showTitles": true,
						"title": "Month"
						}
					).then(function(vis7){
						vis7.show("f_dateconfirm_month");
					});
					
					app.visualization.create(
						"listbox",
						[
							"Date" //date_confirm.autoCalendar.Date
						],
						{
						"showTitles": true,
						"title": "Date"
						}
					).then(function(vis8){
						vis8.show("f_dateconfirm_date");
					});
					
					
				
				// epiunit, species
				// anno, mese, giorno
		
				
				//da predendere dall'oggetto dell'ipercubo reply.qListObject.qDataPages[0].qMatrix[0];
				$( "#btn-gen-map" ).click( function () {
					
					var newClick = 1;
					
					//console.log("Ciccio");
					//selState1 = app.selectionState(); // selection con l'oggetto classico
					
					app.createGenericObject( {
						selection: {
							qStringExpression: "=GetCurrentSelections (chr(35), '=', ',' ,600)"
						}
					}, function ( reply ) {
						selState = reply.selection;
						query = selState.split('#');
						filters = {};
						
						for(var i = 0; i < query.length; i++){
							var splitted = query[i].split('=');
							if(splitted.length == 3){ // caso particolare per le date specifiche --> ci sono 2 segni '='
								
								var values = splitted[2];
								filters['days'] = values;

							} else {

								var key = splitted[0];
								var values = splitted[1];

								if (key == "date_confirm") { // range di date --> prendo la prima e l'ultima

									values = values.split(',') // spezzetto i valori           
									filters[key] = values[0] + "," + values[values.length-1];

								} else if(key == "Date"){ // filtri autocalendar (giorni specifici, mese, anno) date_confirm.autoCalendar.Date

									filters['autocalendar_date'] = values;

								} else if(key == "Month") { //date_confirm.autoCalendar.Month

									filters['autocalendar_month'] = values;

								} else if(key == "Year") { //date_confirm.autoCalendar.Year

									filters['autocalendar_year'] = values;

								} else {
									filters[key] = values;
								}
							}    
						}
						//console.log(selState);
						//console.log(filters);
						
						if(newClick == 1){
						
							$.ajax({
								type: 'POST',
								url: './parseFilter.php',
								data: filters,		
								dataType: 'json',																										
								success: function(response){					
									//location.href = './map.php';
									//problema con window.open() per refresh pagina.
									
									window.open('./map.php','_blank');
									//console.log(response);
								},
								error: function(xhr, type, exception) {
									alert("error response type: " + type + "\nexception: " + exception);
								} 
							});
							newClick = 0;
						}
						
					});
				});

				/*
				selState = app.selectionState( );
  				var listener = function() {
    				alert('Back count:' + selState.backCount);
    				selState.OnData.unbind( listener );
  				};
  				selState.OnData.bind( listener );
				*/
				
				/*function toggleResize() {
					return this.toggle(400,function(){
						//console.log("resize");
						qlik.resize();
					})
				}*/
				
				$("#t_country").on("click", function () {
					$("#a_country").collapse("toggle");
					$("#a_country").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});
				
				$("#t_epiunit").on("click", function () {
					$("#a_epiunit").collapse("toggle");
					$("#a_epiunit").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});
				
				$("#t_species").on("click", function () {
					$("#a_species").collapse("toggle");
					$("#a_species").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});
				
				$("#t_epidemic").on("click", function () {
					$("#a_epidemic").collapse("toggle");
					$("#a_epidemic").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});
				
				$("#t_subtype").on("click", function () {
					$("#a_subtype").collapse("toggle");
					$("#a_subtype").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});
				
				$("#t_dateconfirm").on("click", function () {
					$("#a_dateconfirm").collapse("toggle");
					$("#a_dateconfirm").on('shown.bs.collapse', function() {
						qlik.resize();
					});
				});

				
				//document.getElementById("QV01").style.width = "300px";
				//document.getElementById("QV01").style.width = "100%";
				
			});
			
  </script>
        <div class='inner inner-box'>
            <h3>[sql]</h3>
            <p>[name]</p>
        </div>
        <div class="icon">
            <i class="ion [icon]"></i>
        </div>
        <a href="[link]" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
    </div>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Qlik Widget'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
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