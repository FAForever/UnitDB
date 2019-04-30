<?php
	header('Content-Type: application/json');
	
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
    // alias
    $commands["id"] = $commands["searchunit"];
    ///
    ///  
    //////////////////////////////////
    
    //////////////////////////////////
    ///
    ///  Get missile by id
    ///
    $commands["projectileid"] = function($data){
        
		$projs = $GLOBALS["dataMissiles"];
        foreach($projs as $proj){
            if ($proj->Id == $data){
                
                return json_encode($proj);
                
            }
            
        }
        
    };
    ///
    ///  
    //////////////////////////////////
    
    //////////////////////////////////
    ///
    ///  List units
    ///
    $commands["listunits"] = function($data){
        $list = array();
		$units = $GLOBALS["dataUnits"];
        foreach($units as $unit){
            $list []= $unit->Id;
        }
		return json_encode($list);
    };
    ///
    ///  
    //////////////////////////////////
    
    //////////////////////////////////
    ///
    ///  List data
    ///
    $commands["listdata"] = function($data){
        $list = array("units"=>[], "missiles"=>[]);
		$units = $GLOBALS["dataUnits"];
        foreach($units as $unit){
            $list ["units"] []= $unit->Id;
        }
        foreach($GLOBALS["dataMissiles"] as $miss){
            $list ["missiles"] []= $miss->Id;
        }
		return json_encode($list);
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