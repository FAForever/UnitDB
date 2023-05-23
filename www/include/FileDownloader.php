<?php

class FileDownloader
{
	// set default api url as constant
	const DEFAULT_API_URL = "https://api.faforever.com";
	private $overrideApiUrl;
	private $debug;

	public function __construct($debug = false, $overrideApiUrl = null)
	{
		$this->overrideApiUrl = $overrideApiUrl;
		$this->debug = $debug;
	}

	public function downloadFiles($version)
	{
		if ($version == "local")
			return;

		$url = $this->getUrl($version);

		if ($this->debug) {
			echo "<p>Using url " . $url . "</p>";
		}
		$neededFiles = [
			"units.nx2" => "data/gamedata/",
			"projectiles.nx2" => "data/gamedata/",
			"loc.nx2" => "data/loc/"
		];
		$files = $this->getFileData($url);
		foreach ($files as $thisFile) {
			$name = $thisFile["attributes"]["name"];
			if (!array_key_exists($name, $neededFiles)) {
				continue;
			}
			$path = $neededFiles[$name];
			$this->downloadFile($thisFile, $path);
			$this->checkFileMD5($thisFile, $path);
		}

	}

	private function getUrl($version)
	{
		$defaultApiUrl = self::DEFAULT_API_URL;
		echo "Default API URL: $defaultApiUrl<br>";
		$apiUrl = $this->overrideApiUrl ? rtrim($this->overrideApiUrl, '/') : $defaultApiUrl;
		$apiUrl .= "/featuredMods/0/files/%s";

		return sprintf($apiUrl, $version);
	}

	private function getFileData($url)
	{
		$jsonString = file_get_contents($url);
		return json_decode($jsonString, true)["data"];
	}

	private function downloadFile($thisFile, $path)
	{
		$name = $thisFile["attributes"]["name"];
		$md5 = $thisFile["attributes"]["md5"];
		$url = $thisFile["attributes"]["url"];
		if ($this->overrideApiUrl !== null) {
			$url = str_replace('https://api.faforever.com', $this->overrideApiUrl, $url);
		}
		if ($this->debug) {
			echo "Downloading " . $name . " from " . $url . " to " . $path . " [" . $md5 . "]<br>";
		}
		if (file_exists($path . $name)) {
			unlink($path . $name);
		}
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		file_put_contents($path . $name, fopen($url, 'r'));
	}

	private function checkFileMD5($thisFile, $path)
	{
		$name = $thisFile["attributes"]["name"];
		$md5 = $thisFile["attributes"]["md5"];
		$sum = md5_file($path . $name);
		if ($sum != $md5) {
			if ($this->debug) {
				echo "-> MD5 MISMATCH !<br>";
				echo "--> Exiting.<br>";
			}
			exit;
		} else {
			if ($this->debug) {
				echo "=> MD5 OK !<br>";
			}
		}
	}
}

?>