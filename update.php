<?php
function getFromGET($key, $default = null) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function setErrorHandling($debug) {
    if ($debug) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        echo '<div style="color:orange;background-color:#111111;font-family:Consolas;padding:8px;">';
    } else {
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    }
}

function verifySecret($keyName) {
    if (getenv($keyName) !== false && getFromGET('token') !== $_ENV[$keyName]) {
        http_response_code(403);
        exit;
    }
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$debug = getFromGET('debug');
setErrorHandling($debug);

verifySecret('UNITDB_UPGRADE_SECRET');

require(__DIR__ . '/res/scripts/luaToPhp.php');
require(__DIR__ . '/include/FileDownloader.php');
require(__DIR__ . '/include/Unzipper.php');

function rrmdir($src)
{
	if (file_exists($src)) {
		// echo '<p>--> Found '.$src.' [Exists] </p>';	////////DEBUG

		if (is_dir($src)) {
			$ls = scandirVisible($src);
			foreach ($ls as $thisSub) {
				if ($thisSub != "." && $thisSub != "..") {
					$full = $src . '/' . $thisSub;
					rrmdir($full);
				}
			}
		} else {
			// echo '<p>--> Not a directory ("'.$src.'"), unlinking </p>';	////////DEBUG
			unlink($src);
		}

		// echo '<p>--> Removing source "'.$src.'" </p>';	////////DEBUG
		if (file_exists($src))
			rmdir($src);
	}
}

function scandirVisible($dir)
{
	return array_diff(scandir($dir), array('..', '.'));
}

function prepareForConversion($string_bp)
{
	$string_bp = preg_replace('/--(.*)/', "", $string_bp);
	$string_bp = preg_replace('/#(.*)/', "", $string_bp);
	$string_bp = str_replace("'", '"', $string_bp);
	$string_bp = str_replace('Sound', '', $string_bp);

	return $string_bp;
}

function locfileToPhp($locContent)
{
	$exp = explode("\n", $locContent);
	$finalLoc = [];
	foreach ($exp as $line) {
		$content = explode('=', $line);
		if (count($content) <= 1) {
			continue;
		}
		$name = $content[0];
		$translation = $content[1];
		$translation = preg_replace("/(--\[\[(.*)--\]\]+)/", "", $translation);

		$finalLoc[$name] = $translation;
	}
	return $finalLoc;
}

//GET EXTRACTION INFO AND PREPARE FALLBACK
$toExtract = json_decode(file_get_contents('config/datafiles.json'));
$toExtractLoc = json_decode(file_get_contents('config/locfiles.json'));

$debug = false;

if (isset($_GET['debug'])) {
	$debug = $_GET['debug'];
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	echo '<div style="color:orange;background-color:#111111;font-family:Consolas;padding:8px;">';
}

file_put_contents("config/UPDATE.TMP", "If this file is present, either the database is updating or the last update failed.");

// STEP 0 : DOWNLOAD data IF NEED

if ($debug)
	echo '<p>STEP 0 ----- </p>'; ////////DEBUG

if (isset($_GET['version']) && $_GET['version'] != "local") {
	$version = $_GET['version'];

	// get from env otherwise from GET
	$overrideApiUrl = getenv('UNITDB_OVERRIDE_API');
	if ($overrideApiUrl === false)
		$overrideApiUrl = isset($_GET['overrideApiUrl']) ? $_GET['overrideApiUrl'] : null;

	$downloader = new FileDownloader($debug, $overrideApiUrl);
	$downloader->downloadFiles($_GET['version']);
}

//STEP 1 : UNZIP data

if ($debug)
	echo '<p>STEP 1 ----- </p>'; ////////DEBUG

$unzipper = new Unzipper($debug);
$unzipper->unzipFiles($toExtract);

//loc -->
$failed = 0;
if ($debug)
	echo '<p>-> Opening loc Files... </p>'; ////////DEBUG
foreach ($toExtractLoc as $locArch) {

	$zip = new ZipArchive;

	if ($zip->open('' . ($locArch) . '') === TRUE) {

		if ($debug)
			echo '<p>-> Opened loc archive ' . $locArch . ' and found ' . ($zip->numFiles) . ' files. </p>'; ////////DEBUG

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->statIndex($i)['name'];
			if (strpos($name, '.lua') !== false) {
				$zip->extractTo('data/_temp/' . $locArch . '/', $name);
			}
		}

		$zip->close();
	} else {
		if ($debug)
			echo '<p>-> FAILED opening loc archive ' . $locArch . ' </p>'; ////////DEBUG
		$failed++;
	}
}
if ($failed > 0) {
	if ($debug)
		echo '<p> ->' . $failed . ' loc files could not be extracted. </p>'; ////////DEBUG
}
//endof

