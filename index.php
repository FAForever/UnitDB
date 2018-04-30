<?php 

	include('./res/scripts/functions.php');
	
	///////////////////////////////////////
	///									///
	///				UNITDB 				/// 
	///									///
	///////////////////////////////////////

	// Cookie management must be done before everything else for security reasons.
	// HTTP Standard
	// So do not echo anything to the page before that is done.

	///////////
	// USER-SPECIFICS SETTINGS LOADING
	/////////
	
	$cookieName = "unitDB-settings";

	$defaultSettings = array(
		"showArmies"=>['Aeon','UEF','Cybran','Seraphim'],
		"previewCorner"=>"bottom left",
		"autoExpand"=>"0",
		"spookyMode"=>"0",
		"experimentalPreview"=>"0",
		"lang"=>"US"
	);
	$userSettings = $defaultSettings;

	/// STEP 1 : Checks if someone is passing settings in the URL of the page
	if (isset($_GET["settings64"])){
		$userSettings = json_decode(base64_decode($_GET["settings64"]), true);
		if (is_array($userSettings)){
			$userSettings = array_replace($defaultSettings, $userSettings);	// Merging with the default settings
		}
	}
	
	/// STEP 2 : Check if settings are in the cookies, and merges with them if there are
	if (isset($_COOKIE[$cookieName]) && (!isset($_GET["nocookies"]) || $_GET["nocookies"] != "1")){
		$userSettings = json_decode($_COOKIE[$cookieName], true);
		if (is_array($userSettings)){
			$userSettings = array_replace($defaultSettings, $userSettings);
		}
	}
	
	/// STEP 3 : Checks for POST setting modification and updates the cookie if needed - and merges with the array
	if (isset($_POST['settingsMod'])){
		$userSettings = updateSettings($defaultSettings);
		setcookie($cookieName, json_encode($userSettings), time()+86400*90);	// Updating the cookie. Setting it with a 90 days lifespan.
	}	
	
	//END OF SPECIFIC SETTINGS LOADING
	
	/// Now everything is settled, we can start displaying stuff
	
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<link href="style.css?v=<?php echo time();?>" rel="stylesheet" type="text/css">
	<link rel="icon" href="favicon.ico" />
	<title>SCFA Unit list</title>
	<script src="./res/scripts/uiBehavior.js">
		//// 
		///	Most javascript functions simply are display and interface functions
		///	
	</script>
</head>
  <body>

