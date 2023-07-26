<?php
	
	//	When debugging, uncomment the following to display errors.
	/*
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	*/
    
    include("calculations.php");
		
	///////////////////////////////////////
	///									///
	///	Functions follow.      			///
	///									///
	///////////////////////////////////////
	
	function categorizeUnitData($categoriesOrder, $userSettings, $dataUnits){
		
		$finalData = array();
		$armies = [];
		
		/// For each unit...
		for ($i = 0; $i < sizeOf($dataUnits); $i++){
			
			$item = $dataUnits[$i];
			
			/// This chunk of code can be used to skip units lacking an icon, a preview, or basic general information.
			/// Useful to avoid displaying debug units.
			/*
			if (!property_exists($item, 'StrategicIconName') ||
				!property_exists($item, 'General') ||
				!property_exists($item->General, 'Icon')){
				continue;
			}
			*/
			
			/// Formatting the Faction name to ensure it has good casing : Aeon, Cybran, Seraphim, UEF, Nomads
			$faction = formatFaction($item->General->FactionName);						
			
			/// Adding the army to the list, given the user said he wanted to see it.
			if (!in_array($faction, $armies) && in_array($faction, $userSettings['showArmies'])){
				$armies[] = ($faction);				
			}
			$itemCat = $item->Categories;
			$tech = getTech($item);
			
			/// Now for the unit categorization...
			/// The order in which these Else IF are structured is important. Changing them might allow categorizing Fatboy as "Factory" instead of "Land unit", for example. The unit will be categorized in the first matching category.			
			
			//////////////
			//	CIVILIAN & DEBUG
			// Every non-multiplayer-relevant unit should go there
			/////////////
			if (in_array('OPERATION', $item->Categories) || 
				in_array('CIVILIAN', $item->Categories) ||
				in_array('INSIGNIFICANTUNIT', $item->Categories) ||
				in_array('DEBUG', $item->Categories)){
					
				$finalData['Civilian & Miscellanous'][""][$faction][$item->Id] = $item;
			}
			
			///////////////
			//	COMMANDER & SACU
			// SACUs and ACUs go there.
			//////////////
			else if (in_array ('COMMAND', $itemCat)
				|| in_array ('SUBCOMMANDER', $itemCat)){
				$finalData['Command'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	ENGINEER
			// Everything that has the flag Engineer, along with hives and engineering platforms.
			//////////////
			else if (in_array ('ENGINEER', $itemCat) ||
						in_array('PODSTAGINGPLATFORM', $itemCat)){
				$finalData['Engineer'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	AIRCRAFT
			// Everything that flies, including Novax
			//////////////
			else if (in_array('MOBILE', $itemCat) && 
						(in_array ('AIR', $itemCat))){
				$finalData['Aircraft'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	VEHICLE
			// Land vehicles and bots
			//////////////
			else if (in_array('MOBILE', $itemCat) && 
						(in_array ('LAND', $itemCat))){
				$finalData['Vehicle'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	SHIP
			// Stuff that goes under and over water, including Salem
			//////////////
			else if (in_array('MOBILE', $itemCat) && 
						(in_array ('NAVAL', $itemCat))
						&& !in_array('MOBILESONAR', $itemCat)){
				$finalData['Naval'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	SUPPORT
			// Wall sections, air staging facilities, everything that does not fit elsewhere
			//////////////
			else if (in_array ('WALL', $itemCat) ||
					 in_array ('AIRSTAGINGPLATFORM', $itemCat) ||
					 in_array ('ORBITALSYSTEM', $itemCat) 
					 ){
				$finalData['Support'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	BUILDING - FACTORY
			// Every building that produces units, including Gateways
			//////////////
			else if (in_array('STRUCTURE', $itemCat) && 
					in_array ('FACTORY', $itemCat)){
				$finalData['Building - Factory'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	BUILDING - ECONOMY
			// Mexes, massfabs, pgens, paragons.
			//////////////
			else if (in_array('STRUCTURE', $itemCat) && 
					in_array ('ECONOMIC', $itemCat)){
				$finalData['Building - Economy'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	BUILDING - WEAPON
			// Artillery sites, turrets, ...
			//////////////
			else if (in_array('STRUCTURE', $itemCat) && 
						(in_array('NUKE', $itemCat) ||
						in_array ('INDIRECTFIRE', $itemCat) ||
						in_array ('DIRECTFIRE', $itemCat))){
				$finalData['Building - Weapon'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	BUILDING - DEFENSE
			// Antimissiles, AA turrets, stealth and shields, ...
			//////////////
			else if (in_array('STRUCTURE', $itemCat) && 
						(in_array ('DEFENSE', $itemCat) ||
						in_array('COUNTERINTELLIGENCE', $itemCat) ||
						in_array('ANTINAVY', $itemCat) ||
						in_array('ANTIAIR', $itemCat))
						){
				$finalData['Building - Defense'][$tech][$faction][$item->Id] = $item;
			}
			///////////////
			//	BUILDING - SENSOR
			// Radar and sonar
			//////////////
			else if ((in_array('STRUCTURE', $itemCat) || in_array('MOBILESONAR', $itemCat)) && 
						(in_array ('SORTINTEL', $itemCat) ||
						in_array('SONAR', $itemCat))){
				$finalData['Building - Sensor'][$tech][$faction][$item->Id] = $item;
			}
			
			// Dumping every other unit in the Misc category. Probably debug units or civilian/campaign-related stuff.
			else{
				$finalData['Civilian & Miscellanous'][""][$faction][$item->Id] = $item;
			}
			
		}
		
		
		$finalData = array_replace($categoriesOrder, $finalData);
		/// End of
		
		return array(
			"listData"=>$finalData,
			"armyList"=>$armies
			);
	}
	
	function formatFaction($faction){
		$faction = ucfirst(strtolower($faction));
		if (strtolower($faction) == "uef"){	
			$faction = strtoupper($faction);	// UEF is written all uppercase, unlike the other factions
		}		
		return $faction;
	}
	
	/// DISPLAY FUNCTIONS
	
	function displaySettingsMenu($defaultSettings, $userSettings, $dataLoc){
		// For each setting
		foreach($defaultSettings as $settingName=>$settingValue){
			$settingValue = null;
			if (array_key_exists($settingName, $userSettings)){
				$settingValue = $userSettings[$settingName];
			}
			
			// Preview corner has a specific dropdown menu
			if ($settingName == "previewCorner"){
				$options = ["Top left", "Top right", "Bottom right", "Bottom left", "None"];
				$settingValue = '
					<select style="width:100%;text-align:center;" name="'.$settingName.'">';
				foreach($options as $thisOpt){
					$select = '';
					if ($thisOpt == $userSettings[$settingName]){
						$select = "selected";
					}
					$settingValue.= '
						<option '.$select.' value="'.$thisOpt.'">'.$thisOpt.'</option>';
					
				}
						
				$settingValue .='	
					</select>';
			}
			// Lang depends on localization to populate its dropdown menu
			else if ($settingName == "lang"){
				$options = $dataLoc;
				$settingValue = '
					<select style="width:100%;text-align:center;" name="'.$settingName.'">';
				foreach($options as $locName=>$x){
					$select = '';
					if (array_key_exists($settingName, $userSettings) && $locName == $userSettings[$settingName]){
						$select = "selected";
					}
					$settingValue.= '
						<option '.$select.' value="'.$locName.'">'.$locName.'</option>';
					
				}
						
				$settingValue .='	
					</select>';
			}
			// Both checkboxes, displayed the same way
			else if ($settingName == "autoExpand" || $settingName == "spookyMode" || $settingName == "experimentalPreview"){
				$checked = "";

				if ($settingValue){
					$checked = "checked";
				}
				$settingValue = '<input style="width:100%;text-align:center;" name="'.$settingName.'" type="checkbox" '.$checked.' />'; 
			}	
			
			// Every other array setting is simply exploded
			else if(is_array($settingValue)){
				$settingValue = '<input style="width:100%;" type="text" name="'.$settingName.'" value="'.implode(",",$settingValue).'"/>';
			}	
			else{
				$settingValue = '<input style="width:100%;text-align:center;" name="'.$settingName.'" type="text" value=\''.($settingValue).'\'/>';
			}
			echo '
		<div class="flexColumns" style="margin:32px;margin-top:8px;margin-bottom:8px;">
			<div style="text-align:left;width:50%;margin:8px;">
				'.caseFormat($settingName).'
			</div>
			<div style="text-align:right;width:50%;margin:8px;">
				'.$settingValue.'
			</div>
		</div>';
		}
	}
	
	function displayUnitlistUnit($thisUnit, $userSettings, $techName, $armyName, $dataLoc, $onCard=false){
		/// We will only be displaying this unit if it has an ID
		if (property_exists($thisUnit, 'Id')){
			$id = $thisUnit->Id;
			
			
			/// Hover preview displaying
			$previewTrigger = "";
			
			if ($userSettings['previewCorner'] != "None"){
				$previewTrigger = 'onMouseover="loadHoverInfo(\''.($thisUnit->Id).'\', \''.($userSettings['lang']).'\')"';
			}
			echo '<div class="unitMainDiv tooltip"
							id="'.($thisUnit->Id).'" 
							onClick="toggleSelect(\''.($thisUnit->Id).'\')"
							'.$previewTrigger.'>';
				
			/// Hover preview, "Heavy mode"
			if (!$userSettings['experimentalPreview'] && $userSettings['previewCorner'] != "None"){
				/// Position of the hover-preview
				$position = 'left:0px;bottom:0px;';
				switch ($userSettings['previewCorner']){
					case "Top left":
						$position = 'left:0px;top:0px;';
						break;
						
					case "Top right":
						$position = 'right:0px;top:0px;';
						break;
						
					case "Bottom right":
						$position = 'right:0px;bottom:0px;';
						break;
				}
				echo '
					<div class="legacyTooltiptext" style="'.$position.'background-color:'.getFactionColor($thisUnit->General->FactionName, "dark").';">
						'.getUnitTitle($thisUnit, $dataLoc, $userSettings['lang']).'
					</div>';	
			}
			/// "Spooky mode" displaying preview instead of unit names
			if ($userSettings['spookyMode']){
				$terrain = 'none';
				$strategic = 'none';
				$description = 'unit';
				if (property_exists($thisUnit, 'StrategicIconName')){
					$strategic = $thisUnit->StrategicIconName;
				}
				if (property_exists($thisUnit->General, 'Icon')){
					$terrain = $thisUnit->General->Icon;
				}
				if (property_exists($thisUnit, 'Description')){
					$description = ($thisUnit->Description);
					$matches = [];
					if (preg_match ('/(<loc.*>+)/', $description, $matches)){
						$line = $matches[0];
						$line = str_replace('<loc ', '', $line);
						$line = str_replace('>', '', $line);
						$thisLang = $dataLoc[$userSettings['lang']];
						if (array_key_exists($line, $thisLang)){
							$description = str_replace('"', '', $thisLang[$line]);
						}
					}
				}
				
				/// Custom style for icon hue because of PHP-dynamic faction hue
				echo '
					<div>
						<div class="previewImg">
							<img alt="?" class="backgroundIconOverlap" src="res/img/preview_background/'.($terrain).'_up.png">
							<img alt="?" class="strategicIcon" style= "width:64px;height:64px;" src="res/img/preview/'.strtoupper($thisUnit->Id).'.png">
							<img alt="[x]" class="strategicIconOverlap" src="res/img/strategic/'.($strategic).'_rest.png" style="filter: hue-rotate('.(getFactionColor($armyName, 'hue')).'deg)">
						</div>
					</div>';
			}
			
			/// "Default mode" displaying each unit as a row, with name and strategic icon only
			else {
				echo '
						<div class="unit">';
				
				/// Icon path
				$icon = '';
				if (property_exists($thisUnit, 'StrategicIconName')){
					$icon = ($thisUnit->StrategicIconName).'_rest';
				}
				
				/// Custom style for icon hue because of PHP-dynamic faction hue
                echo '
						<div class="strategicIconDiv">'.                        
						'<img style="filter: hue-rotate('.(getFactionColor($armyName, 'hue')).'deg)" src="res/img/strategic/'.$icon.'.png" alt="[x]">
						</div>';
				
				/// Translates name of both unit name an description if possible
				$description = "unit";
				if (property_exists($thisUnit, 'Description')){
					$description = attemptTranslation($thisUnit->Description, $dataLoc, $userSettings['lang']);
				}
				$description = $techName.($description);
				$name = '';
				if (property_exists($thisUnit->General, 'UnitName')){
					$name = attemptTranslation($thisUnit->General->UnitName, $dataLoc, $userSettings['lang']);
				}
				
				/// Slightly different style if we're displayed on a unit comparator card, because the background is darker
				$moreStyle = "";
				if ($onCard){
					$moreStyle = 'style="color:'.getFactionColor($armyName, "bright").';"';
				}
				
				/// Displays it all
				echo '
						<div class="unitName" style="width:100%;">
							<span '.$moreStyle.' class="unitHotLink" onclick="seeUnit(\''.$id.'\')">
							'.($description).'
							</span>
							<span class="unitCustomName"
									style="color:'.getFactionColor($armyName, ($onCard ? "bright" : "dark") ).'">'.
								($name).'
							</span>';
							
				if (!$onCard){
					/// Uncomment this to add a little "Compare..." text on hover
					/*
					echo '
							<span style="
								color:'.getFactionColor($armyName, "bright").';">
								Compare...
							</span>';
					*/
				};
				echo '
						</div>
					</div>';
			}
			echo '
			</div>';
		}
	}
	
	function displayUnitlistArmies($techName, $techLevel, $userSettings, $armies, $dataLoc){
		echo '<div class="unitlistArmyRow">';
		
		/// For each army as a column, display one unit per row
		foreach($armies as $armyName){
			
			$border = '';
			if ($techName != "T1 " && $techName != ""){
				$border = 'border-top: 1px dotted grey;';
			}				
			
			/// Will behave differently if asked to display "Spooky mode", which means preview instead of names for units.
			$style = 'classicStyle';
			if ($userSettings['spookyMode']){
				$style = 'spookyStyle';
			}
			
			echo '<div class="'.$style.'"
				style="
				'.$border.'
				background-color:'.getFactionColor($armyName, "bright").'" >';
			
			if (array_key_exists($armyName, $techLevel)){
				$thisArmy = $techLevel[$armyName];			
			}
			else{
				echo '</div>';
				continue;
			}
			foreach($thisArmy as $thisUnit){
				displayUnitlistUnit($thisUnit, $userSettings, $techName, $armyName, $dataLoc);
			}
			echo '</div>';
		}
		echo '</div>';	
	}
	
	function displayUnitlistTechs($catName, $categories, $userSettings, $armies, $dataLoc){
		/// The order in which techs will be displayed
		$techOrder = ['', 'T1 ', 'T2 ', 'T3 ', 'T4 '];
		
		/// By default, the category will be opened unless it is "Civilian & Miscellanous".
		$open = "open";
		if ($catName == "Civilian & Miscellanous"){
			$open = "";
		}
		echo '
		<details '.$open.'>
			<summary class="categoryName">
				'.$catName.'
			</summary>';
		
		/// Custom flex column - no need for a class
		echo '<div style="
			display: flex;
			justify-content:space-between;
			flex-direction: column;" >';
			
		
		$categories = array_merge(array_flip($techOrder), $categories);
			
		/// For each tech as a row, display one column per army
		foreach($categories as $techName => $techLevel){
			if (!is_array($techLevel)){
				continue;
			}
			displayUnitlistArmies($techName, $techLevel, $userSettings, $armies, $dataLoc);
		}
		echo '</div>
			</details>';
	}	
	
	function displayFactionsHeader($armies){
		echo '<div class="factionsHeader" >';
			
		foreach($armies as $armyName){
			// #
			$loadFile = 'res/img/factional/'.strtolower($armyName).'_load.jpg';
			if (!file_exists($loadFile)){
				$loadFile = 'res/img/factional/default_load.jpg';
			}
			echo '
				<div class="flexColumns title factionSplash" style="
					background-image:url(\''.$loadFile.'\');
					color:'.getFactionColor($armyName, "bright").';">
					
					<div style="width:40%;">
					'.$armyName.'
					</div>
					<div style="text-align:left;width:60%;">
					</div>
				</div>';
			
		}
		echo '</div>';
	}
	
	function displayBuildlist($info, $thisUnit, $userSettings, $dataUnits, $dataLoc){
		if (property_exists($thisUnit->Economy, 'BuildableCategory')){
			$buildable = [];
			
			/// Checks every unit in the $data to see which ones correspond to the right buildable category
			/// and populates the $buildable with them
			foreach($dataUnits as $unit){
				foreach($thisUnit->Economy->BuildableCategory as $buildableCategory){
					$buildableRequirements = explode(' ', $buildableCategory);
					$canBuild = true;
					foreach($buildableRequirements as $requirement){
						if (!in_array($requirement, $unit->Categories) && strtoupper($requirement) != strtoupper($unit->Id)){
							$canBuild = false;
						}
					}
					if ($canBuild){
						$buildable[] = $unit;
					}
				}
			}
			
			/// Making sure there are no doubles
			$buildable = array_unique ($buildable, SORT_REGULAR);
			
			if (sizeOf($buildable) > 0){
				$autoOpen = '';
				if ($userSettings['autoExpand']){
					$autoOpen = 'open';
				}
				echo '
				<details class="sheetSection unitBlueprints" id="blueprintsSection'.($thisUnit->Id).'"  '.$autoOpen.'>
					<summary onClick="openAllBlueprintsSections(\'blueprintsSection'.($thisUnit->Id).'\')" class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
						Blueprints
					</summary>';
					
				if ($userSettings['spookyMode']){
					echo '<div class="flexWrap">';
				}
				foreach($buildable as $buildableUnit){
					
					displayUnitlistUnit($buildableUnit, $userSettings, getTech($buildableUnit), formatFaction($buildableUnit->General->FactionName), $dataLoc, true);
				}
				
				if ($userSettings['spookyMode']){
					echo '</div>';
				}
				echo '
				</details>';
			}
		}
	}
	
	function displayEnhancements($info, $thisUnit, $userSettings, $dataLoc){
		if (property_exists($thisUnit, "Enhancements")){
			
			$enhancements = $thisUnit->Enhancements;
			$blacklist = ["Slots"];	/// We don't want to get the "Remove " enhancements to display
			
			echo '<div class="sheetSection">
					<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Enhancements
					</div>
					
					<div class="flexColumn" style="width:100%;color:'.getFactionColor($info['Faction'], 'bright').';">
						
						';
			
			/// For each enhancement...
			foreach($enhancements as $thisEnhancement){
				if (!in_array($thisEnhancement, $blacklist)
					&& property_exists($thisEnhancement, "Slot")
					&&!property_exists($thisEnhancement, 'RemoveEnhancements')){
						
					$autoOpen = '';
					if ($userSettings['autoExpand']){
						$autoOpen = 'open';
					}
					
					echo '<details class="weaponList" '.$autoOpen.'>
								
								<summary  class="weaponCategory">
									<img 
									alt="X" 
									src="res/img/enhancements/'.$info['Faction'].'/'.($thisEnhancement->Icon).'_btn_up.png"
									class="enhancementImg">
											
									['.($thisEnhancement->Slot).'] '.attemptTranslation($thisEnhancement->Name, $dataLoc, $userSettings['lang']).'
								</summary>
							<div class="flexRows">
								<div class="info"  style="font-weight:normal;color:'.getFactionColor($info['Faction'], 'bright').';">
									Build costs
								</div>
								
								<div class="flexColumns" style="padding-bottom:4px;color:black;">
									<div class="energyCost">
										<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.format($thisEnhancement->BuildCostEnergy).'
									</div>
									<div class="massCost">
										<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.format($thisEnhancement->BuildCostMass).'
									</div>
									<div class="buildTimeCost">
										<img alt="tim" style="vertical-align:top;" src="res/img/icons/time.png"> '.format($thisEnhancement->BuildTime).'
									</div>
								</div>';
								
					/// If the enhancement offers any yield, or has any drain, lets display that.	
					if (property_exists($thisEnhancement, 'ProductionPerSecondMass') ||
						property_exists($thisEnhancement, 'ProductionPerSecondEnergy') ||
						property_exists($thisEnhancement, 'MaintenanceConsumptionPerSecondEnergy') ||
						property_exists($thisEnhancement, 'MaintenanceConsumptionPerSecondMass') ||
						property_exists($thisEnhancement, 'BuildRate')){
							
							$build = 0;
							$nrg = 0;
							$mass = 0;
							
							if (property_exists($thisEnhancement, 'MaintenanceConsumptionPerSecondMass')) $mass -= $thisEnhancement->MaintenanceConsumptionPerSecondMass;
							if (property_exists($thisEnhancement, 'MaintenanceConsumptionPerSecondEnergy')) $nrg -= $thisEnhancement->MaintenanceConsumptionPerSecondEnergy;
							if (property_exists($thisEnhancement, 'BuildRate')) $build += $thisEnhancement->BuildRate;
							if (property_exists($thisEnhancement, 'ProductionPerSecondEnergy')) $nrg += $thisEnhancement->ProductionPerSecondEnergy;
							if (property_exists($thisEnhancement, 'ProductionPerSecondMass')) $mass += $thisEnhancement->ProductionPerSecondMass;
							if (property_exists($thisEnhancement, 'BuildRate')) $nrg += $thisEnhancement->BuildRate;
							
							echo '<div class="sheetSection">
								<div class="info"  style="font-weight:normal;color:'.getFactionColor($info['Faction'], 'bright').';">
									Production / Consumption
								</div>
								
								<div class="flexColumns" style="padding-bottom:4px;color:black;">
									<div class="energyCost">
										<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.$nrg.'
									</div>
									<div class="massCost">
										<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.$mass.'
									</div>
									<div class="buildTimeCost">
										<img alt="tim" style="vertical-align:top;" src="res/img/icons/time.png"> '.$build.'
									</div>
								</div>
							</div>';
							
					}
					
					/// Now let's dump every enhancement info with its rough name and value
					/// This is a list of values we don't want to display. They are useless to us, or already displayed elsewhere.
					/// Should one of those values be encountered in the enhancement, it will be skipped
					$blacklist = ['Slot', 
									'BuildCostEnergy', 
									'BuildCostMass', 
									'MaintenanceConsumptionPerSecondEnergy', 
									'MaintenanceConsumptionPerSecondMass', 
									'ProductionPerSecondEnergy',
									'ProductionPerSecondMass',
									'BuildTime', 
									'Name', 
									'Icon',
									'BuildableCategoryAdds',
									'Prerequisite',
									'ShieldRegenStartTime',
									'ShowBones',
									'UpgradeEffectBones',
									'UpgradeUnitAmbientBones',
									'HideBones',
									'OwnerShieldMesh',
									'ImpactEffects',
									'ShieldEnergyDrainRechargeTime',
									'ImpactMesh',
									'Mesh',
									'MeshZ',
									'ShieldEnhancementNumber',
									'ShieldVerticalOffset',
									'ShieldSpillOverDamageMod'];
									
					foreach($thisEnhancement as $propName=>$property){
						if (!in_array($propName, $blacklist)){
							echo '
						<div class="flexColumns" style="padding-bottom:6px;">';
						
							// NEW HEALTH has the same formating has an unit healthbar
							if ($propName == "NewHealth"){
								echo '
							<div class="littleInfo">
								'.$propName.'
							</div>
							<div class="healthBar" style="height:auto;vertical-align:middle;margin:0px;">
								'.($info['Health']+$property).'
							</div>';
							}
							// SHIELD Health also has a custom formatting with pretty colours
							else if ($propName == "ShieldMaxHealth"){
									echo '
							<div class="littleInfo">
								'.$propName.'
							</div>
							<div class="shieldBar">
								'.($property).'
							</div>';
							}
							/// "Rates" have a value /per/second displayed
							else if ($propName == "ShieldRegenRate" ||
									$propName == "NewRateOfFire"){
									echo '
								<div class="littleInfo">
									'.$propName.'
								</div>
								<div class="littleInfoVar">
									'.$property.'/s
								</div>';
							}
							/// Health regen rate has the same formatting as healthbar regen rate (+/s)
							else if ($propName == "NewRegenRate"){
									echo '
								<div class="littleInfo">
									'.$propName.'
								</div>
								<div class="littleInfoVar">
									+'.($info['Regen']+$property).'/s
								</div>';
							}
							
							/// Everything else is just displayed as-is
							else{
								echo '
							<div class="littleInfo">
								'.$propName.'
							</div>
							<div class="littleInfoVar">
								'.$property.'
							</div>';
							}
							echo '
						</div>';
						}
					}
					
					echo '
								
						</div>
					</details>';
				}
			}
				
			echo '
						
					</div>
				</div>';
					
		}
	}
	
	function displayWeaponList($info, $thisUnit, $dataLoc, $userSettings, $dataMissiles){
		
		if (property_exists($thisUnit, "Weapon")){
			echo '<div class="sheetSection">
					<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Weapons
					</div>
					
					<div class="flexColumn" style="width:100%;color:'.getFactionColor($info['Faction'], 'bright').';">';
			$weapons = $thisUnit->Weapon;
			
			/// Count duplicate weapons so we can display them as "x2"  "x3"  etc
			$alreadyDisplayed = array();	// Used to not display dupes twice
			$registeredWeapons = array();	// Used to count amount of same weapons
            
			foreach($weapons as $property=>$thisWeapon){
				$found = false;
				foreach($registeredWeapons as $key=>$thisRegisteredWeapon){
                
					if (sameWeapon($thisWeapon, $thisRegisteredWeapon)){
						$thisRegisteredWeapon = (array)$thisRegisteredWeapon;
                        
						if (!array_key_exists("Occurences", $thisRegisteredWeapon)){
							$thisRegisteredWeapon ["Occurences"] = 1;
						}
                        $thisRegisteredWeapon ["Occurences"]++;
                        
						$thisRegisteredWeapon = (object)$thisRegisteredWeapon;
						$registeredWeapons[$key] = $thisRegisteredWeapon;
						$found = true;
					}
				}
				if (!$found){
					$thisWeapon = (array)$thisWeapon;
					$thisWeapon ["Occurences"] = 1;
					$thisWeapon = (object)$thisWeapon;
					$registeredWeapons[]=	$thisWeapon;
				}
			}
			$weapons = $registeredWeapons;
            
			/// For each weapon...
			foreach($weapons as $thisWeapon){
				$skip = false;
				foreach($alreadyDisplayed as $alreadyDisplayedWeapon){
					if (sameWeapon($alreadyDisplayedWeapon, $thisWeapon)){
						$skip = true;
						break;
					}
				}
				if (!$skip){
					displayWeapon($thisWeapon, $info, $thisUnit, $dataLoc, $userSettings, $dataMissiles, $thisWeapon->Occurences);
					$alreadyDisplayed [] = $thisWeapon;
				}
			}
			echo '	</div>';
		echo '	</div>';
		}
	}
	
	function sameWeapon($wep1, $wep2){
		// Checks if two weapons are the same, omitting Blueprint->Json artifacts (PropertyXXX, ...) by checking only some important values
		$valuesToCheck = array('DisplayName', 'Damage', 'RateOfFire', 'DamageType');
		$same = true;
		foreach($valuesToCheck as $thisProperty){
			if (
				!property_exists($wep1, $thisProperty) ||
				!property_exists($wep2, $thisProperty) ||
				$wep1->$thisProperty != $wep2->$thisProperty
				){
				$same = false;
			}
		}
		return $same;		
	}
	
	function displayWeapon($thisWeapon, $info, $thisUnit, $dataLoc, $userSettings, $dataMissiles, $occurences=1){
		if (property_exists($thisWeapon, 'DisplayName')){
			$autoOpen = '';
			
			/// Automatically unfolds weapon if "AutoExpand" user setting is set.
			if ($userSettings['autoExpand']){
				$autoOpen = 'open';
			}
			
			/// If the weapon is present multiple times, like for the Megalith for example, we display a little "x2" or "x3" with its name.
			$duplicates = "";
			if ($occurences > 1){
				$duplicates = "[x".$occurences."] ";
			}
			
			/// Displaying name of the weapon
			echo '<details class="weaponList" '.$autoOpen.'> 
					<summary  class="weaponCategory">
						'.$duplicates.(attemptTranslation($thisWeapon->DisplayName, $dataLoc, $userSettings['lang'])).'
					</summary>
					<div class="flexRows">';
					
			//// The following properties will be displayed (the rough value, without units) if found.
			$propertiesToDisplay = ['SlavedToBodyArcRange', 'FiringRandomnessWhileMoving', 'FiringRandomness', 'MaxProjectileStorage', 'DamageToShields', 'InitialDamage', 'DoTPulses', 'DoTTime'];
			
			//// The following properties will be displayed (the rough value, without units) if found AND if their rough value is greater than 0.
			$propertiesToDisplayGreaterThanZero = ['DamageRadius', 'MuzzleVelocity', 'FiringTolerance']; 
			
			//// The following properties will be displayed (the rough value, without units) if found AND if their rough value is greater than 1.
			$propertiesToDisplayGreaterThanOne = ['MuzzleSalvoSize']; 
				
			// Displaying a colored name for the weapon category
			if (property_exists($thisWeapon, 'WeaponCategory')){ 
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" style="width:50%;">
							Weapon Category
						</div>
						<div class="littleInfoVar" style="color:'.getWeaponCategoryColor($thisWeapon->WeaponCategory).';width:50%;text-align:left;">
							'.($thisWeapon->WeaponCategory).'
						</div>
					</div>';
			}
			
			/// Displaying damage type if necessary
			if (property_exists($thisWeapon, 'DamageType') &&
				$thisWeapon->DamageType != "Normal"){ 
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							DamageType
						</div>
						<div class="littleInfoVar" >
							'.($thisWeapon->DamageType).'
						</div>
					</div>';
			}	
            
            /// Approximate DPS
			if (property_exists($thisWeapon, 'Damage') &&
				$thisWeapon->Damage > 0 &&
                $thisWeapon->WeaponCategory != "Death"){ 
				echo '<div class="flexColumns weaponLine" style="background-color:'.getFactionColor($info['Faction'], 'bright').';">
						<div class="littleInfo" style="color:'.getFactionColor($info['Faction'], 'dark').';">
							<b>Approximate DPS</b>
						</div>
						<div class="littleInfoVar" style="color:'.getFactionColor($info['Faction'], 'dark').';">
							<b>'.format(calculateDps($thisWeapon, $info['Id'])).'</b>
						</div>
                    </div>';
            }
			
			/// Specific Damage styled display if the weapon has damage
			if (property_exists($thisWeapon, 'Damage') &&
				$thisWeapon->Damage > 0){ 
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							Raw Dmg
						</div>
						<div class="littleInfoVar" >
							'.format($thisWeapon->Damage).'
						</div>
						';
				if (property_exists($thisWeapon, 'RateOfFire')){
					echo '<div class="littleInfo" style="text-align:right;border-left:1px dotted grey;">
							Firerate
						</div>
						<div class="littleInfoVar" >
							'.number_format (($thisWeapon->RateOfFire), 2).' /s 
						</div>';
				}
				echo '
					</div>';
			}	
			
			/// Displaying the rate of fire differently depending if the weapon has a continuous beam or not
			if (property_exists($thisWeapon,'ContinuousBeam') &&
				$thisWeapon->ContinuousBeam){
					echo '<div class="flexColumns weaponLine">
							<div class="littleInfo" >
								Fire cycle
							</div>
							<div class="littleInfoVar" >
								Continuous
							</div>
						</div>';
				}
			else if (property_exists($thisWeapon, 'MuzzleSalvoSize')){ 
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							Fire cycle
						</div>
						<div class="littleInfoVar" >
							'.calculateFireCycle($thisWeapon).' projectiles / shot
						</div>
					</div>';
			}		

			
			// Displaying range of the weapon - both minradius and maxradius if available
			if (property_exists($thisWeapon, 'MaxRadius') &&
				$thisWeapon->MaxRadius > 0){ 
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							Range
						</div>
						<div class="littleInfoVar" >
						';
							if (property_exists($thisWeapon, 'MinRadius') &&
								$thisWeapon->MinRadius > 0){ 
									echo ($thisWeapon->MinRadius).'-';
							}
				echo '
							'.($thisWeapon->MaxRadius).'
						</div>
					</div>';
			}
			
			/// Displaying nuke radius and nuke-related info
			if (
				property_exists($thisWeapon, 'NukeInnerRingRadius') &&
				property_exists($thisWeapon, 'NukeOuterRingRadius') &&
				property_exists($thisWeapon, 'NukeInnerRingDamage') &&
				property_exists($thisWeapon, 'NukeOuterRingDamage')
				){
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							Nuke radius
						</div>
						<div class="littleInfoVar" >
							'.format($thisWeapon->NukeInnerRingRadius).'-'.format($thisWeapon->NukeOuterRingRadius).'
						</div>
					</div>';
				echo '<div class="flexColumns weaponLine">
						<div class="littleInfo" >
							Nuke damage
						</div>
						<div class="littleInfoVar" >
							'.format($thisWeapon->NukeOuterRingDamage).'-'.format($thisWeapon->NukeInnerRingDamage).'
						</div>
					</div>';
				
			}
			
			/// If the weapon uses a missile, and we have this missile in the $data, lets display more information about the missile
			if (property_exists($thisWeapon, 'ProjectileId')){
				
				$foundArr = [];
				$found = preg_match('~(?<=projectiles\/).*(?=\/)~', $thisWeapon->ProjectileId, $foundArr);
				
				if ($found){
					
					$projectileId = $foundArr[0];
					
					if (strlen($projectileId) > 0){
						/// searching the needle in the haystack...
						foreach($dataMissiles as $thisMissile){
							if ($thisMissile->Id == strtoupper($projectileId)){
								// found it !
								
								/// Display the blueprint + github link
									echo '
								<div class="flexColumns weaponLine" style="margin-bottom:4px;">
									<div class="littleInfo" style="text-align:center;" >
										Missile ID/BP
									</div>
									<div class="littleInfoVar"  style="text-align:center;margin:0px;"  >
										<a class="blueprintLink externalBlueprint" href="https://github.com/FAForever/fa/blob/deploy/fafdevelop/projectiles/'.($projectileId).'">
											'.($projectileId).'
										</a>
									</div>
								</div>';
								
								/// Display cost if any
								if (property_exists($thisMissile, 'Economy')){
									$eco = $thisMissile->Economy;
									echo '
								<div class="flexRows weaponLine" style="margin-bottom:4px;">
									<div class="littleInfo" style="text-align:center;" >
										Missile Cost
									</div>
									<div class="littleInfoVar"  style="text-align:center;margin:0px;"  >
										<div class="flexColumns" style="color:black;" style="text-align:center;" >
											<div class="energyCost bubbleInfo">
												<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.format($eco->BuildCostEnergy).'
												<span class="bubbleText">Energy cost</span>
											</div>
											<div class="massCost bubbleInfo">
												<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.format($eco->BuildCostMass).'
												<span class="bubbleText">Mass cost</span>
											</div>
											<div class="buildTimeCost bubbleInfo">
												<img alt="tim" style="vertical-align:top;" src="res/img/icons/time.png"> '.format($eco->BuildTime).'
												<span class="bubbleText">Build time</span>
											</div>
										</div>
									</div>
								</div>';
								}
								break;
							}
						}
						
					}
				}
			}
			
			/// Displaying every generic property now
			foreach($propertiesToDisplayGreaterThanZero as $thisProp){
				if (property_exists($thisWeapon, $thisProp) &&
					$thisWeapon->$thisProp > 0){
					echo '<div class="flexColumns weaponLine">
							<div class="littleInfo" style="border-left:1px dotted grey;">
								'.caseFormat($thisProp).'
							</div>
							<div class="littleInfoVar" >
								'.format($thisWeapon->$thisProp).'
							</div>
						</div>';
				}
			}	
			foreach($propertiesToDisplayGreaterThanOne as $thisProp){
				if (property_exists($thisWeapon, $thisProp) &&
					$thisWeapon->$thisProp > 1){
					echo '<div class="flexColumns weaponLine">
							<div class="littleInfo" style="border-left:1px dotted grey;">
								'.caseFormat($thisProp).'
							</div>
							<div class="littleInfoVar" >
								'.format($thisWeapon->$thisProp).'
							</div>
						</div>';
				}
			}	
			foreach($propertiesToDisplay as $thisProp){
				if (property_exists($thisWeapon, $thisProp)){
					echo '<div class="flexColumns weaponLine">
							<div class="littleInfo" style="border-left:1px dotted grey;">
								'.caseFormat($thisProp).'
							</div>
							<div class="littleInfoVar" >
								'.format($thisWeapon->$thisProp).'
							</div>
						</div>';
				}
			}	
			
			/// That's it. Nothing more to display.
			echo '	</div>
				</details>';
		}
	}
	
	function displayVeterancy($info, $thisUnit){
		
		/// Before displaying any veterancy, we must check the following :
		// The unit has AT LEAST one weapon
		// If the unit has only one weapon, it is NOT a death weapon (else the power generators would be vetting)
		
		if (property_exists($thisUnit, 'Weapon') && 
			(count((array)$thisUnit->Weapon) > 1 || (property_exists(((array)($thisUnit->Weapon))[0], "WeaponCategory") && ((array)($thisUnit->Weapon))[0]->WeaponCategory != "Death"))){
			
			
			///////////
			/// If this is a commander, display the custom veterancy system with multiple tabs
			///////////
			echo '<div class="sheetSection">
			<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
			Veterancy
			</div>';
			if (in_array("COMMAND", $info['Categories']) ||		
				in_array("SUBCOMMANDER", $info['Categories'])){
					
					$veterancies = ["T1"=>1, 
									"T2"=>0.5, 
									"T3"=>0.33333, 
									"T4"=>0.25, 
									"SACU"=>0.3, 
									"ACU"=>0.05];
									
					$firstSelection = "T1";
					
					echo '
						<div class="flexColumns tabZone">';
						
					foreach($veterancies as $vetName=>$vetFactor){
						
						$selected = "";
						if ($vetName == $firstSelection){
							$selected = 'selected=selected';
						}
						
						echo '<div 
								class="tabButton'.$info['Id'].'" 
								id ="tabButton'.$vetName.$info['Id'].'" 
								onClick="openTab(
										\'tabButton'.$info['Id'].'\', 
										\'tabButton'.$vetName.$info['Id'].'\',
										\''.$info['Id'].'\',
										\''.$vetName.$info['Id'].'\' )" 
										'.$selected.'>
											
									'.$vetName.'
									
								</div>';
					}
					
						
					echo '
						</div>';
					
					foreach($veterancies as $vetName=>$vetFactor){
						$display = '';
						if ($vetName != $firstSelection){
							$display = "style='display:none;'";
						}
						echo '
						<div class="'.$info['Id'].' ACUVetZone" id="'.$vetName.$info['Id'].'" '.$display.'>
							<div class="flexRows info" style="color:'.getFactionColor($info['Faction'], "bright").';">';
							for ($i = 0; $i < 5; $i++){
								echo '
									<div class="flexColumns">		
										<div class="veterancyLogoZone">
											';
											
											for ($j = 0; $j < $i+1; $j++){
												echo '<img alt="X" src="res/img/icons/'.strtolower($info['Faction']).'-veteran.png">';
											}
											
											echo'
										</div>		
										<div>
											<img alt="X" src="res/img/icons/mass.png" style="vertical-align:middle;">
											'.format(($i+1)*((($info['Economy']->BuildCostMass)/2)/$vetFactor)).'
										</div>			
										<div style="font-weight:normal;">
											'.format($info['Health'] +  $info['Health']*(0.1*($i+1))).'HP+'.($info['Regen'] + 3*($i+1)).'/s
										</div>
									</div>
									';
							}
						echo '</div>
						</div>
						';
					}
				}
				
			///////////
			/// Not a commander, displays regular veterancy
			///////////
			else{
			echo '
					<div class="flexColumns tabZone">';
												
					echo '<div class="allUnitsVetTab">
										
								(All units)
								
							</div>';
				
					
				echo '
					</div>';
				echo '
					<div class="ACUVetZone">
						<div class="flexRows info" style="color:'.getFactionColor($info['Faction'], "bright").';">';
						for ($i = 0; $i < 5; $i++){
							echo '
								<div class="flexColumns">		
										<div class="veterancyLogoZone">
										';
										
										for ($j = 0; $j < $i+1; $j++){
											echo '<img alt="X" src="res/img/icons/'.strtolower($info['Faction']).'-veteran.png">';
										}
										
										echo'
									</div>		
									<div style="width:30%;">
										<img alt="X" src="res/img/icons/mass.png" style="vertical-align:middle;">
										'.format(($i+1)*((($info['Economy']->BuildCostMass)))).'
									</div>			
									<div style="width:40%;font-weight:normal;">
										'.format($info['Health'] +  $info['Health']*(0.1*($i+1))).'HP+'.($info['Regen'] + 3*($i+1)).'/s
									</div>
								</div>
								';
						}
					echo '</div>
					</div>';
				
			}
			echo '</div>';
		}
	}
	
	function displayWreckage($info, $thisUnit){
		if (property_exists($thisUnit, "Wreckage")){
			echo '<div class="sheetSection">
				<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
				Wreckage
				</div>';
				
			echo ' 	<div class="flexColumns" style="color:'.getFactionColor($info['Faction'], 'bright').';">';
				$hpMul = $thisUnit->Wreckage->HealthMult;
				$massMul = $thisUnit->Wreckage->MassMult * $hpMul;
				$hp = $info['Health']*$hpMul;
				$mass = ($info['Economy']->BuildCostMass) * $massMul;
				
				echo '	<div class="flexRows">
							<div class="info">
								HP
							</div>			
							<div class="info">
								Mass
							</div>								
						</div>
						<div class="flexRows">
							<div class="info">
								'.format($hp).'
							</div>			
							<div class="info">
								'.format($mass).'
							</div>								
						</div>
						<div class="flexRows">
							<div class="info">
								('.($hpMul*100).'%)
							</div>			
							<div class="info">
								('.($massMul*100).'%)
							</div>								
						</div>
				';
				
			echo '	</div>
				</div>';
		}
	}
	
	function displayUnitPhysics($info, $thisUnit){
		/// Check if there is any physic interesting information to display
		if (property_exists($thisUnit, 'Physics') &&
			(property_exists($thisUnit->Physics, 'TurnRate') && 
			$thisUnit->Physics->TurnRate > 0) ||
			(property_exists($thisUnit ,'Air'))){
				
				
			$physics = $thisUnit->Physics;
			$air = null;
			$titleString = "Physics";
			
			/// Checking if the unit is a plane
			if (property_exists($thisUnit, 'Air')){
				$air = $thisUnit->Air;
				$titleString .= " / Air";
			}
			
			echo '<div class="sheetSection">
					<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					'.$titleString.'
					</div>';
					
				/// If the unit is a plane, we will have two columns to display : one for land physic (when the unit is grounded), one for Air physics.
			if ($air != null){
				echo '<div class="flexColumns">';
			}
		
			echo '
				<div class="flexRows">
					';
					
					/// Dumping every info we can
					if (property_exists($physics, 'TurnRate') && $physics->TurnRate > 0) echo '
						<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
							Turn rate : '.($physics->TurnRate).' °/s
						</div>';
					if (property_exists($physics, 'MaxSpeed') && $physics->MaxSpeed > 0) echo '
						<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
							Max speed : '.($physics->MaxSpeed).'
						</div>';
					if (property_exists($physics, 'FuelRechargeRate') && $physics->FuelRechargeRate > 0) echo '
						<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
							Fuel refill rate: '.(($physics->FuelRechargeRate)).'
						</div>';
					if (property_exists($physics, 'FuelUseTime') && $physics->FuelUseTime > 0) echo '
						<div class ="info" 
							style="
							color:'.getFactionColor($info['Faction'], 'bright').';">
							Fuel use time : '.($physics->FuelUseTime).'s
						</div>';
					if (property_exists($physics, 'LandSpeedMultiplier') && $physics->LandSpeedMultiplier != 1) echo '
						<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
							Land speed multiplier : '.floor(($physics->LandSpeedMultiplier)*100).'%
						</div>';
			echo '
				</div>';
				
			/// Now for air physics, if the thing is a plane
			if ($air != null){
				echo '<div class="flexRows">
				';
				if (property_exists($air, 'TurnSpeed') && $air->TurnSpeed > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						Turn speed : '.($air->TurnSpeed).'
					</div>';
				if (property_exists($air, 'MaxAirspeed') && $air->MaxAirspeed > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						Max air speed : '.($air->MaxAirspeed).'
					</div>';
				if (property_exists($air, 'EngageDistance') && $air->EngageDistance > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						Engage Distance: '.($air->EngageDistance).'
					</div>';
				if (property_exists($air, 'MinAirspeed') && $air->MinAirspeed > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						Min air speed : '.($air->MinAirspeed).'
					</div>';
				if (property_exists($air, 'CombatTurnSpeed') && $air->CombatTurnSpeed > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						CombatTurnSpeed : '.($air->CombatTurnSpeed).'
					</div>';
				if (property_exists($air, 'LayerTransitionDuration') && $air->LayerTransitionDuration > 0) echo '
					<div class ="info" style="color:'.getFactionColor($info['Faction'], 'bright').';">
						LayerTransitionDuration : '.($air->LayerTransitionDuration).'s
					</div>';
				echo '
				</div>
			</div>';
			}
		
			echo '
			</div>';
		}
	}
	
	function displayUnitSupport($info, $thisUnit){
		if (property_exists($thisUnit, 'Intel')){
			$intel = $thisUnit->Intel;
				
			echo '
			<div class="sheetSection">
				<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
				'.'Support'.'
				</div>
				
				<div class="flexColumns">
					
				';
			if (property_exists($intel, 'VisionRadius')) echo '
					<div class="visionRadius" 
						style="
						color:'.getFactionColor($info['Faction'], 'bright').';">
						Vision : '.($intel->VisionRadius).'
					</div>';
			if (property_exists($intel, 'RadarRadius')) echo '
					<div class="radarRadius"
						style="
						color:'.getFactionColor($info['Faction'], 'bright').';">
						Radar : '.($intel->RadarRadius).'
					</div>';
			if (property_exists($intel, 'SonarRadius')) echo '
					<div class="sonarRadius"
						style="
						color:'.getFactionColor($info['Faction'], 'bright').';">
						Sonar : '.($intel->SonarRadius).'
					</div>';
			echo '
				</div>';
			
			/// Let's display additional support stats if needed
			if(
				// either it is stealthy
				((property_exists($intel, "RadarStealth") && ($intel->RadarStealth == true)) || property_exists($intel, "RadarStealthFieldRadius")) ||
				
				// or it is shieldy
				(property_exists($thisUnit, "Defense") && property_exists($thisUnit->Defense, "Shield")) ||
				
				// or it has omni or watervisionradius
				(property_exists($thisUnit, "Intel") && (property_exists($thisUnit->Intel, "WaterVisionRadius") || property_exists($thisUnit->Intel, "OmniRadius")))
				
			   ){
				   
				echo '
				<div style="color:'.getFactionColor($info['Faction'], 'bright').';">';
				
				/// This array will be populated with the stats to display
				$toDisplay = array();
				
				if((property_exists($intel, "RadarStealth") && ($intel->RadarStealth == true)) || property_exists($intel, "RadarStealthFieldRadius")){
					/// List of STEALTH elements we want to display if we find them
					$whitelist = array(
						"RadarStealthFieldRadius",
						"SonarStealthFieldRadius"
					);
					foreach($intel as $key=>$value){
						if (!in_array($key, $whitelist)){
							continue;
						}
						$toDisplay[caseFormat($key)] = $value;
					}
				}
				
				if (property_exists($thisUnit, "Defense") && property_exists($thisUnit->Defense, "Shield")){
				/// List of SHIELD elements we want to display if we find them
					$whitelist = array(
						"ShieldSize",
						"RegenAssistMult",
						"ShieldRegenStartTime"
					);
					foreach($thisUnit->Defense->Shield as $key=>$value){
						if (!in_array($key, $whitelist)){
							continue;
						}
						$toDisplay[caseFormat($key)] = $value;
					}
				}
				
				/// Other support elements we might want to display 
				$whitelist = array(
						"WaterVisionRadius",
						"OmniRadius"
						);
				
				foreach($thisUnit->Intel as $key=>$value){
					if (!in_array($key, $whitelist)){
						continue;
					}
					$toDisplay[caseFormat($key)] = $value;
				}
				
				/// Actual display
				foreach($toDisplay as $name=>$value){
					echo '
					<div class="supportData">
						<div class="info supportInfo">
							'.$name.'
						</div>
						<div class="info supportInfoVar">
							'.$value.'
						</div>
					</div>';
				}
				  
				echo '
				</div>';
			}
				
			echo '
			</div>';
		}
	}
	
	function displayUnitAbilities($info, $dataLoc, $lang){
		if (property_exists($info['Display'], "Abilities")){
			$abilities = $info['Display']->Abilities;
			
			echo '
			<div class="sheetSection">
				<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Abilities
				</div>
				<div class="flexWrap" style="padding-right:10px;padding-left:10px;">
					';
					
					foreach($abilities as $thisAb){
						
						echo '<div style="font-weight:bold;
											text-shadow: 1px 1px black;
											color:'.getFactionColor($info['Faction'], 'bright').';"> 
							[
								'.attemptTranslation($thisAb, $dataLoc, $lang).'
							]</div>';
					}
					
					echo '
				</div>
			</div>';
		}
	}
	
	function displayUnitTitle($thisUnit, $info, $dataLoc, $lang){
		$nickname = '';
		if (property_exists($thisUnit->General, 'UnitName')){
			$nickname = '"'.($thisUnit->General->UnitName).'" ';
		}					
		echo '<div style="position:relative;">
				<div style="margin-right:27px;">
					'.getUnitTitle($thisUnit, $dataLoc, $lang).'
				</div>
				<button class="comparatorRemoveButton"
						onClick="removeUnitFromComparator(\''.($thisUnit->Id).'\')">
				X
				</button>';
		echo '</div>';
	}

	function displayUnitHealthDefense($info, $thisUnit){
		echo '
			<div class="healthBar" style="color:'.getFactionColor($info['Faction'], 'bright').';">
				'.format($info['Health']).'HP + '.$info['Regen'].'/s
			</div>';
		
		// If this is a shield, we should display "Shield HP" as well
		if (property_exists($thisUnit->Defense, "Shield")){
			
			$shieldHealth = 0;
			$shieldRegenRate = 0;
			$shieldSize = 0;
			
			if (property_exists($thisUnit->Defense->Shield, "ShieldMaxHealth")) $shieldHealth = $thisUnit->Defense->Shield->ShieldMaxHealth;
			if (property_exists($thisUnit->Defense->Shield, "ShieldRegenRate")) $shieldRegenRate = $thisUnit->Defense->Shield->ShieldRegenRate;
			if (property_exists($thisUnit->Defense->Shield, "ShieldSize")) $shieldSize = $thisUnit->Defense->Shield->ShieldSize;
			
			echo '
			<div style="color:'.getFactionColor($info['Faction'], 'bright').';" class="shieldBar unitcardShieldBar">
				'.format($shieldHealth).'HP + '.$shieldRegenRate.'/s
			</div>';
		}
		
	}
	
	function displayUnitEconomics($info){
			
		//ECONOMY

		
		/// Buildtime, NRG cost or mass cost. Every unit /should/ have this, but because one or two does not, we're checking if they do just in case.
		$nrgCost = 0;
		$mssCost = 0;
		$bldCost = 0;
		
		if (property_exists($info['Economy'], 'BuildCostEnergy')) $nrgCost = $info['Economy']->BuildCostEnergy;
		if (property_exists($info['Economy'], 'BuildCostMass')) $mssCost = $info['Economy']->BuildCostMass;
		if (property_exists($info['Economy'], 'BuildTime')) $bldCost = $info['Economy']->BuildTime;
			
		echo '<div class="sheetSection">
				<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Build costs
					</div>
					
					<div class="flexColumns" style="padding-bottom:4px;">
						<div class="energyCost bubbleInfo">
							<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.format($nrgCost).'
							<span class="bubbleText">Energy cost</span>
						</div>
						<div class="massCost bubbleInfo"">
							<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.format($mssCost).'
							<span class="bubbleText">Mass cost</span>
						</div>
						<div class="buildTimeCost bubbleInfo"">
							<img alt="tim" style="vertical-align:top;" src="res/img/icons/time.png"> '.format($bldCost).'
							<span class="bubbleText">Build time</span>
						</div>
					</div>
				</div>';
		
		
		/// Following section only displays if the unit either produces, or consumes ressources
		if (property_exists($info['Economy'], 'ProductionPerSecondMass') ||
			property_exists($info['Economy'], 'ProductionPerSecondEnergy') ||
			property_exists($info['Economy'], 'MaintenanceConsumptionPerSecondEnergy') ||
			property_exists($info['Economy'], 'MaintenanceConsumptionPerSecondMass') ||
			property_exists($info['Economy'], 'BuildRate')){
				
				$build = 0;
				$nrg = 0;
				$mass = 0;
				
				if (property_exists($info['Economy'], 'MaintenanceConsumptionPerSecondMass')) $mass -= $info['Economy']->MaintenanceConsumptionPerSecondMass;
				if (property_exists($info['Economy'], 'MaintenanceConsumptionPerSecondEnergy')) $nrg -= $info['Economy']->MaintenanceConsumptionPerSecondEnergy;
				if (property_exists($info['Economy'], 'BuildRate')) $build += $info['Economy']->BuildRate;
				if (property_exists($info['Economy'], 'ProductionPerSecondMass')) $mass += $info['Economy']->ProductionPerSecondMass;
				if (property_exists($info['Economy'], 'ProductionPerSecondEnergy')) $nrg += $info['Economy']->ProductionPerSecondEnergy;
				
				if ($mass > 0){
					$mass = '<span class="positiveYield">'.$mass.'</span>';
				}
				else if ($mass < 0){
					$mass = '<span class="negativeYield">'.$mass.'</span>';
				}
				if ($nrg > 0){
					$nrg = '<span class="positiveYield">'.$nrg.'</span>';
				}
				else if ($nrg < 0){
					$nrg = '<span class="negativeYield">'.$nrg.'</span>';
				}
				if ($build > 0){
					$build = '<span class="positiveYield">'.$build.'</span>';
				}
					
				echo '<div class="sheetSection">
					<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Yield / Drain
					</div>
					
					<div class="flexColumns" style="padding-bottom:4px;">
						<div class="energyCost bubbleInfo">
							<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.$nrg.'
							<span class="bubbleText">Energy yield / drain</span>
						</div>
						<div class="massCost bubbleInfo">
							<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.$mass.'
							<span class="bubbleText">Mass yield / drain</span>
						</div>
						<div class="buildTimeCost bubbleInfo">
							<img alt="tim" style="vertical-align:top;" src="res/img/icons/time.png"> '.$build.'
							<span class="bubbleText">Build capacity</span>
						</div>
					</div>
				</div>';
				
		}
			
			/// Following section only displays if the unit is storing ressources, either mass or energy
			if (property_exists($info['Economy'], 'StorageMass') ||
				property_exists($info['Economy'], 'StorageEnergy')){
					
				$nrg = 0;
				$mass = 0;
				
				if (property_exists($info['Economy'], 'StorageMass')) $mass += $info['Economy']->StorageMass;
				if (property_exists($info['Economy'], 'StorageEnergy')) $nrg += $info['Economy']->StorageEnergy;
				
				echo '<div class="sheetSection">
					<div class="smallTitle"  style="color:'.getFactionColor($info['Faction'], 'bright').';">
					Storage
					</div>
					
					<div class="flexColumns" style="padding-bottom:4px;">
						<div class="energyCost bubbleInfo">
							<img alt="nrg" style="vertical-align:top;" src="res/img/icons/energy.png"> '.$nrg.'
							<span class="bubbleText">Energy storage</span>
						</div>
						<div class="massCost bubbleInfo">
							<img alt="mss" style="vertical-align:top;" src="res/img/icons/mass.png"> '.$mass.'
							<span class="bubbleText">Mass storage</span>
						</div>
					</div>
				</div>';
			}
	}
	
	
	/// END OF
	
	function getWeaponCategoryColor($cat){
		switch($cat){
			case "Death":
				return 'grey;';
				break;
			case "Direct Fire Naval":
				return 'lightgreen;';
				break;
			case "Anti Navy":
				return 'green;';
				break;
			case "Defense":
				return 'DarkOrange ;';
				break;
			case "Teleport":
				return 'MediumPurple;';
				break;
			case "Anti Air":
				return 'Aqua;';
				break;
			case "Bomb":
				return 'DarkRed;';
				break;
			case "Death":
				return 'grey;';
				break;
			case "Artillery":
				return 'yellow;';
				break;
			case "Missile":
				return 'DarkKhaki ;';
				break;
			case "Kamikaze":
				return 'white ;';
				break;
			case "Direct Fire Experimental":
			case "Experimental":
				return 'red;';
				break;
		}
	}
	
	function getBasicUnitInfo($thisUnit, $dataLoc, $userSettings){
		
		// Some units with a regenrate of 0 don't get detected properly. Putting the value to 0 if it doesn't exist, "just in case".
		if (!property_exists($thisUnit->Defense, "RegenRate")){
			$thisUnit->Defense->RegenRate = 0;
		}

		if (!property_exists($thisUnit, "StrategicIconName")){
			$thisUnit->StrategicIconName = "unknown";
		}
		
		/// Filling up basic unit information. **EVERY BLUEPRINT** normally has these infos.
		return array(
					'Description' => getDescription($thisUnit, $dataLoc, $userSettings),
					'Health'=>$thisUnit->Defense->Health,
					'Regen'=>$thisUnit->Defense->RegenRate,
					'Economy'=>$thisUnit->Economy,
					'Faction'=>$thisUnit->General->FactionName,
					'Id'=>strtoupper($thisUnit->Id),
					'Strategic'=>$thisUnit->StrategicIconName,
					'Display'=>$thisUnit->Display,
					'Categories'=>$thisUnit->Categories);
	}
	
	function getDescription ($thisUnit, $dataLoc, $userSettings){
		if (property_exists($thisUnit, 'Description')){
			$description = ($thisUnit->Description);
			$matches = [];
			if (preg_match ('/(<loc.*>+)/', $description, $matches) || preg_match ('/(<LOC.*>+)/', $description, $matches)){
				$line = $matches[0];
				$line = str_replace('<loc ', '', $line);
				$line = str_replace('<LOC ', '', $line);
				$line = str_replace('>', '', $line);
				$thisLang = $dataLoc[$userSettings['lang']];
				if (is_array($thisLang) && array_key_exists($line, $thisLang)){
					$description = $thisLang[$line];
				}
			}
			return $description;
		}
		return "Unknown unit";
	}
	
	function filterCommonCategories($toCompare, $categories){
		$components = array();
		
		foreach($toCompare as $thisUnit){
			foreach ($categories as $thisProperty){
				if (property_exists($thisUnit, $thisProperty) &&
					!in_array($thisProperty, $components)){
					$components [] = $thisProperty;
				}
			}
		}
		$components = array_slice (array_merge(($categories), $components), 0, sizeOf($categories));
		return $components;
	}
	
	function getUnitsToCompare($get, $dataUnits){
		$list = array_unique(explode(',', strtoupper($get)));
		$toCompare = array();
		
		for ($i = 0; $i < sizeOf($dataUnits); $i++){
			$element = $dataUnits[$i];
			$id = $element->Id;
			
			foreach($list as $unit){
				if ($unit == $id){
					$toCompare[] = $element;
				}
			}
		}
		
		return $toCompare;
	}
	
	function getGameData($dataPath = ""){
		if (!file_exists($dataPath."data/blueprints.json")){
			return false;
		}
		
		$dataString = file_get_contents($dataPath."data/blueprints.json");
		$dataFull = json_decode($dataString);
		$dataUnits = [];
		$dataMissiles = [];
		$dataLoc = json_decode(file_get_contents($dataPath."data/localization.json"), true);
		
		foreach($dataFull as $thisUnit){
			if ($thisUnit->BlueprintType == "UnitBlueprint"){
				$dataUnits[]=$thisUnit;
			}
			else if ($thisUnit->BlueprintType == "ProjectileBlueprint"){
				$dataMissiles[]=$thisUnit;
			}
		}
		
		return array(
			"missiles"=>$dataMissiles,
			"units"=>$dataUnits,
			"localization"=>$dataLoc);
	}
		
	function makeCleanURL(){
		$strArgs = $_SERVER['QUERY_STRING'];
		if ($strArgs != ""){
			
			$argList = explode("&", $strArgs);
			
			$arguments = [];
			
			foreach($argList as $thisArgument){
				if ($thisArgument == ''){
					continue;
				}
				$thisExploded = explode("=", $thisArgument);
				$argName = $thisExploded[0];
				$argVal = $thisExploded[1];
				$arguments[$argName] = $argVal;
			}
			
			$finalArgString = '?';
			$iterator = 0;
			foreach($arguments as $key=>$value){
				if ($iterator > 0){
					$finalArgString .= "&";
				}
				$finalArgString .= $key."=".$value;
				$iterator++;
			}
			
			$cleanURL = $_SERVER['SCRIPT_NAME'].$finalArgString;
			
		}
		
		else{
			$cleanURL = $_SERVER['SCRIPT_NAME'];
		}
		
		$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$cleanURL."";
		
		$s = "?";
		if (count($_GET)){
			$s = "&";
		}
		
		return array(
			'url'=>$url,
			'separator'=>$s
		);
	}
	
	function updateSettings($defaultSettings){
		$userSettings = $defaultSettings;
		foreach($defaultSettings as $key=>$thisSetting){
			if (!isset($_POST[$key])){
				$userSettings[$key] = $defaultSettings[$key];
			}
			else{
				switch ($key){
					default:
						$thisSetting = $_POST[$key];
						break;
					
					case "autoExpand":
					case "spookyMode":
					case "experimentalPreview":
						if (array_key_exists($key, $_POST) && $_POST[$key] == "on"){
							$thisSetting = "1";
						}
						else{
							$thisSetting = "0";
						}
						break;
						
					case "showArmies":
						$thisSetting = explode(",",$_POST[$key]);
						break;
				}
			}
			
			if ($key != "settingsMod"){
				$userSettings[$key] = $thisSetting;
			}
		}
		return $userSettings;
	}
	
	function getFactionColor($faction, $tint="normal"){
		switch($faction){
			default:
				$color['normal'] = "grey";
				$color['bright'] = "lightgrey";
				$color['dark'] = "#222222";
				$color['hue'] = 300;
				break;
		
			case "Cybran" :
				$color['normal'] = "#C3272B";
				$color['bright'] = "#F1A9A0";
				$color['dark'] = "#6A0C0C";
				$color['hue'] = 0;
				break;
				
			case "Seraphim" :
				$color['normal'] = "#F4B350";
				$color['bright'] = "#FDE3A7";
				$color['dark'] = "#664d00";
				$color['hue'] = 20;
				break;
				
			case "UEF" :
				$color['normal'] = "#2C4770";
				$color['bright'] = "#ADB6C4";
				$color['dark'] = "#13294C";
				$color['hue'] = 227;
				break;
				
			case "Aeon" :
				$color['normal'] = "#87D37C";
				$color['bright'] = "#C8F7C5";
				$color['dark'] = "#004d00";
				$color['hue'] = 150;
				break;
				
			case "Nomads" :
				$color['normal'] = "#E57E00";
				$color['bright'] = "#FFBA66";
				$color['dark'] = "#7F4600";
				$color['hue'] = 20;
				break;
		}
		return $color[$tint];
	}
	
	function format($value){
		$string = number_format(round(floatval($value)), 0, ',', ' ');
		if (floatval($value) > 999999){
			$val = floatval($value);
			$val /= 1000000;
			$string = number_format($val, ($val)!=ceil($val), '.', ' ')."M ";
		}
		else if (floatval($value) > 99999){
			$val = floatval($value);
			$val /= 1000;
			$string = number_format($val, ($val)!=ceil($val), '.', ' ')."k ";
		}
		return $string;
	}
	
	function insertAtPosition($string, $insert, $position) {
		return implode($insert, str_split($string, $position));
	}
	function getUnitTitle($unit, $dataLoc=null, $lang='US'){
		$nickname = '';
		if (property_exists($unit->General, 'UnitName')){
			$nickname = '"'.attemptTranslation($unit->General->UnitName, $dataLoc, $lang).'" ';
		}
		$terrain = 'none';
		$strategic = 'none';
		$description = 'unit';
        $armyName = $unit->General->FactionName;
		if (property_exists($unit, 'StrategicIconName')){
			$strategic = $unit->StrategicIconName;
		}
		if (property_exists($unit->General, 'Icon')){
			$terrain = $unit->General->Icon;
		}
		if (property_exists($unit, 'Description')){
			$description = attemptTranslation($unit->Description, $dataLoc, $lang);
		}
		return '<div class="unitTitleBar">
					<div class="previewImg">
						<img alt="" class="backgroundIconOverlap" src="res/img/preview_background/'.($terrain).'_up.png">
						<img alt="?" class="strategicIcon" style= "width:64px;height:64px;" src="res/img/preview/'.strtoupper($unit->Id).'.png">
						<img alt="[x]" class="strategicIconOverlap" src="res/img/strategic/'.($strategic).'_rest.png" style="filter: hue-rotate('.(getFactionColor($armyName, 'hue')).'deg)">
					</div>
					<div style="color:'.getFactionColor($unit->General->FactionName, 'bright').';" >
						<p  class="previewTitle">
						'.$nickname.$description.'
						</p>
						<p style="
								padding-left:15px;
								margin:0px;">
							<a href="https://github.com/FAForever/fa/blob/deploy/fafdevelop/units/'.($unit->Id).'/'.($unit->Id).'_unit.bp"
								class="blueprintLink" style="color:'.(getFactionColor($unit->General->FactionName, 'bright')).';">
								'.strtoupper($unit->Id).'
							</a>
						</p>
					</div>
				</div>';
	}
	
	function getUnit($id, $dataUnits){
	
		for ($i = 0; $i < sizeOf($dataUnits); $i++){
			$element = $dataUnits[$i];
			$thisId = $element->Id;
			
			if ($id == $thisId){
				return $element;
				break;
			}
		}
		return false;
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
			$unitTech = "T4  ";
		}
		return $unitTech;
	}
	
	function is_JSON($args) {
		json_decode($args);
		return (json_last_error()===JSON_ERROR_NONE);
	}
	
	function caseFormat($string){
		$array = str_split($string);
		foreach($array as $key=>$letter){
			if ($key == 0){
				$letter = strtoupper($letter);
			}
			else if (ctype_lower($array[$key-1]) && ctype_upper($letter)){
				$letter = ' '.$letter;
			}
			$array[$key] = $letter;
		}
		return implode('', $array);
	}
	
	function attemptTranslation($string, $dataLoc, $thisLang){
        
		if ($dataLoc == null){
			return $string;
		}
		$matches = [];
        
		if (preg_match ('/(<LOC.*>+)/', $string, $matches)){
            
			$line = $matches[0];
			$line = str_replace('<LOC ', '', $line);
			$line = str_replace('>', '', $line);
			
			if (array_key_exists($line, $dataLoc[$thisLang])){
				$string = str_replace('"', '', $dataLoc[$thisLang][$line]);
			}
		}	
		$string = preg_replace ('/(<LOC.*>+)/', '', $string);
		
		return $string;
	}
	
	?>
