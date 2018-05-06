<?php
	header('Content-Type: application/json');
	
	$dataString = file_get_contents("data/blueprints.json");
	$dataFull = json_decode($dataString);
	$dataUnits = [];
	$dataMissiles = [];
	
	$locData = json_decode(file_get_contents("data/localization.json"), true);
	
	foreach($dataFull as $thisUnit){
		if ($thisUnit->BlueprintType == "UnitBlueprint"){
			$dataUnits[]=$thisUnit;
		}
		else if ($thisUnit->BlueprintType == "ProjectileBlueprint"){
			$dataMissiles[]=$thisUnit;
		}
	}
	
	
	if (isset($_GET['id']) || isset($_GET['searchunit'])){
		
		$requested = $_GET["id"];
		$searchName = $_GET["searchunit"];
		$uOI = null;
		
		for ($i = 0; $i < sizeOf($dataUnits); $i++){
			$element = $dataUnits[$i];
			$id = $element->Id;
			$name = $id .=  " ";
			
			/*
			
			//Nicknames
			switch ($id){
				case "XSL0401 ":
					$name .=  "chicken ";
					break;
					
				case "UAL0401 ":
					$name .= "gc ";
					break;
					
				case "UAA0310 ":
					$name .= "donut ";
			}
			//Endof
			*/
			$name .= ($element->General->FactionName).' '.getTech($element);
			
			if (property_exists($element->General, 'UnitName')){
				$name .= ' "'.($element->General->UnitName).'" ';
			}
			if (property_exists($element, 'Description')){
				$name .= ($element->Description);
			}
			/*
			//All the names
			if (strpos(strtolower($name), "extractor") !== false){
				$name .=  " mex mexes ";
			}
			if (strpos(strtolower($name), "strategic missile launcher") !== false){
				$name .= " nuke SML ";
			}
			if (strpos(strtolower($name), "strategic missile defense") !== false){
				$name .= " SMD ";
			}
			if (strpos(strtolower($name), "tactical missile launcher") !== false){
				$name .= " TML ";
			}
			if (strpos(strtolower($name), "tactical missile defense") !== false){
				$name .= " TMD ";
			}
			if (strpos(strtolower($name), "power generator") !== false && getTech($element) == "T1 "){
				$name .= " pgen ";
			}
			//endof
			*/
			if ($id == $requested || strpos(strtolower($name), strtolower($searchName)) !== false){
				$uOI = $element;
				$uOI = (array)$uOI;
				$uOI['ApiName'] = $name;
				$uOI = (object)$uOI;
				break;
			}
		}
		
		echo json_encode($uOI);
		exit;
		
	}
	
	
	function getTech($unit){
			
		$unitTech = "";
		$unitCat = $unit->Categories;
			
		if (in_array ('TECH1', $unitCat)){
			$unitTech = "T1 ";
		}
		else if (in_array ('TECH2', $unitCat)){
			$unitTech = "T2 ";
		}
		else if (in_array ('TECH3', $unitCat)){
			$unitTech = "T3 ";
		}
		else if (in_array ('EXPERIMENTAL', $unitCat)){
			$unitTech = "Experimental ";
		}
		return $unitTech;
	}
	
	
?>