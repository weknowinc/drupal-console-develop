<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\DevelopDocDataCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class DevelopDocDataCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class DevelopDocDataCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('develop:doc:data')
            ->setDescription(
                $this->trans('commands.develop.doc.data.description')
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.doc.data.options.file')
            )
            ->setAliases(['gdda']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = null;
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
        }

        $data = $this->getApplication()->getData();
        if ($file) {
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

            return 0;
        }

        $io->write(json_encode($data, JSON_PRETTY_PRINT));
    }
}
