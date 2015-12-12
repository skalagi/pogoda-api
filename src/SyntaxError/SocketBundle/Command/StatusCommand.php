<?php

namespace SyntaxError\SocketBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyntaxError\SocketBundle\Server\Config;

class StatusCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ws:status')
            ->setDescription('Status of the webSocket server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::create();
        $output->writeln("<fg=blue>** Status **</>");

        $output->writeln("<info>Bind on address: </info><comment>".$config['bind']."</comment>");
        $output->writeln("<info>Listen on port: </info><comment>".$config['port']."</comment>");
        $output->writeln("<info>Logged to file: </info><comment>".$config['log']."</comment>");
        $output->writeln("<info>Task class is: </info><comment>".$config['task']."</comment>");

        $pid = Config::getPid();
        $starStop = is_numeric($pid) ? "<fg=blue>STARTED ($pid)</>" : "<fg=red>STOPPED</>";
        $output->writeln("<info>Server: </info>$starStop");
    }
}
