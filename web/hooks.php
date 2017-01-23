<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../app/AppKernel.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: http://'.$_SERVER['SERVER_NAME']); exit;
}
$redis = new \Redis();
$redis->connect('127.0.0.1');
$redis->set('_weather_api_deploy_running', 'true');

$pathToProject = __DIR__."/..";
$output = '';

$kernel = new AppKernel('prod', false);
$kernel->boot();

$gitPull = new \Symfony\Component\Process\Process("cd $pathToProject && git pull");
$gitPull->run();
$pullOutput = $gitPull->isSuccessful() ? $gitPull->getOutput() : $gitPull->getErrorOutput();
$output .= 'GIT PULL: '.PHP_EOL.$pullOutput.PHP_EOL;

$cacheClear = new \Symfony\Component\Process\Process("cd $pathToProject && php bin/console cache:clear --env=prod");
$cacheClear->run();
$clearOutput = $cacheClear->isSuccessful() ? $cacheClear->getOutput() : $cacheClear->getErrorOutput();
$output .= 'CACHE CLEAR: '.$clearOutput.PHP_EOL;

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
$kernel->shutdown();
$redis->set('_weather_api_deploy_running', (new \DateTime)->format('d.m.y H:i:s'));