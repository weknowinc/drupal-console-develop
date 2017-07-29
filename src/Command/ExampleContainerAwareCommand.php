<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\ExampleContainerAwareCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ExampleContainerAwareCommand
 *
 * @package Drupal\Console\Develop\Command
 */
class ExampleContainerAwareCommand extends ContainerAwareCommand
{

    /**
     * ExampleContainerAwareCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('develop:example:container:aware');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* Register your command as a service
         *
         * Make sure you register your command class at
         * config/services/namespace.yml file and add the `drupal.command` tag.
         *
         * develop_example_container_aware:
         *   class: Drupal\Console\Command\Develop\ExampleContainerAwareCommand
         *   tags:
         *     - { name: drupal.command }
         *
         * NOTE: Make the proper changes on the namespace and class
         *       according your new command.
         *
         * DrupalConsole extends the SymfonyStyle class to provide
         * an standardized Output Formatting Style.
         *
         * Drupal Console provides the DrupalStyle helper class:
         */
        $io = new DrupalStyle($input, $output);
        $io->simple('This text could be translatable by');
        $io->simple('adding a YAML file at "console/translations/LANGUAGE/command.name.yml"');

        /**
         *  By using ContainerAwareCommandTrait on your class for the command
         *  (instead of the more basic CommandTrait), you have access to
         *  the service container.
         *
         *  In other words, you can access to any configured Drupal service
         *  using the provided get method.
         *
         *  $this->get('entity_type.manager');
         *
         *  Reading user input argument
         *  $input->getArgument('ARGUMENT_NAME');
         *
         *  Reading user input option
         *  $input->getOption('OPTION_NAME');
         */
    }
}
