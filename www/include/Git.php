<?php

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
            $this->execute("git clone --filter=blob:none --depth 1 --sparse {$escRepo} --branch {$escBranch} {$escDestDir}");

            foreach ($sparseFolders as $folder) {
                $escFolder = escapeshellarg($folder);
                $this->execute("cd {$escDestDir} && git sparse-checkout add {$escFolder}");
            }
        } else {
            $this->execute("git clone {$escRepo} {$escDestDir}");
        }
    }

    private function execute($command)
    {
        $output = shell_exec($command);
        return $output;
    }
}

?>