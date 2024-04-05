<?php

// Improved error handling. Detect when commands fail. Exit script on failure.
// Removed use of shell_exec (does not reliably detect command failures).
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
            print_r($out);

            foreach ($sparseFolders as $folder) {
                $escFolder = escapeshellarg($folder);
                $out = $this->execute("cd {$escDestDir} && git sparse-checkout add {$escFolder}");
                print_r($out);
            }
        } else {
            $out = $this->execute("git clone {$escRepo} {$escDestDir}");
            print_r($out);
        }
    }

    private function execute($command)
    {
        $output = null;
        $retval = null;
        logDebug($command);
        exec($command, $output, $retval);
        logDebug("Returned with status $retval and output: \n");
        if ($retval != 0) exit ($retval);

        return $output;
    }
}

?>