//STEP 2 : MERGING FILES
if ($debug)
	echo '<p>------------ </p>'; ////////DEBUG
if ($debug)
	echo '<p>STEP 2 ----- </p>'; ////////DEBUG

$idsUnitsList = [];
$finalLangs = [];
$dir = 'data/_temp/';
if (is_dir($dir)) {
	if ($debug)
		echo '<p>-> Directory ' . $dir . ' found </p>'; ////////DEBUG
	foreach ($toExtract as $fileFolder) { //For every PAK to use, like units.3599.scd or units.nx2
		$realPath = $dir . $fileFolder;
		if ($debug)
			echo '<p>-> Working on ' . $realPath . '</p>'; ////////DEBUG

		$skipping = false;
		if (!is_dir($realPath)) {
			if ($debug)
				echo '<p>--> No directory, SKIPPING </p>'; ////////DEBUG
			continue;
		}
		$dirs = scandirVisible($realPath);
		$thisPakUnitsList = [];
		$totalFound = 0;
		$notFoundAfterX = 0;

		foreach ($dirs as $thisDirectory) { //For every subfolder of the PAK, like "/units" or "/projectiles"

			$unitList = scandirVisible($realPath . '/' . $thisDirectory);
			$thisSubfolderUnitsList = [];
			$units = 0;

			foreach ($unitList as $thisUnit) { // For every unit inside this folder.

				$thisUnitDirectory = $realPath . '/' . $thisDirectory . '/' . $thisUnit;

				$thisMissileFile = $thisUnitDirectory . '/' . strtoupper($thisUnit) . '_PROJ.BP';

				$thisUnit = strtoupper($thisUnit);
				$thisUnitFile = $thisUnitDirectory . '/' . strtoupper($thisUnit) . '_UNIT.BP';

				$proj = false;


				if (file_exists($thisMissileFile)) {
					$proj = true;
					$file = $thisMissileFile;
				} else {
					$file = $thisUnitFile;
				}

				if ($debug)
					echo '--> Adding unit ' . $thisUnit . ' from ' . $file . '...<br>';

				if (file_exists($file)) {
					$blueprint = file_get_contents($file);
					$blueprint = makePhpArray(prepareForConversion($blueprint));
					//var_dump("3");
					$blueprint['Id'] = ($thisUnit);
					// var_dump("4");
					if ($proj) {
						$blueprint['BlueprintType'] = 'ProjectileBlueprint';
					} else {
						$blueprint['BlueprintType'] = 'UnitBlueprint';
					}
					$thisSubfolderUnitsList[$thisUnit] = $blueprint; //Key is ID
					$units++;
				} else {
					if ($debug)
						echo '---> File not found!<br>';
					$notFoundAfterX++;
				}

			}
			if ($debug)
				echo '<p>--> Found ' . $units . ' units in directory ' . $thisDirectory . '</p>'; ////////DEBUG
			if ($debug)
				echo '<p>--> Could not find ' . $notFoundAfterX . ' units</p>'; ////////DEBUG
			$totalFound += $units;
			$thisPakUnitsList = array_merge($thisPakUnitsList, $thisSubfolderUnitsList);
		}

		//$o = $idsUnitsList;
		if ($debug)
			echo '<p>-> Total units found for pak ' . $realPath . ' : ' . $totalFound . ' </p>'; ////////DEBUG
		$idsUnitsList = array_merge($idsUnitsList, $thisPakUnitsList);

	}

	//loc
	$totalLines = 0;
	foreach ($toExtractLoc as $locFolder) {
		$realPath = $dir . $locFolder;

		if ($debug)
			echo '<p>-> Working on loc ' . $realPath . '</p>'; ////////DEBUG

		if (!is_dir($realPath)) {
			if ($debug)
				echo '<p>--> No directory, SKIPPING </p>'; ////////DEBUG
			continue;
		}

		$dirs = scandirVisible($realPath);
		$thisPakLangs = [];

		foreach ($dirs as $thisDirectory) { //For every subfolder of the PAK, like "/units" or "/projectiles"

			$langs = scandirVisible($realPath . '/' . $thisDirectory);
			$thisSubfolderLocList = [];
			$foundLines = 0;

			foreach ($langs as $thisLang) { // For every LANG inside the folder
				$thisLang = strtoupper($thisLang);

				$thisLangDirectory = $realPath . '/' . $thisDirectory . '/' . $thisLang;
				$file = $thisLangDirectory . '/' . 'strings_db.lua';

				if (file_exists($file)) {
					$lines = file_get_contents($file);
					$lines = locfileToPhp($lines);
					$thisSubfolderLocList[$thisLang] = $lines;
					$foundLines++;
					//echo '--> Found lang '.$thisLang.'<br>';
				}

			}
			if ($debug)
				echo '<p>--> Found ' . $foundLines . ' locfiles in directory ' . $thisDirectory . '</p>'; ////////DEBUG
			$totalLines += $foundLines;
			$thisPakLangs = array_merge($thisPakLangs, $thisSubfolderLocList);
		}

		if ($debug)
			echo '<p>-> Total files found for loc ' . $realPath . ' : ' . $totalLines . ' </p>'; ////////DEBUG
		$finalLangs = array_merge($finalLangs, $thisPakLangs);

	}
	//ENDOF
} else {
	if ($debug)
		echo '<p>' . $dir . ' not found. EXITING !</p>'; ////////DEBUG
	exit;
}


