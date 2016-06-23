<?php

namespace Riimu\ComposerTool;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * InstallCommand.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RequireCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('require')
            ->setDescription('Adds a new composer tool')
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'Name of the main package to install'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Optional version for the package'
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $package = $input->getArgument('package');

        if (strpos($package, ':') !== false) {
            list($package, $version) = explode(':', $package, 2);
        } else {
            $version = $input->getArgument('version');
        }

        if (!$version) {
            $version = '*';
        }

        $path = $this->getPackagePath($package);

        if (file_exists($path . DIRECTORY_SEPARATOR . 'composer.json')) {
            $this->write("The package '$package' has already been installed");
            return 0;
        } elseif (!file_exists($path)) {
            if (!mkdir($path)) {
                $this->error("Could not create directory '$path'");
                return 1;
            }
        }

        $this->write("Installing '$package' to '$path'");
        $code = $this->runCommand('require', $path, ["$package:$version"]);

        if ($code !== 0) {
            return $code;
        }

        return $this->addPackage($package, $version) ? 0 : 1;
    }

    private function addPackage($package, $version)
    {
        $this->configBase['require'][$package] = $version;
        $json = json_encode($this->configBase, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($this->configPath, $json, LOCK_EX) === false) {
            $this->error("Could not write config to '$this->configPath'");
            return false;
        }

        return true;
    }
}
