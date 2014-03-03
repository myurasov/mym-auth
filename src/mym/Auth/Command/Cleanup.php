<?php

/**
 * Installation
 * @copyright 2014 Mikhail Yurasov <me@yurasov.me>
 */

namespace mym\Auth\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Command
{
  protected function configure()
  {
    $this
      ->setName('mym:auth-service:cleanup')
      ->setDescription('Cleanup AuthService database');
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $service = $this->getHelper('mymAuthService')->getAuthService();
    $service->cleanup();
    $output->writeln('AuthService database cleaned');
  }
}