//STEP 3 : MAKING JSON
if ($debug)
	echo '<p>------------ </p>'; ////////DEBUG
if ($debug)
	echo '<p>STEP 3 ----- </p>'; ////////DEBUG

$finalUnitList = [];
foreach ($idsUnitsList as $thisUnit) {
	$finalUnitList[] = $thisUnit;
}
file_put_contents('data/blueprints.json', json_encode($finalUnitList));
file_put_contents('data/localization.json', json_encode($finalLangs));

//STEP 4 : CLEANING UP

if ($debug)
	echo '<p>------------ </p>'; ////////DEBUG
if ($debug)
	echo '<p>STEP 4 ----- </p>'; ////////DEBUG

if ($debug)
	echo '<p>-> Beginning ' . $dir . ' cleanup </p>';

if (is_dir($dir)) {
	$files = scandirVisible($dir);
	foreach ($files as $unit) {
		if ($debug)
			echo '<p>-> Removing ' . $dir . $unit . ' </p>'; ////////DEBUG
		rrmdir($dir . $unit);
	}
	;
}

unlink("config/UPDATE.TMP");

if ($debug)
	echo '<p>Unliked UPDATE.TMP - all operations complete.</p>'; ////////DEBUG

if ($debug)
	echo '</div>'; ////////DEBUG


?>

<script>

	function hideUpdateMenu() {
		document.getElementById('updateMenu').style.display = "none";
	}

</script>
<div style="
		z-index:10;
		position:fixed;
		left:50%;
		top:25%;
		margin-left:-300px;
		color:#EEEEEE;
		background-color:#303030;
		border:1px solid white;
		width:600px;
		display:block;" id="updateMenu">
	<div style="font-family:Zeroes;text-align:center;width:100%;margin-top:8px;margin-bottom:16px;">
		Unit database has been updated.
	</div>
	<div class="flexRows" style="width:100%;text-align:center;margin-bottom:32px;">
		<div>
			<button style="
				font-family:Zeroes;
				color:#303030;
				background-color:#EEEEEE;
				width:30%;" onClick="hideUpdateMenu()">OK
			</button>
		</div>
	</div>
</div>