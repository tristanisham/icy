<?php declare(strict_types=1);
use PhpParser\PhpVersion;

require_once("lib/lib.php");

if (php_sapi_name() === "cli") {
    for ($i = 0; $i < $argc; $i++) {
        $input = $argv[$i];
        $dmap = new Icy\DepMap();

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
            default:
                try {
                    if (file_exists($input)) {
                        if (is_dir($input)) {
                            $dmap->addRecursiveTargets($input);
                        } else {
                            $dmap->addTarget($input);
                        }
                    }

                    $dmap->map();
                } catch (InvalidArgumentException $err) {
                    echo $err->getMessage() . "\n";
                    die(1);
                }

                break;

        }
    }
}