<?php
	
	$data = getGameData();
	
	/// If invalid or unsuitable game data, we shall exit with an error display.
	if ($data===false || !array_key_exists("missiles", $data) || !array_key_exists("units", $data) || !array_key_exists("localization", $data)){
		print_r('
			<p style="color:white;">
				Invalid or corrupt JSON blueprint data. Please run the update again.
			</p>
			');
		echo "</body>";
		exit;
	}
	$dataUnits = $data['units'];		/// List of units, each one as a JSON object
	$dataMissiles = $data['missiles'];	/// List of missiles, each one as a JSON object
	$dataLoc = $data['localization'];	
	
	/////////////////////////////////
	///
	///		UNIT COMPARATOR MODE
	///
	///
	
	if (isset($_GET['id']) && $_GET['id'] != "-1"){
	/// We're checking if the page requested is the comparator page. If the GET['id'] is set, and not set to -1, then the user is requesting the comparator page.
	
	
		/// Fetching the list of units to compare from the URL
		$toCompare = getUnitsToCompare($_GET['id'], $data['units']);
		
		/// Below is each category of information the comparator will display for the unit
		$categories = [
			"Titlecard",		/// The titlecards holds the unit name and preview, and the github link
			"HealthDefense",	/// Displays Health and Shield in a formatted bar
			"Economics",		/// "Economics" displays yield and drain, and engineering power
			"Display", 			// "Display" are, for example, unit categories (Amphibious, etc...)
			"Physics", 			// "Physics" are movespeed and such
			"Support", 			// "Support" is sonar, radar, stealth range, shield range
			"Wreckage", 		// "Wreckage" is information about wreckage HP and mass
			"FACTORY", 			// "FACTORY" serves to display the build list of the unit, if it can build any (Factory, crab, ...)
			"Veteran", 			// "Veteran" displays veterancy if the unit has a weapon
			"Weapon", 			// "Weapon" display a list of weapons
			"Enhancements"		// "Enhancements" display a list of commander upgrades
		];
		/// Any of these categories can be removed, and then the corresponding statistic will not be displayed.
		
		
		/// Now we filter this list of categories to only keep the one that are common to all the units that are about to be compared. No need to display "enhancements" if none of the compared units have upgrades.
		$components = filterCommonCategories($toCompare, $categories);

		/// "Section_end" is another dummy used to put a footer at the end of the units' card
		$components [] = "Section_end";	
		
		
		/// Starting the display of the comparator...
		echo '<div class="comparisonBoard">';
		
		/// For each component
		foreach ($components as $thisComponent){
			
			echo '<div class="boardLane">';
			
			/// For each unit
			foreach($toCompare as $thisUnit){
				
				/// Filling up basic unit information. **EVERY BLUEPRINT** normally has these infos.
				$info = getBasicUnitInfo($thisUnit, $data['localization'], $userSettings);
				
				/// The only hardcoded style info should/will be the faction color, which is dynamically decided in PHP depending on the number and type of factions.
				echo '<div class="unitCompared"
						style="background-color:'.getFactionColor($info['Faction'], 'dark').';">';
			
			
				
				switch ($thisComponent){
					
					/// Unit title and economics - common info
					case "Titlecard":
						displayUnitTitle($thisUnit, $info, $data['localization'], $userSettings['lang']);	// Basic title card with nickname, preview...
						break;
						
					case "HealthDefense":
						displayUnitHealthDefense($info, $thisUnit);	// Health bar and / or shield information
						break;
						
					case "Economics":
						displayUnitEconomics($info);// Yield and drain
						break;
						
					/// Abilities
					case "Display":
						displayUnitAbilities($info, $data['localization'], $userSettings['lang']);
						break;
					
					/// Sonar, radar and vision radius
					case "Support":
						displayUnitSupport($info, $thisUnit);
						break;
						
					/// Air and land physics - speed, turn rate, etc
					case "Physics":
						displayUnitPhysics($info, $thisUnit);
						break;
						
					/// Wreckage info like mass amount and health
					case "Wreckage":
						displayWreckage($info, $thisUnit);
						break;
						
					/// Veterancy system
					case "Veteran":
						displayVeterancy($info, $thisUnit);	
						break;
						
					/// Weapon list
					case "Weapon":
						displayWeaponList($info, $thisUnit, $userSettings, $data['missiles']);
						break;
						
					/// ACU/SACU upgrades
					case "Enhancements":
						displayEnhancements($info, $thisUnit, $userSettings, $data['localization']);
						break;
					
					/// Displays the list of units that this unit can build (megalith, factory, ...)
					case "FACTORY":
						displayBuildlist($info, $thisUnit, $userSettings, $data['units'], $data['localization']);
						break;
					
					/// Spawning the end of the unit card (a white horizontal bar with a small margin)
					case "Section_end":
						echo '<div class="sheetSection sectionTerminator"></div>';
						break;
				}
				
				echo '</div>';
			}
			echo '</div>
			';
		}
		echo '</div>
			<div style="height:48px;">
			</div>';
		
		
		/// Echoing the "<< BACK" button so the user can go back to the unit list
		echo '
			<div style="position:fixed;left:50%;bottom:10px;">
				<button class="comparatorPopup"					
						onClick="seeUnit()">
					<< Back to unit list
				</button>
			</div>';
	}
	////
	/// END OF COMPARATOR CODE
	/////////////////////////////////
	
	/////////////////////////////////
	///
	///		UNIT LIST MODE 
	///		If there is no GET['id'], the page displays the unit list instead of the comparator
	///
	
	else{
		
		/*
			The units are going to be sorted in multiple arrays for display. 
			The structure will be the following :
			
			Categories 						(Buildings, Command, ...)
				|__Techs 					(T1, T2, T3, ...)
					 |___Armies 			(Aeon, Cybran, ...)
							|___Unit	 	(URL0001, UEL0001, ...)
			
			For example, If I want to get the cybran beetle : 

			$beetleBlueprint = $listData["Vehicle"]["T2"]["Cybran"]["XRL0302"];
			
			This structure allows to display the whole list using multiple nested foreach() loops in an efficient way.
		
		*/

		$listData = [];		/// Will contain the whole data, structured as said above ^
		$armies = [];		/// Will contain the name of each faction, as a string
		
		/// The categories will be displayed in the following order :
		$categoriesOrder = array(
		
				'Command'=>null, 
				'Engineer'=>null, 
				'Building - Factory'=>null, 
				'Building - Economy'=>null, 
				'Building - Weapon'=>null, 
				'Building - Defense'=>null, 
				'Building - Sensor'=>null, 
				'Aircraft'=>null, 
				'Vehicle'=>null, 
				'Naval'=>null, 
				'Support'=>null,
				'Civilian & Miscellanous'=>null
			
		);
		
		/// This populates $listData using the $data['unit'] data, in a structure like described above.
		$categorizedData = categorizeUnitData($categoriesOrder, $userSettings, $data['units']);
		$listData = $categorizedData['listData'];
		$armies = $categorizedData['armyList'];
		
	
		///										///
		///	At this point, armies should be		///
		///	ready for display. Everything that	///
		/// follows is display.					///
		///										///
		
		
		/// Display each faction's logo and name
		displayFactionsHeader($armies);
		
		/// Actual unit list display
		echo '<div class="unitlistCategories" >';
		foreach($listData as $catName => $categories){
			/// Display each category ontop of the others (Buildings, Land units, Aircraft, ...)
			displayUnitlistTechs($catName, $categories, $userSettings, $armies, $data['localization']);
		}
		echo '</div>';
		/// End of
		
		/// Once every unit is displayed, we fire the "Check for units to compare" script to initialize UI elements javascript-side.
		echo '
		<script>
			checkForUnitsToCompare();
		</script>';
		
		/// Compare button 
		echo '
			<div class="comparatorButtonDiv">
				<button id="comparatorPopup" hidden	
						onClick="seeUnit()">
					Compare units...
				</button>
			</div>';
		
		/// License! The most important part.
		echo '<p>
				<a href="LICENSE" style="color:white;font-size:10px;">
					Made by rackover@racknet.noip.me - 2018
				</a>
			</p>
			<p>
				<a style="color:white;font-size:10px;" href="https://github.com/FAForever/UnitDB">
					See the source code on github...
				</a>
			</p>';
		
		
	} 
	
	if ($userSettings['experimentalPreview']){
		if ($userSettings['previewCorner'] != "None"){
		
			/// Position of the hover-preview zone
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
			
			/// Empty for now... Will be filled with unit title when necessary
			
			echo '<div id="tooltipZone" class="tooltipZone" style="'.$position.'">
				
			</div>';
		}
	}
	?>
	
	<button class="settingsButton"
			onClick="toggleSettingsMenu()">
		<div class="settingsDiv">
			Settings
		</div>
		
	</button>
	
	<div class="settingsMenu" id="settingsMenu">
		<div class="settingsMenuTitle">
			Settings
		</div>
		<div class="flexRows settingsMenuList">
			<form method="POST" name="settingsMod">
			<?php 
				
				displaySettingsMenu($defaultSettings, $userSettings, $data['localization']);
			?>
				<div>
					<input type="hidden" name="settingsMod" value=1>
					<input type="submit" value="Apply" class="settingsApplyButton"/>
				</div>
			</form>
		</div>
	</div>
	
	<?php echo file_get_contents("LICENSE"); ?>
	</body>
</html>