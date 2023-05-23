<?php

class Unzipper {
    private $debug;

    public function __construct($debug) {
        $this->debug = $debug;
    }

    public function unzipFiles($toExtract) {
        $this->log('STEP 1 -----');

        $failed = 0;
        for ($h = 0; $h < sizeOf($toExtract); $h++) {
            $failed += $this->unzipFile($toExtract[$h]);
        }

        if ($failed > 0) {
            $this->log(" -> {$failed} files could not be extracted.");
        }
    }

    private function log($message) {
        if ($this->debug) {
            echo "<p>{$message}</p>";
        }
    }
    
    private function unzipFile($fileName) {
        $zip = new ZipArchive;
        if ($zip->open($fileName) !== TRUE) {
            $this->log("-> FAILED opening archive {$fileName}");
            return 1;
        }

        $this->log("-> Opened archive {$fileName} and found {$zip->numFiles} files.");

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->statIndex($i)['name'];
            $this->log("--> Found file {$name}");

            if (strpos(basename($name), '.bp') !== false) {
                $this->extractFile($zip, $fileName, $name);
            }
        }

        $zip->close();

        return 0;
    }

    private function extractFile($zip, $fileName, $name) {
        $this->log("---> Extracting {$name} to data/_temp/{$fileName}/ ...");
        $success = $zip->extractTo("data/_temp/{$fileName}/", $name);

        // Let's assure the unit blueprint has the right casing
        $basename = basename("data/_temp/{$fileName}/{$name}");
        $path = str_replace($basename, "", "data/_temp/{$fileName}/{$name}");
        $this->log("---> Renaming {$path}{$basename} to {$path}" . strtoupper($basename));
        rename("{$path}{$basename}", "{$path}" . strtoupper($basename));

        if (!$success) {
            $this->log("----> Extraction FAILED !");
            $this->log("----> Error : " . error_get_last()['message']);
        }
    }
}

?>