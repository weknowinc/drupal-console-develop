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

        $application = $this->getApplication();
        $applicationData = $application->getData();
        $namespaces = $applicationData['application']['namespaces'];

        $data['language'] = $applicationData['default_language'];
        $data['type'] = 'commands';

        $excludeNamespaces = ['misc', 'demo', 'version', 'develop'];
        $excludeCommands = ['demo', 'version', 'develop'];

        foreach ($namespaces as $namespace) {
            if(!in_array($namespace, $excludeNamespaces)){
                $data['namespaces'][] = ['name' => $namespace];
            }

            if(in_array($namespace, $excludeCommands)) {
                continue;
            }

            foreach ($applicationData['commands'][$namespace] as $command) {
                if(!empty($command['options'])){
                    $command['options'] = array_values($command['options']);
                }

                if(!empty($command['arguments'])){
                    $command['arguments'] = array_values($command['arguments']);
                }

                $data['commands'][] = $command;
            }
        }

        if ($file) {
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

            return 0;
        }

        $io->write(json_encode($data, JSON_PRETTY_PRINT));
    }
}
