<?php

namespace Vinelab\Bowler\Generators;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class QueueGenerator
{
    protected $srcDirectoryName = 'src';

    public function generate($queue, $handler)
    {
        $queuePath = $this->findQueuePath();

        $handlerPath = $this->findHandlerPath();

        $handlerNamespace = $this->findHandlerNamespace();

        $queueContent = file_get_contents($this->getQueueStub());
        $queueContent = str_replace(['{{queue}}', '{{handler}}'], ["'".$queue."'", "'".$handlerNamespace.'\\'.$handler."'"], $queueContent);

        $handlerContent = file_get_contents($this->getHandlerStub());
        $handlerContent = str_replace(['{{handler}}', '{{namespace}}'], [$handler, $handlerNamespace], $handlerContent);

        // Create Handlers directory if it doesn't exist
        if(!is_dir($handlerPath)) {
            mkdir($handlerPath, 0777, true);
        }

        // Remove <?php string if file already exist
        if(file_exists($queuePath)) {
            $queueContent = str_replace('<?php', '',$queueContent);
        }

        // Create or Append to file if it doesn't exist
        file_put_contents($queuePath, $queueContent, FILE_APPEND);

        // Create Handler
        file_put_contents($handlerPath.$handler.'.php', $handlerContent);
    }

    public function findQueuePath()
    {
        return app_path().'/Messaging/queues.php';
    }

    public function findHandlerPath()
    {
        return app_path().'/Messaging/Handlers/';
    }

    public function findHandlerNamespace()
    {
        $rootNamespace = $this->findRootNamespace();

        return $rootNamespace.'\Messaging\Handlers';
    }

    public function getQueueStub()
    {
        return __DIR__.'/stubs/queue.stub';
    }

    public function getHandlerStub()
    {
        return __DIR__.'/stubs/handler.stub';
    }

    /**
     * Get the namespace used for the application.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function findRootNamespace()
    {
        // read composer.json file contents to determine the namespace
        $composer = json_decode(file_get_contents(base_path().'/composer.json'), true);
        // see which one refers to the "src/" directory
        foreach ($composer['autoload']['psr-4'] as $namespace => $directory) {
            if ($directory === $this->getSourceDirectoryName().'/') {
                return trim($namespace, '\\');
            }
        }

        throw new Exception('App namespace not set in composer.json');
    }


    /**
     * Get the source directory name.
     * In a microservice installation this will be `app`. `src` otherwise.
     *
     * @return string
     */
    public function getSourceDirectoryName()
    {
        if (file_exists(base_path().'/'.$this->srcDirectoryName)) {
            return $this->srcDirectoryName;
        }

        return 'app';
    }
}