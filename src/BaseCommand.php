<?php

namespace Riimu\ComposerTool;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BaseCommand.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class BaseCommand extends Command
{
    /** @var InputInterface Interface for console input */
    private $input;

    /** @var OutputInterface Interface for console output */
    private $output;
    private $config;
    protected $configPath;
    protected $configBase;

    public function configure()
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Path to the configuration file'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->readConfiguration($input);
    }

    protected function write($string, $options = 0)
    {
        $this->output->writeln($string, $options);
    }

    protected function error($string)
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            $this->output->getErrorOutput()->writeln($string);
        } else {
            $this->output->writeln($string);
        }
    }

    protected function debug($string)
    {
        $this->write($string, OutputInterface::VERBOSITY_DEBUG);
    }

    protected function readConfiguration(InputInterface $input)
    {
        $path = $input->getOption('config');

        if (!$path) {
            if (file_exists('composer-tool.json')) {
                $path = 'composer-tool.json';
            } elseif (file_exists(__DIR__ . '/../../../../vendor')) {
                $path = __DIR__ . '/../../../../composer-tool.json';
            } else {
                $path = 'composer-tool.json';
            }
        }

        $this->configBase = [];
        $this->config = [
            'bin-dir' => 'vendor/bin',
            'lib-dir' => 'tools',
            'composer-path' => 'composer',
        ];

        if (file_exists($path)) {
            $path = realpath($path);
            $config = json_decode(file_get_contents($path), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Could not decode '$path'");
            }

            $this->configBase = $config;
            $this->config = $config + $this->config;
        } else {
            if (!file_exists(dirname($path))) {
                throw new \RuntimeException("Invalid configuration path '$path'");
            }

            $path = realpath(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
        }

        $this->debug("Using configuration path '$path'");

        $this->configPath = $path;

        $current = getcwd();
        chdir(dirname($path));

        $this->resolvePath('bin-dir');
        $this->resolvePath('lib-dir');

        chdir($current);
    }

    private function resolvePath($name)
    {
        $path = $this->config[$name];

        if (!file_exists($path)) {
            if (!mkdir($path)) {
                throw new \RuntimeException("Could not create directory '$path'");
            }
        }

        $this->config[$name] = realpath($path);
    }

    protected function getPackagePath($package)
    {
        if (!strpos('/', $package) === false) {
            throw new \InvalidArgumentException("Invalid package name '$package'");
        }

        $parts = explode('/', $package, 2);

        return $this->config['lib-dir'] . DIRECTORY_SEPARATOR . end($parts);
    }

    protected function runCommand($command, $workingDirectory, array $arguments)
    {
        $command = sprintf(
            '%s %s -d %s %s',
            $this->config['composer-path'],
            escapeshellarg($command),
            escapeshellarg($workingDirectory),
            implode(' ', array_map('escapeshellarg', $arguments))
        );

        $this->debug("Running command '$command'");
        putenv('COMPOSER_BIN_DIR=' . $this->config['bin-dir']);
        passthru($command, $return);

        return $return;
    }

    protected function getPackages()
    {
        if (!file_exists($this->config['lib-dir'])) {
            return [];
        }

        $packages = [];

        foreach (new \DirectoryIterator($this->config['lib-dir']) as $dir) {
            if (!$dir->isDir() || $dir->isDot()) {
                continue;
            }

            $composer = $dir->getPathname() . DIRECTORY_SEPARATOR . 'composer.json';

            if (file_exists($composer)) {
                $package = $this->parsePackage($composer, $dir->getFilename());

                if ($package) {
                    $packages[] = $package;
                }
            }
        }

        return $packages;
    }

    private function parsePackage($file, $name)
    {
        $json = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Could not parse file '$file'");
            return false;
        }

        if (empty($json['require'])) {
            return false;
        }

        foreach ($json['require'] as $package => $version) {
            $parts = explode('/', $package);

            if (strcasecmp($name, end($parts)) === 0) {
                return $package;
            }
        }

        return false;
    }
}
