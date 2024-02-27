<?php declare(strict_types=1);

namespace Ham\Icy;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

require_once("vendor/autoload.php");

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

enum DepMapOutput
{
    case JSON;
    case PHP;
}

/**
 * @var array<string> $targets ;
 */
final class DepMap
{
    private array $targets;
    public DepMapOutput|null $outputType;

    public PhpVersion|null $phpVersion;

    public function __construct()
    {
        $this->targets = [];
        $this->outputType = null;
        $this->phpVersion = null;
    }

    /**
     * @param string $path must be a directory.
     * @throws \InvalidArgumentException if $path is not a directory.
     */
    public function addRecursiveTargets(string $path): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("{$path} is not a directory");
        }

        foreach (new \RecursiveDirectoryIterator($path) as $file) {
            if ($file->getExtension() !== "php") {
                continue;
            }

            $absPath = $file->getRealPath();
            if (is_string($absPath)) {
                $this->targets[] = $absPath;
            }
        }
    }

    /**
     * @param string $path must be a file.
     * @throws \InvalidArgumentException if $path is not a file.
     */
    public function addTarget(string $path): void
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("{$path} must be an existing file");
        }

        $absPath = realpath($path);
        if (is_string($absPath)) {
            $this->targets[] = $absPath;
        }
    }

    /**
     * @throws \Exception
     */
    public function map(): void
    {
        $parser = match ($this->phpVersion) {
            null => (new ParserFactory())->createForNewestSupportedVersion(),
            default => (new ParserFactory())->createForVersion($this->phpVersion),
        };

        if ($this->outputType === null) {
            $this->outputType = DepMapOutput::JSON;
        }


        foreach ($this->targets as $path) {

            try {
                $data = file_get_contents($path);
                if (!$data) {
                    throw new \Exception("Cannot read contents of {$path}");
                }

                $ast = $parser->parse($data);
                if ($ast === null) {
                    throw new \Exception("Unable to parse the AST of{$path}");
                }

                if ($this->outputType === DepMapOutput::JSON) {
                    $encoding = json_encode($ast, JSON_PRETTY_PRINT);
                    // TODO: filter our imports, and append to import map with current $path as key.
                    echo $encoding;
                }

            } catch (Error $error) {
                echo "Parse error: {$error->getMessage()}\n";
                return;
            }
        }


    }

    /**
     * @param PhpVersion|null $version
     * @return void
     */
    public function setPHPVersion(PhpVersion|null $version): void
    {
        $this->phpVersion = $version;
    }
}