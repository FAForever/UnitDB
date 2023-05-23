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
        if ($sparseFolders) {
            $this->execute("git clone --filter=blob:none --depth 1 --sparse {$this->repositoryUrl} --branch $branch {$destinationDirectory}");

            foreach ($sparseFolders as $folder){
                $this->execute("cd $destinationDirectory && git sparse-checkout add $folder");
            }
        }
        else
            $this->execute("git clone {$this->repositoryUrl} {$destinationDirectory}");
    }

    private function execute($command)
    {
        $output = shell_exec($command);

        return $output;
    }
}
?>