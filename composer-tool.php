<?php

require __DIR__ . '/vendor/autoload.php';

$app = new \Symfony\Component\Console\Application();

$app->add(new \Riimu\ComposerTool\RequireCommand());
$app->add(new \Riimu\ComposerTool\UpdateCommand());

exit((int) $app->run());
