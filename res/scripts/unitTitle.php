<?php
	include("functions.php");
	
	if (isset($_GET['id']) && 
		isset($_GET['localization'])
		){
		/// First, find unit
		
		$data = getGameData("../../");
		$thisUnit = getUnit($_GET['id'], $data['units']);
		
		if ($thisUnit === false){
			return;
		}
		
		
		echo '
			<div class="tooltipText" style="background-color:'.getFactionColor($thisUnit->General->FactionName, "dark").';">
				'.getUnitTitle($thisUnit, $data['localization'], $_GET['localization']).'
			</div>';	
	}
?>