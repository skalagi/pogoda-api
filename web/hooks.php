<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../app/AppKernel.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: '.$_SERVER['HTTP_REFERER']); exit;
}

$pathToProject = __DIR__."/..";
$output = '';

$kernel = new AppKernel('prod', false);

$gitPull = new \Symfony\Component\Process\Process("cd $pathToProject && git pull");
$gitPull->run();
$pullOutput = $gitPull->isSuccessful() ? $gitPull->getOutput() : $gitPull->getErrorOutput();
$output .= 'Git pull: '.$pullOutput.PHP_EOL;

$cacheClear = new \Symfony\Component\Process\Process("cd $pathToProject && php bin/console cache:clear --env=prod");
$cacheClear->run();
$clearOutput = $cacheClear->isSuccessful() ? $cacheClear->getOutput() : $cacheClear->getErrorOutput();
$output .= 'Cache clear: '.$clearOutput.PHP_EOL;

$message = Swift_Message::newInstance('Deployment of weather API complete!')
    ->setFrom($kernel->getContainer()->getParameter('mailer_user'))
    ->setTo($kernel->getContainer()->getParameter('mailer_user'))
    ->setBody(nl2br($output), 'text/html');

$mailer = $kernel->getContainer()->get('mailer');
/** @noinspection PhpParamsInspection */
$mailer->send($message);
if(method_exists($mailer->getTransport(), 'getSpool')) {
    $spool = $mailer->getTransport()->getSpool();
    $transport = $kernel->getContainer()->get('swiftmailer.transport.real');
    if(method_exists($spool, 'flushQueue')) $spool->flushQueue($transport);
}
