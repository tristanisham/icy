#!/usr/bin/env php
<?php declare(strict_types=1);

use PhpParser\PhpVersion;

require_once("DepMap.php");

if (php_sapi_name() === "cli") {
    for ($i = 0; $i < $argc; $i++) {
        $input = $argv[$i];
        $dmap = new Ham\Icy\DepMap();

        switch ($input) {
            case "version":
            case "--version":
            case "-v":
                echo "v0.0.1" . "\n";
                break;

            case "--phpv":
                if (count($argv) > $i + 1) {
                    $dmap->setPHPVersion(PhpVersion::fromString($argv[$i + 1]));
                } else {
                    $dmap->setPHPVersion(null);
                }
                break;
            case "--outfile":
                if (count($argv) > $i + 1) {
                    $dmap->outFilePath = $argv[$i + 1];
                }
                break;
            default:
                try {
                    if (file_exists($input)) {
                        if (is_dir($input)) {
                            $dmap->addRecursiveTargets($input);
                        } else {
                            $dmap->addTarget($input);
                        }
                    }

                    $importMap = $dmap->map();
                    if ($dmap->outputType === \Ham\Icy\DepMapOutput::JSON) {
                        $encoded = json_encode($importMap, JSON_PRETTY_PRINT);
                        file_put_contents($dmap->outFilePath ?? "importMap.json", $encoded);
                    }
                } catch (InvalidArgumentException|Exception $err) {
                    echo $err->getMessage() . "\n";
                    die(1);
                }

                break;

        }
    }
}