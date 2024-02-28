<?php declare(strict_types=1);

namespace Tristan\Icy;

use Exception;
use InvalidArgumentException;


require_once ICY_COMPOSER_INSTALL;

require_once(__DIR__ . "/DepMapOutput.php");

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use RecursiveDirectoryIterator;

/**
 * @var array<string> $targets ;
 * @var array<string, array<string>> $importMap ;
 */
final class DepMap
{
    private array $targets;
    public DepMapOutput $outputType;
    public array $importMap;
    public string $outFilePath;

    public PhpVersion|null $phpVersion;

    public function __construct()
    {
        $this->targets = [];
        $this->importMap = [];
        $this->outputType = DepMapOutput::STDOUT;
        $this->phpVersion = null;
    }

    /**
     * @param string $path must be a directory.
     * @throws InvalidArgumentException if $path is not a directory.
     */
    public function addRecursiveTargets(string $path): void
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("$path is not a directory");
        }

        foreach (new RecursiveDirectoryIterator($path) as $file) {
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
     * @throws InvalidArgumentException if $path is not a file.
     */
    public function addTarget(string $path): void
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("$path must be an existing file");
        }

        $absPath = realpath($path);
        if (is_string($absPath)) {
            $this->targets[] = $absPath;
        }
    }

    /**
     * @throws Exception
     */
    public function map(): array|false
    {
        $parser = match ($this->phpVersion) {
            null => (new ParserFactory())->createForHostVersion(),
            default => (new ParserFactory())->createForVersion($this->phpVersion),
        };


        foreach ($this->targets as $path) {
//            echo $path . PHP_EOL;
            try {
                $data = file_get_contents($path);
                if (!$data) {
                    throw new Exception("Cannot read contents of $path");
                }

                $ast = $parser->parse($data);
                if ($ast === null) {
                    throw new Exception("Unable to parse the AST of $path");
                }

                $encoding = json_encode($ast, JSON_THROW_ON_ERROR);
                // TODO: filter our imports, and append to import map with current $path as key.
                $decoded = json_decode($encoding, true, flags: JSON_THROW_ON_ERROR);


                self::recursiveArrayIter($decoded, function ($array) use ($path) {
                    if (isset($array['nodeType']) && $array['nodeType'] === 'Expr_Include') {
//                        echo "Index: {$index} Path: {$path}" . PHP_EOL;
//                        print_r($array);
                        if (isset($array['expr']) && is_array($array['expr'])) {
//                            print_r($array);
//                            echo "=============" . PHP_EOL;
                            $expr = $array['expr'];

                            if ($expr['nodeType'] === "Expr_BinaryOp_Concat") {

                                $left = $expr['left'];
                                $right = $expr['right'];
                                if ($left['nodeType'] === "Scalar_MagicConst_Dir" && $right['nodeType'] === 'Scalar_String') {
                                    $parentDir = dirname(realpath($path));
                                    $merge = join(DIRECTORY_SEPARATOR, [$parentDir, $right['value']]);
                                    $this->importMap[realpath($path) ?: $path][] = realpath($merge) ?: $merge;

                                }
                            } else if ($expr['nodeType'] === 'Scalar_String') {
                                $this->importMap[realpath($path) ?: $path][] = realpath($expr['value']) ?: $expr['value'];

                            }

                        }

                    }
                });

            } catch (Error $error) {
                echo "Parse error: {$error->getMessage()}\n";
                return false;
            }
        }

        foreach ($this->importMap as $key => $val) {
            if (is_array($this->importMap[$key])) {
                $this->importMap[$key] = array_unique($this->importMap[$key]);
//                echo "Cleaned {$key}" . PHP_EOL;
            }
        }

        return $this->importMap;

    }

    /**
     * @param PhpVersion|null $version
     * @return void
     */
    public function setPHPVersion(PhpVersion|null $version): void
    {
        $this->phpVersion = $version;
    }

    private static function recursiveArrayIter(array &$array, callable $callback): void
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                self::recursiveArrayIter($value, $callback);
            } else {
                $callback($array);
            }
        }
    }
}