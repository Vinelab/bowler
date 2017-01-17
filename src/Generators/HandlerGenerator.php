<?php

namespace Vinelab\Bowler\Generators;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class HandlerGenerator
{
    protected $srcDirectoryName = 'src';

    /**
     * Generate App\Messaging\queues.php and App\Messaging\Handlers\*Handler.php.
     */
    public function generate($queue, $handler, $type)
    {
        $queuePath = $this->findQueuePath();

        $handlerPath = $this->findHandlerPath();

        $handlerNamespace = $this->findHandlerNamespace();

        $this->registerHandler($queue, $handler, $queuePath, $handlerNamespace, $type);

        $this->generateHandler($handler, $handlerPath, $handlerNamespace);
    }

    private function registerHandler($queue, $handler, $queuePath, $handlerNamespace, $type)
    {
        // Get queue stub content and replace variables with values
        $queueContent = file_get_contents($this->getQueueStub());
        $queueContent = str_replace(['{{type}}', '{{queue}}', '{{handler}}'], [$type, "'".$queue."'", "'".$handlerNamespace.'\\'.$handler."'"], $queueContent);

        // Remove `<?php` string if file already exist
        if (file_exists($queuePath)) {
            $queueContent = str_replace('<?php', '', $queueContent);
        }

        // Create or Append to file if it doesn't exist
        file_put_contents($queuePath, $queueContent, FILE_APPEND);
    }

    private function generateHandler($handler, $handlerPath, $handlerNamespace)
    {
        // Get handler stub content and replace variables with values
        $handlerContent = file_get_contents($this->getHandlerStub());
        $handlerContent = str_replace(['{{handler}}', '{{namespace}}'], [$handler, $handlerNamespace], $handlerContent);

        // Create Handlers directory if it doesn't exist
        if (!is_dir($handlerPath)) {
            mkdir($handlerPath, 0777, true);
        }

        // Create Handler
        file_put_contents($handlerPath.$handler.'.php', $handlerContent);
    }

    /**
     * Find queue absolute path.
     */
    private function findQueuePath()
    {
        return app_path().'/Messaging/queues.php';
    }

    /**
     * Find handler absolute path.
     */
    private function findHandlerPath()
    {
        return app_path().'/Messaging/Handlers/';
    }

    /**
     * Find handler relative path.
     */
    private function findHandlerNamespace()
    {
        $rootNamespace = $this->findRootNamespace();

        return $rootNamespace.'\Messaging\Handlers';
    }

    /**
     * Find queue stub absolute path.
     */
    private function getQueueStub()
    {
        return __DIR__.'/stubs/queue.stub';
    }

    /**
     * Find handler stub absolute path.
     */
    private function getHandlerStub()
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
    private function findRootNamespace()
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
    private function getSourceDirectoryName()
    {
        if (file_exists(base_path().'/'.$this->srcDirectoryName)) {
            return $this->srcDirectoryName;
        }

        return 'app';
    }
}
