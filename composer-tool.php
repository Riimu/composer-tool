<?php

require __DIR__ . '/vendor/autoload.php';

$app = new \Symfony\Component\Console\Application();

$app->add(new \Riimu\ComposerTool\RequireCommand());

exit((int) $app->run());
