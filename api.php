<?php
	header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
	
    ///////
    // Loading data
    //
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
    
    //
    //////
    
    $commands = array();
    
    //////////////////////////////////
    ///
    ///  Search for a specific unit
    ///
    $commands["searchunit"] = function($data){
		$uOI = null;
		$units = $GLOBALS["dataUnits"];
		for ($i = 0; $i < sizeOf($units); $i++){
			$element = $units[$i];
			$id = $element->Id;
			$name = $id .=  " " .($element->General->FactionName).' '.getTech($element);
			
			if (property_exists($element->General, 'UnitName')){
				$name .= ' "'.($element->General->UnitName).'" ';
			}
			if (property_exists($element, 'Description')){
				$name .= ($element->Description);
			}
            
			if ($id == $data || strpos(strtolower($name), strtolower($data)) !== false){
				$uOI = $element;
				$uOI = (array)$uOI;
				$uOI['ApiName'] = $name;
				$uOI = (object)$uOI;
				break;
			}
		}
		
		return json_encode($uOI);
    };
    ///
    ///  
    //////////////////////////////////
	
    
    
    
    //////
    // Main execution of commands
    //
    
    foreach($commands as $name=>$function){
        if (isset($_GET[$name])){
            $data = $function($_GET[$name]);
            echo $data;
            exit;
        }
    }	
    //
    //////    
    
    
    //////////////////////////////////
    ///
    ///  Utilitary functions
    ///
	
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