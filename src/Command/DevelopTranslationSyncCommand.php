<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\DevelopTranslationSyncCommand.
 */

namespace Drupal\Console\Develop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class DevelopTranslationSyncCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class DevelopTranslationSyncCommand extends Command
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
     * DevelopTranslationSyncCommand constructor.
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
            ->setName('develop:translation:sync')
            ->setDescription($this->trans('commands.develop.translation.sync.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.develop.translation.sync.arguments.language'),
                null
            )
            ->addArgument(
                'library',
                InputArgument::OPTIONAL,
                $this->trans('commands.develop.translation.sync.arguments.library'),
                null
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.translation.stats.options.file'),
                null
            )
            ->setAliases(['tsy']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $language = $input->getArgument('language');
        $library = $input->getArgument('library');
        $file = $input->getOption('file');
        $languages = $this->configurationManager->getConfiguration()->get('application.languages');
        unset($languages['en']);

        if ($language && $language != 'all' && !isset($languages[$language])) {
            $io->error(
                sprintf(
                    $this->trans('commands.develop.translation.stats.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language &&$language != 'all') {
            $languages = [$language => $languages[$language]];
        }

        $this->syncTranslations($io, $language, $library, $languages, $file);

        $io->success($this->trans('commands.develop.translation.sync.messages.sync-finished'));
    }

    protected function syncTranslations($io, $language = null, $library = null, $languages, $file)
    {
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $dumper = new Dumper();

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

        if ($file) {
            $englishFiles = $englishFilesFinder->files()->name($file)->in($englishDirectory);
        } else {
            $englishFiles = $englishFilesFinder->files()->name('*.yml')->in($englishDirectory);
        }

        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            try {
                $englishFile = file_get_contents($resource);
                $englishFileParsed = $yaml->parse($englishFile);
            } catch (ParseException $e) {
                $io->error($filename . '.yml: ' . $e->getMessage());
                continue;
            }

            foreach ($languages as $langCode => $languageName) {

                if($library) {
                    $languageDir = $this->consoleRoot .
                        sprintf(
                            DRUPAL_CONSOLE_LIBRARY,
                            $library,
                            $langCode
                        );
                } else {
                    $languageDir = $this->consoleRoot .
                        sprintf(
                            DRUPAL_CONSOLE_LANGUAGE,
                            $langCode
                        );
                }

                if (isset($language) && $langCode != $language && $language != 'all') {
                    continue;
                }

                if(!is_dir($languageDir)) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.develop.translation.sync.messages.missing-language'),
                            $languages[$langCode]
                        )
                    );
                    unset($languages[$langCode]);
                    continue;
                }

                if (!isset($statistics[$langCode])) {
                    $statistics[$langCode] = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
                }

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                //echo $resourceTranslated . "\n";
                if (!file_exists($resourceTranslated)) {
                    file_put_contents($resourceTranslated, $englishFile);
                    $io->info(
                        sprintf(
                            $this->trans('commands.develop.translation.sync.messages.created-file'),
                            $file->getBasename(),
                            $languageName
                        )
                    );
                    continue;
                }

                try {
                    //print $resourceTranslated . "\n";
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                    continue;
                }

                $resourceTranslatedParsed = array_replace_recursive($englishFileParsed, $resourceTranslatedParsed);

                $resourceTranslatedParsed = array_replace_recursive($englishFileParsed,
                    array_intersect_key(
                        $resourceTranslatedParsed, $englishFileParsed
                    )
                );

                try {
                    $resourceTranslatedParsedYaml = $dumper->dump($resourceTranslatedParsed, 10);
                } catch (\Exception $e) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.develop.translation.sync.messages.error-generating'),
                            $resourceTranslated,
                            $languageName,
                            $e->getMessage()
                        )
                    );

                    continue;
                }

                try {
                    file_put_contents($resourceTranslated, $resourceTranslatedParsedYaml);
                } catch (\Exception $e) {
                    $io->error(
                        sprintf(
                            '%s: %s',
                            $this->trans('commands.develop.translation.sync.messages.error-writing'),
                            $resourceTranslated,
                            $languageName,
                            $e->getMessage()
                        )
                    );

                    return 1;
                }
            }
        }
    }
}
