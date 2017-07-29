<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\DevelopTranslationPendingCommand.
 */

namespace Drupal\Console\Develop\Command;

use Drupal\Console\Command\Shared\TranslationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\NestedArray;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class DevelopTranslationPendingCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class DevelopTranslationPendingCommand extends Command
{
    use TranslationTrait;

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
      * @var NestedArray
      */
    protected $nestedArray;

    /**
     * @var mixed array
     */
    protected $excludeTranslations = ['messages.console'];


    /**
     * DevelopTranslationPendingCommand constructor.
     *
     * @param $consoleRoot
     * @param $configurationManager
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager,
        NestedArray $nestedArray
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('develop:translation:pending')
            ->setDescription($this->trans('commands.develop.translation.pending.description'))
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                $this->trans('commands.develop.translation.pending.arguments.language'),
                null
            )
            ->addArgument(
                'library',
                InputArgument::OPTIONAL,
                $this->trans('commands.develop.translation.pending.arguments.library'),
                null
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.translation.pending.options.file'),
                null
            )
            ->setAliases(['tp']);
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
                    $this->trans('commands.develop.translation.pending.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language &&$language != 'all') {
            $languages = [$language => $languages[$language]];
        }

        $pendingTranslations = $this->determinePendingTranslation($io, $language, $library, $languages, $file);

        if ($file) {
            $io->success(
                sprintf(
                    $this->trans('commands.develop.translation.pending.messages.success-language-file'),
                    $pendingTranslations,
                    $languages[$language],
                    $file
                )
            );
        } else {
            $io->success(
                sprintf(
                    $this->trans('commands.develop.translation.pending.messages.success-language'),
                    $pendingTranslations,
                    $languages[$language]
                )
            );
        }
    }

    protected function determinePendingTranslation($io, $language = null, $library = null, $languages, $fileFilter)
    {
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $statistics = [];

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

        $englishFiles = $englishFilesFinder->files()->name('*.yml')->in($englishDirectory);

        $pendingTranslations = 0;
        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            if ($fileFilter && $fileFilter != $file->getBasename()) {
                continue;
            }

            try {
                $englishFileParsed = $yaml->parse(file_get_contents($resource));
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

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                if (!file_exists($resourceTranslated)) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.develop.translation.pending.messages.missing-file'),
                            $languageName,
                            $file->getBasename()
                        )
                    );
                    continue;
                }

                try {
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                }

                $diffStatistics = ['total' => 0, 'equal' => 0, 'diff' => 0];

                // Calculate diff excluding examples execution
                $diff = $this->nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, true, $diffStatistics);

                if (!empty($diff)) {
                    $diffFlatten = [];
                    $keyFlatten = '';
                    $this->nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

                    $tableHeader = [
                        $this->trans('commands.develop.translation.pending.messages.key'),
                        $this->trans('commands.develop.translation.pending.messages.value'),
                    ];

                    $tableRows = [];

                    $diffFlatten = array_filter(
                        $diffFlatten,
                        array($this, 'validatePendingTranslation'),
                        ARRAY_FILTER_USE_BOTH
                    );

                    foreach ($diffFlatten as $yamlKey => $yamlValue) {
                        $tableRows[] = [
                            $yamlKey,
                            $yamlValue
                        ];

                    }

                    if (count($diffFlatten)) {
                        $io->writeln(
                            sprintf(
                                $this->trans('commands.develop.translation.pending.messages.pending-translations'),
                                $languageName,
                                $file->getBasename()
                            )
                        );

                        $io->table($tableHeader, $tableRows, 'compact');
                        $pendingTranslations+= count($diffFlatten);
                    }
                }
            }
        }

        return $pendingTranslations;
    }

    public function validatePendingTranslation($value, $key) {
        if (in_array($key, $this->excludeTranslations) ||
            preg_match('/examples.\d+.execution/', $key) ||
            $this->isYamlKey($value)) {
            return false;
        } else {
            return true;
        }
    }
}
