<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\ContributeCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ContributeCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class ContributeCommand extends Command
{
    use CommandTrait;

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    protected $packages = [
        'drupal-console',
        'drupal-console-en',
        'drupal-console-core',
        'drupal-console-extend-plugin',
        'drupal-console-dotenv',
        'drupal-console-develop',
        'drupal-console-yaml'
    ];

    /**
     * ContributeCommand constructor.
     *
     * @param $consoleRoot
     * @param configurationManager $configurationManager
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('develop:contribute')
            ->addOption(
                'code-directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.contribute.options.code-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $codeDirectory = $input->getOption('code-directory');
        if (!$codeDirectory) {
            $codeDirectory = $io->ask(
                $this->trans('commands.develop.contribute.questions.code-directory')
            );
            $input->setOption('code-directory', $codeDirectory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $codeDirectory = $input->getOption('code-directory');
        $fileSystem = new Filesystem();

        foreach ($this->packages as $package) {
            $projectPath = $codeDirectory . '/' . $package;
            $packagePath = $this->consoleRoot . '/vendor/drupal/' . substr($package, 7, strlen($package));
            if ($fileSystem->exists([$projectPath,$packagePath])) {
                $fileSystem->remove($packagePath);
                $fileSystem->symlink(
                    $projectPath,
                    $packagePath
                );
                $io->writeln($this->trans('commands.develop.contribute.messages.symlink'));
                $io->info(
                    $fileSystem->makePathRelative(
                        $packagePath,
                        $this->consoleRoot
                    ) . ' => ',
                    false
                );
                $io->comment(
                    $fileSystem->makePathRelative(
                        $projectPath,
                        $this->consoleRoot
                    )
                );
            }
        }
        $autoloadDistOriginal = $codeDirectory.'/'.$this->packages[0].'/autoload.local.php.dist';
        $autoloadDistLocal = $codeDirectory.'/'.$this->packages[0].'/autoload.local.php';
        if ($fileSystem->exists($autoloadDistOriginal) && !$fileSystem->exists($autoloadDistLocal)) {
            $io->writeln(
                sprintf(
                    $this->trans('commands.develop.contribute.messages.copy'),
                    $fileSystem->makePathRelative(
                        $autoloadDistOriginal,
                        $this->consoleRoot
                    ),
                    $fileSystem->makePathRelative(
                        $autoloadDistLocal,
                        $this->consoleRoot
                    )
                )
            );
            $fileSystem->copy(
                $autoloadDistOriginal,
                $autoloadDistLocal
            );
        }
    }
}
