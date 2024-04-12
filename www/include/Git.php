<?php

// Wrapper for Git, exits on git failures.
class Git
{
    private $repositoryUrl;

    public function __construct($repositoryUrl)
    {
        $this->repositoryUrl = $repositoryUrl;
    }

    public function clone($destinationDirectory, $branch = 'master', $sparseFolders = ['loc', 'projectiles', 'units'])
    {
        // Escape arguments to ensure they're safe for shell execution
        $escRepo = escapeshellarg($this->repositoryUrl);
        $escDestDir = escapeshellarg($destinationDirectory);
        $escBranch = escapeshellarg($branch);

        if ($sparseFolders) {
            $out = $this->execute("git clone --filter=blob:none --depth 1 --sparse {$escRepo} --branch {$escBranch} {$escDestDir}");

            foreach ($sparseFolders as $folder) {
                $escFolder = escapeshellarg($folder);
                $out = $this->execute("cd {$escDestDir} && git sparse-checkout add {$escFolder}");
            }
        } else {
            $out = $this->execute("git clone {$escRepo} {$escDestDir}");
        }
    }

    private function execute($command)
    {
        $output = null;
        $retval = null;
        logDebug($command);
        exec($command, $output, $retval);

        if ($retval != 0) {
            logDebug("Returned with status $retval and output: \n");
            print_r($output);
            exit ($retval);
        }

        return $output;
    }
}

?>