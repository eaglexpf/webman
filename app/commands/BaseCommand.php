<?php

namespace app\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BaseCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function handle(InputInterface $input)
    {
        throw new \Exception('The handle method must exist!');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        return $this->handle($input) ? Command::SUCCESS : Command::FAILURE;
    }
}