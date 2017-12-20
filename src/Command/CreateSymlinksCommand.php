<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\CreateSymlinksCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CreateSymlinksCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */
class CreateSymlinksCommand extends Command
{
    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $packages = [
        'drupal-console',
        'drupal-console-core',
        'drupal-console-extend-plugin',
        'drupal-console-develop',
        'drupal-console-dotenv',
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('develop:create:symlinks')
            ->setDescription($this->trans('commands.develop.create.symlinks.description'))
            ->addOption(
                'code-directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.create.symlinks.options.code-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->newLine();
        $io->comment(
            trim($this->trans('commands.develop.create.symlinks.messages.info')),
            false
        );

        $codeDirectory = $input->getOption('code-directory');
        if (!$codeDirectory) {
            $codeDirectory = $io->ask(
                $this->trans('commands.develop.create.symlinks.questions.code-directory')
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
        if (!$codeDirectory) {
            $io->error(
                $this->trans('commands.develop.create.symlinks.messages.no-directory')
            );
        }
        $codeDirectory = $str = rtrim($codeDirectory, '/');

        $io->writeln(
            $this->trans('commands.develop.create.symlinks.messages.symlink')
        );

        foreach ($this->packages as $package) {
            $projectDirectory = $codeDirectory . '/' . $package;
            $packageDirectory = $this->consoleRoot . '/vendor/drupal/' . substr($package, 7, strlen($package));
            $this->symlinkDirectory(
                $io,
                $projectDirectory,
                $packageDirectory
            );
        }

        $languages = $this->configurationManager
                ->getConfiguration()
                ->get('application.languages');

        foreach ($languages as $languageKey => $language) {
            $projectDirectory = $codeDirectory . '/drupal-console-' . $languageKey;
            $packageDirectory = $this->consoleRoot . '/vendor/drupal/console-' . $languageKey;
            $this->symlinkDirectory(
                $io,
                $projectDirectory,
                $packageDirectory
            );
        }

        $autoloadDistOriginal = $codeDirectory.'/'.$this->packages[0].'/autoload.local.php.dist';
        $autoloadDistLocal = $codeDirectory.'/'.$this->packages[0].'/autoload.local.php';
        $this->copyAutoloadFile(
            $io,
            $autoloadDistOriginal,
            $autoloadDistLocal
        );
    }

    /**
     * @param DrupalStyle   $io
     * @param string        $projectDirectory
     * @param string        $packageDirectory
     */
    protected function symlinkDirectory(
        DrupalStyle $io,
        $projectDirectory,
        $packageDirectory
    ) {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists([$projectDirectory])) {
            if ($fileSystem->exists([$packageDirectory])) {
                $fileSystem->remove($packageDirectory);
            }
            $fileSystem->symlink(
                $projectDirectory,
                $packageDirectory
            );
            $io->info(
                rtrim(
                    $fileSystem->makePathRelative(
                        $packageDirectory,
                        $this->consoleRoot
                    ),
                    '/'
                ) . ' => ',
                    FALSE
            );
            $io->writeln(
                rtrim(
                    $fileSystem->makePathRelative(
                        $projectDirectory,
                        $this->consoleRoot
                    ),
                    '/'
                )
            );
        }
    }

    /**
     * @param DrupalStyle   $io
     * @param string        $autoloadDistOriginal
     * @param string        $autoloadDistLocal
     */
    protected function copyAutoloadFile(
        DrupalStyle $io,
        $autoloadDistOriginal,
        $autoloadDistLocal
    ) {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($autoloadDistOriginal) && !$fileSystem->exists($autoloadDistLocal)) {
            $io->writeln(
                sprintf(
                    $this->trans('commands.develop.create.symlinks.messages.copy'),
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
