<?php

namespace Riimu\ComposerTool;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpdateCommand.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UpdateCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('update')
            ->setDescription('Updates composer tools')
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'The package to update'
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $package = $input->getArgument('package');

        if ($package) {
            $packages = [$package];
        } else {
            $packages = $this->getPackages();
        }

        $result = 0;

        foreach ($packages as $package) {
            $path = $this->getPackagePath($package);

            if (file_exists($path . DIRECTORY_SEPARATOR . 'composer.json')) {
                $this->write("Updating package '$package'");
                $code = $this->runCommand('update', $path, []);

                if ($code !== 0) {
                    $result = 1;
                }
            }
        }

        return $result;
    }
}
