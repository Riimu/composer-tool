<?php

namespace Riimu\ComposerTool;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ShowCommand.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2016, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ShowCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('show')
            ->setDescription('Shows information about installed composer tools')
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                'Optional package to show'
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $package = $input->getArgument('package');
        $packages = $package ? [$package] : $this->getPackages();
        $result = 0;

        foreach ($packages as $package) {
            $path = $this->getPackagePath($package);

            if (file_exists($path . DIRECTORY_SEPARATOR . 'composer.json')) {
                 $code = $this->runCommand('show', $path, ['-D']);

                if ($code !== 0) {
                    $result = 1;
                }
            }
        }

        return $result;
    }
}
