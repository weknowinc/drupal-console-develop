<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\TranslationCleanupCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\views\Views;

/**
 * Class TranslationCleanupCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class TranslationCleanupCommand extends Command
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

    /**
     * TranslationCleanupCommand constructor.
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
        $this
            ->setName('translation:cleanup')
            ->setDescription($this->trans('commands.translation.cleanup.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.translation.cleanup.arguments.language'),
                null
            )
            ->addArgument(
                'library',
                InputArgument::OPTIONAL,
                $this->trans('commands.translation.cleanup.arguments.library'),
                null
            )
            ->setAliases(['tc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new DrupalStyle($input, $output);

        $language = $input->getArgument('language');
        $library = $input->getArgument('library');

        $languages = $this->configurationManager->getConfiguration()->get('application.languages');
        unset($languages['en']);

        if ($language && $language != 'all' && !isset($languages[$language])) {
            $io->error(
                sprintf(
                    $this->trans('commands.translation.cleanup.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language &&$language != 'all') {
            $languages = [$language => $languages[$language]];
        }

        $this->cleanupTranslations($io, $language, $library, $languages);

        $io->success(
            $this->trans('commands.translation.cleanup.messages.success')
        );
    }

    protected function cleanupTranslations($io, $language = null, $library = null, $languages)
    {
        if($library) {
            $englishDirectory = $this->consoleRoot .
                sprintf(
                    DRUPAL_CONSOLE_LIBRARY,
                    $library,
                    'en'
                );
        } else {
            $englishDirectory = $this->consoleRoot .
                sprintf(
                    DRUPAL_CONSOLE_LANGUAGE,
                    'en'
                );
        }


        foreach ($languages as $langCode => $languageName) {
            if($library) {
                $langDirectory = $this->consoleRoot .
                    sprintf(
                        DRUPAL_CONSOLE_LIBRARY,
                        $library,
                        $langCode
                    );
            } else {
                $langDirectory = $this->consoleRoot .
                    sprintf(
                        DRUPAL_CONSOLE_LANGUAGE,
                        $langCode
                    );
            }

            if (file_exists($langDirectory)) {
                $finder = new Finder();
                foreach ($finder->files()->name('*.yml')->in($langDirectory) as $file) {
                    $filename = $file->getBasename('.yml');
                    if (!file_exists($englishDirectory . DIRECTORY_SEPARATOR .  $filename . '.yml')) {
                        $io->info(
                            sprintf(
                                $this->trans('commands.translation.cleanup.messages.file-deleted'),
                                $filename,
                                $languageName
                            )
                        );
                        unlink($langDirectory. DIRECTORY_SEPARATOR . $filename . '.yml');
                    }
                }
            }
        }
    }
}
