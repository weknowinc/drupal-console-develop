<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\DevelopDocGitbookCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class DevelopDocGitbookCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class DevelopDocGitbookCommand extends Command
{
    /**
     * @var TwigRenderer $renderer
     */
    protected $renderer;
    protected $filterNamespaces;
    protected $excludeNamespaces;
    protected $excludeChainCommands;

    /**
     * DevelopDocGitbookCommand constructor.
     *
     * @param TwigRenderer $renderer
     */
    public function __construct(TwigRenderer $renderer)
    {
        $this->renderer = $renderer;
        $this->filterNamespaces = null;
        $this->excludeNamespaces = ['develop', 'yaml'];
        $this->excludeChainCommands = true;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('develop:doc:gitbook')
            ->setDescription($this->trans('commands.develop.doc.gitbook.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.doc.gitbook.options.path')
            )
            ->setAliases(['gdg']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $path = null;
        if ($input->hasOption('path')) {
            $path = $input->getOption('path');
        }

        if (!$path) {
            $io->error(
                $this->trans('commands.develop.doc.gitbook.messages.missing_path')
            );

            return 1;
        }

        $this->renderer->addSkeletonDir(__DIR__ . '/../../templates');

        $application = $this->getApplication();

        // Get data filtering, excluding and remove chain command based on preferences
        $applicationData = $application->getData($this->filterNamespaces, $this->excludeNamespaces, $this->excludeChainCommands);

        $namespaces = $applicationData['application']['namespaces'];

        foreach ($namespaces as $namespace) {
            foreach ($applicationData['commands'][$namespace] as $command) {
                $this->renderFile(
                    'gitbook' . DIRECTORY_SEPARATOR . 'command.md.twig',
                    $path . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command['dashed'] . '.md',
                    $command,
                    null,
                    $this->renderer
                );
            }
        }

        $this->renderFile(
            'gitbook'.DIRECTORY_SEPARATOR.'available-commands.md.twig',
            $path . DIRECTORY_SEPARATOR . 'commands'.DIRECTORY_SEPARATOR.'available-commands.md',
            $applicationData,
            null,
            $this->renderer
        );

        $this->renderFile(
            'gitbook'.DIRECTORY_SEPARATOR.'available-commands-list.md.twig',
            $path . DIRECTORY_SEPARATOR . 'commands'.DIRECTORY_SEPARATOR.'available-commands-list.md',
            $applicationData,
            null,
            $this->renderer
        );
    }

    private function renderFile($template, $target, $parameters, $flag = null, $renderer)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $renderer->render($template, $parameters), $flag);
    }
}
