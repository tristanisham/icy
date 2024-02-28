<?php declare(strict_types=1);

namespace Tristan\Icy\Cli;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tristan\Icy\DepMap;
use Tristan\Icy\DepMapOutput;

require_once __DIR__ . "/../DepMapOutput.php";
require_once __DIR__ . "/../DepMap.php";

#[AsCommand(
    name: 'sa:gen-import-map',
    description: "generates an import map for all PHP files in a directory.",
    aliases: ['sa:im'],
    hidden: false,
)]
class RunDepMap extends Command
{
    public function __construct()
    {
        parent::__construct();

    }

    protected function configure(): void
    {
        $this->addArgument('filepath', InputArgument::REQUIRED, 'filepath to crawl');
        $this->addOption('phpv', null, InputArgument::OPTIONAL, "Set PHP version (defaults to host)");
        $this->addOption('outputType', 'ot', InputArgument::OPTIONAL, "Set output format", "STDOUT");
        $this->addOption('outFile', 'of', InputArgument::OPTIONAL, "Set's the output filepath (only JSON/PHP)", "importMap.json");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dmap = new DepMap();
        $val = $input->getArgument('filepath');

        $dmap->outFilePath = $input->getOption('outFile');

        $dmap->outputType = match ($input->getOption('outputType')) {
            "json", "JSON" => DepMapOutput::JSON,
            "php", "PHP" => DepMapOutput::PHP,
            default => DepMapOutput::STDOUT
        };

        try {
            if (file_exists($val)) {
                if (is_dir($val)) {
                    $dmap->addRecursiveTargets($val);
                } else {
                    $dmap->addTarget($val);
                }
            }

            $importMap = $dmap->map();
            if ($importMap !== false) {
                //                        print_r($importMap);
                if ($dmap->outputType === DepMapOutput::JSON) {
                    $encoded = json_encode($importMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $fileName = $dmap->outFilePath ?? "importMap.json";
                    file_put_contents($fileName, $encoded);
                    $output->writeln("Saved JSON output in $fileName");
                } else if ($dmap->outputType === DepMapOutput::STDOUT) {
                    $output->write(json_encode($importMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
        } catch (InvalidArgumentException|Exception $err) {
            echo $err->getMessage() . "\n";
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}