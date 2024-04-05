<?php
require(__DIR__ . '/include/Git.php');
require(__DIR__ . '/res/scripts/luaToPhp.php');

$debug = isDebug();
setErrorHandling($debug);
verifySecret('UNITDB_UPGRADE_SECRET');

function getFromGET($key, $default = null) {
  return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function isDebug() {
return getFromGET('debug') || getenv('UNITDB_DEBUG');
}

function logDebug($msg) {
if (isDebug())
  echo('<div style="color:orange;background-color:#111111;font-family:Consolas;padding:8px;">'.$msg.'</div>');
}

function setErrorHandling($debug) {
  if ($debug) {
      error_reporting(E_ALL);
      ini_set('display_errors', 1);
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


function copyFolder($sourceFolder, $destinationFolder)
{
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $sourceFolder = str_replace('/', '\\', $sourceFolder);
    $destinationFolder = str_replace('/', '\\', $destinationFolder);

    $command = 'xcopy /E /Y /I "' . $sourceFolder . '" "' . $destinationFolder . '"';
  } else {
    // Linux or other Unix-based systems
    $command = 'mkdir -p "'.$destinationFolder.'" && cp -r -f "' . $sourceFolder . '" "' . $destinationFolder . '"';
  }

  // Execute the command
  exec($command, $output, $returnStatus);

  // Check the return status to see if the command executed successfully
  if ($returnStatus === 0) {
    logDebug("Copy '$sourceFolder' to '$destinationFolder' succeeded.\n");
  } else {
    logDebug("Failed to copy '$sourceFolder' to '$destinationFolder'.\n");
  }
}

function deleteFolder($folder)
{
    if (!is_dir($folder)) return;
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = 'rmdir /S /Q "' . $folder . '"';
    } else {
        // Linux or other Unix-based systems
        $command = 'rm -rf "' . $folder . '"';
    }

    // Execute the command
    exec($command, $output, $returnStatus);

    // Check the return status to see if the command executed successfully
    if ($returnStatus === 0) {
        logDebug("Deleted folder '$folder' succeeded.\n");
    } else {
        logDebug("Failed to delete folder '$folder'.\n");
    }
}

function unzipFiles($zipFile, $destinationFolder)
{
    $zip = new ZipArchive;

    // Open the ZIP archive
    if ($zip->open($zipFile) === true) {
        logDebug("Opened $zipFile successfully.\n");
        // Extract all files to the destination folder
        $zip->extractTo($destinationFolder);
        
        // Close the ZIP archive
        $zip->close();
        
        logDebug("$zipFile extracted successfully to $destinationFolder.\n");
    } else {
        logDebug("Failed to open $zipFile");
    }
}

function getFiles(string $directory): array
{
    $files = array_diff(scandir($directory), ['.', '..']);
    $allFiles = [];

    foreach ($files as $file) {
        $fullPath = $directory. DIRECTORY_SEPARATOR .$file;
        is_dir($fullPath) ? array_push($allFiles, ...getFiles($fullPath)) : array_push($allFiles, $file);
    }

    return $allFiles;
}

function path(...$parts) {
  // Remove leading and trailing slashes from each part before joining them.
  $parts = array_map(function($part) {
      return trim($part, '/\\');
  }, $parts);

  // Join all the parts using the DIRECTORY_SEPARATOR constant
  $path = implode(DIRECTORY_SEPARATOR, $parts);

  // Makes sure that the path is correctly formatted for the current OS.
  if (DIRECTORY_SEPARATOR === '\\') {
      $path = str_replace('/', '\\', $path);
  } else {
      $path = str_replace('\\', '/', $path);
  }

  return $path;
}

function prepareForConversion($string_bp)
{
	$string_bp = preg_replace('/--(.*)/', "", $string_bp);
	$string_bp = preg_replace('/#(.*)/', "", $string_bp);
	$string_bp = str_replace("'", '"', $string_bp);
	$string_bp = str_replace('Sound', '', $string_bp);

/// Deals with values enclosed by {} being on the same line. MakePHPArray() will not properly convert such lines.
/// Example: Intel = { VisionRadius = 32 },

///    After trying a million different syntaxes in a PHP Sandbox, this seems to work! Using the
///    double quotes is key to getting the newlines in there. - PV
///    Breaks up any line that has { } on the same line with a property
       $string_bp = preg_replace("/{(.*)}\,/", "{\n$1\n},", $string_bp);

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

$version = getFromGET('version');
if (!$version)
  $version = "master";

$repoUrl = "https://github.com/FAForever/fa";

// Changed to .tmp to tmp for better Windows compatibility
$repoDir = path("tmp/fa");
$dataFolder = path("tmp/data");

$gamedataFolder = path($dataFolder, 'gamedata');
$locFolder = path($dataFolder, 'loc');
deleteFolder($locFolder);
deleteFolder($dataFolder);

/* Start of github dependent section */
// Normal operation is to pull files from GitHub using git commands.
// If git commands don't work in your environment, or for testing purposes, comment out the next 3 lines.
// You can then manually place & unzip units.nx2, projectiles.nx2, loc.nx2 into tmp/fa ($repoDir)
deleteFolder($repoDir);
$git = new Git($repoUrl);
$git->clone($repoDir, $version);
/* End of github dependent section */

copyFolder($repoDir . "/projectiles", path($dataFolder, 'projectiles'));
copyFolder($repoDir . "/units", path($dataFolder, 'units'));
copyFolder($repoDir . "/loc", path($locFolder, 'loc', 'loc'));
unzipFiles("data/gamedata/projectiles.scd.3599", path($dataFolder, 'projectiles.3599'));
unzipFiles("data/gamedata/units.scd.3599", path($dataFolder, 'units.3599'));
unzipFiles("data/loc/loc_US.scd.3599", path($locFolder, 'loc_US.3599'));
unzipFiles("data/loc/loc.nx2", path($locFolder, 'loc.nx2'));

$folders = [ 'projectiles.3599', 'units.3599', 'projectiles', 'units', 'loc\\loc_US.3599', 'loc\\loc.nx2'];
$valid_types = [ 'unit.bp', 'proj.bp', 'db.lua' ];

$blueprints = array();
$bpFiles = array();
$locFiles = array();

// It is >critical< that the folders are read in the order specified by the $folders variable.
// The .3599 folders must be read first, otherwise old values will appear in the results.

foreach ($folders as $thisFolder) {
	$thisPath = $dataFolder."\\".$thisFolder;

	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($thisPath));
	foreach ($iterator as $file) {
	  if ($file->isDir()) continue;
	  $filename = $file->getFilename();
	  if ($filename == '.' || $filename == '..') continue;
	  if (strpos($filename, '_') == false) continue;
	  list($blueprintId, $type) = explode('_', $filename);
	  if (!in_array($type, $valid_types)) continue;

	  $path = $file->getPathname();

	  if ($type == 'db.lua') {
	    // get parent folder of file
	    $parentFolder = basename(dirname($path));
	    $lang = strtoupper($parentFolder);
	    $locFiles[$lang] = $path;
	  }
	  else {
	    $bpFiles[$blueprintId] = $path;
	  }
	}
}

$blueprint = array();
foreach ($bpFiles as $blueprintId => $path) {
  // get filename from path
  $filename = basename($path);
  list($blueprintId, $type) = explode('_', $filename);

  $fileContent = file_get_contents($path);
  $blueprint = makePhpArray(prepareForConversion($fileContent));
  $blueprint['Id'] = $blueprintId;
  $blueprint['BlueprintType'] = $type == 'unit.bp' ? 'UnitBlueprint' : 'ProjectileBlueprint';
  $blueprints[] = $blueprint;
}

$localizations = array();
foreach ($locFiles as $lang => $path) {
  // get filename from path
  $loc = locfileToPhp(file_get_contents($path));
  $localizations[$lang] = $loc;
}

logDebug("Writing data/blueprints.json");
file_put_contents('data/blueprints.json', json_encode($blueprints));
logDebug("Writing data/localization.json");
file_put_contents('data/localization.json', json_encode($localizations));

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
