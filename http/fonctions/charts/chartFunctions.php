<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/template/header.php');

function addPie($idDiv, $nameData, $data){
	
	if(!empty($data)){

		echo "
			new Highcharts.Chart({
				chart: {
					renderTo : '".$idDiv."',
					margin : 5,
					spacing : 5,
					backgroundColor: 'transparent',
					borderWidth: 1,
					borderColor: '#ccc',
					plotShadow: false,
					animation: false
				},
				title: {
					text: null
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f} %)'
				},
				plotOptions: {
					pie: {
						allowPointSelect: false,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							distance: 65,
							format: '<b>{point.name}</b>: {point.y}',
							style: {
								color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black',
								fontFamily: 'Corbel, sans-serif',
								fontSize: '13px',
							},
							connectorColor: 'black'
						}
					}
				},
				series: [{
					type: 'pie',
					name: '".$nameData."',
					startAngle : 180,
					animation: false,
					data: [
						".$data."
					]
				}]
			});
		";
	
	}else{
		echo "document.getElementById('".$idDiv."').innerHTML = 'Pas de données.';";
		
	}
}


function addColumn($idDiv, $nameData, $data){
	
	if(!empty($data)){

		echo "
			new Highcharts.Chart({
				chart: {
					renderTo : '".$idDiv."',
					margin : [0, 10, 80,10],
					spacing : 0,
					backgroundColor: 'transparent',
					borderWidth: 1,
					borderColor: '#ccc',
					plotShadow: false,
					animation: false
				},
				title: {
					text: null
				},
				
				xAxis: {
					type: 'category',
					labels: {
						rotation: -65,
						style: {
							fontSize: '13px',
							fontFamily: 'Corbel, sans-serif'
						}
					}
				},
				yAxis: {

					title: {
						text: null
					}
				},
				legend: {
					enabled: false
				},
				
				tooltip: {
					enabled: false
				},
				
				plotOptions: {
					column: {
						allowPointSelect: false,
						dataLabels: {
							enabled: true,
							format: '<b>{point.y}</b>',
							style: {
								color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
							},
						}
					}
				},
				series: [{
					type: 'column',
					name: '".$nameData."',
					animation: false,
					data: [
						".$data."
					],
					
					dataLabels: {
						enabled: true,
						rotation: 0,
						color: 'black',
						align: 'center',
						y: 30,
						style: {
							fontSize: '16px',
							fontFamily: 'Corbel, sans-serif',
						}
					}
				}]
			});
		";
	
	}else{
		echo "document.getElementById('".$idDiv."').innerHTML = 'Pas de données.';";
		
	}
	
}


function addMap($idDiv, $nameData, $data){
	
	if(!empty($data)){

		echo "
			new Highcharts.Map({
					
					chart: {
							renderTo : 'mapMonde',
							margin : 5,
							spacing : 5,
							backgroundColor: 'transparent',
							borderWidth: 1,
							borderColor: '#ccc',
							plotShadow: false,
							animation: false
						},
						
						
					title : {
						text : null
					},

					subtitle : {
						text : null
					},

					mapNavigation: {
						enabled: true,
						enableMouseWheelZoom: false,
						enableTouchZoom: false,
						buttonOptions: {
							verticalAlign: 'bottom'
						}
					},

					colorAxis: {
					
							minColor: '#809ABF',
							maxColor: '#001832',
							min: 1,
							
						},

					 tooltip: {
							backgroundColor: 'none',
							borderWidth: 0,
							shadow: false,
							useHTML: true,
							padding: 0,
							pointFormat: '<span class=\"f32\"><span class=\"flag {point.code}\"></span></span>'
								+ ' {point.nom}: <b>{point.value}</b>',
							positioner: function () {
								return { x: 0, y: 350 };
							},
							style: {
								fontSize: '14px',
								fontFamily: 'Corbel, sans-serif',
								}
						},

						
						
					series : [{
						data: [
							".$data."
							],
						animation: false,
						mapData: Highcharts.maps['custom/world'],
						joinBy: ['iso-a2', 'code'],
						name: '".$nameData."',
						states: {
							hover: {
								color: '#F48420'
							}
						},
						dataLabels: {
							enabled: false
						}
					}]
				});";
				
	}else{
		echo "document.getElementById('".$idDiv."').innerHTML = 'Pas de données.';";
		
	}
}




//Conversion de données
function SQLtoChart($reponse, $x, $y){

	$dataChart = "";

	if($reponse !== false && count($reponse>0)){

		for($i=0; $i < count($reponse); $i++){
			
			if(isset($reponse[$i][$x]) && isset($reponse[$i][$y])){
				$dataChart .= '["'.$reponse[$i][$x].'",'.$reponse[$i][$y].'],';

			}
		
		}

		$dataChart = substr($dataChart,0,-1);
	}

	return $dataChart;
	
}


function ArraytoChart($tableau){

	$dataChart = "";

	if(count($tableau>0)){
		for($i=0; $i < count($tableau); $i++){
		
			if(isset($tableau[$i][0]) && isset($tableau[$i][1])){
				$dataChart .= '["'.$tableau[$i][0].'",'.$tableau[$i][1].'],';
			}
		
		}
		
		$dataChart = substr($dataChart,0,-1);
	}

	return $dataChart;
	
}


function paysSQLtoDataIso($reponse, $pays, $y){

	//Construction du tableu de données pays/iso

	$paysISO = array();

	$fileCodesPays = fopen($_SERVER['DOCUMENT_ROOT'].'/fonctions/charts/codes_pays.txt', 'r');

	while (!feof($fileCodesPays)){
		
		$lignePays = explode('//',trim(fgets($fileCodesPays)),2);	
		
		if(count($lignePays)==2){
		
			array_push($paysISO, array('pays' => $lignePays[0], 'code' => $lignePays[1]));
		
		}		
	}
	
	fclose($fileCodesPays);


	$dataChart="";
	
	//Association du code pays + creation Data to chart
	
	if($reponse !== false && count($reponse>0)){

		for($i=0; $i < count($reponse); $i++){
		
			$key = recursive_array_search($reponse[$i][$pays],$paysISO);
		
			if($key !== false){
			
				if(isset($reponse[$i][$pays]) && isset($reponse[$i][$y])){
			
					$dataChart .= '{"code":"'.$paysISO[$key]['code'].'","nom":"'.$reponse[$i][$pays].'","value":'.$reponse[$i][$y].'},';				

				}
			}
		}
		$dataChart = substr($dataChart,0,-1);
	}
	
	return $dataChart;

}

function recursive_array_search($needle,$haystack) {
    foreach($haystack as $key=>$value) {
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

?>
