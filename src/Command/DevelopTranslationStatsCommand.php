<?php

/**
 * @file
 * Contains \Drupal\Console\Develop\Command\DevelopTranslationStatsCommand.
 */

namespace Drupal\Console\Develop\Command;

use Drupal\Console\Command\Shared\TranslationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Core\Utils\NestedArray;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Generator\Generator;

/**
 * Class DevelopTranslationStatsCommand.
 *
 * @DrupalCommand (
 *     extension="drupal/console-develop",
 *     extensionType="library"
 * )
 */

class DevelopTranslationStatsCommand extends Command
{
    use TranslationTrait;
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
     * @var TwigRenderer $renderer
     */
    protected $renderer;

    /**
      * @var NestedArray
      */
    protected $nestedArray;

    /**
     * @var mixed array
     */
    protected $excludeTranslations = ['messages.console'];

    /**
     * DevelopTranslationStatsCommand constructor.
     *
     * @param $appRoot
     * @param ConfigurationManager $configurationManager
     * @param TwigRenderer         $renderer
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager,
        TwigRenderer $renderer,
        NestedArray $nestedArray
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        $this->renderer = $renderer;
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */

    protected function configure()
    {
        $this
            ->setName('develop:translation:stats')
            ->setDescription($this->trans('commands.develop.translation.stats.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.develop.translation.stats.arguments.language'),
                null
            )
            ->addArgument(
                'library',
                InputArgument::OPTIONAL,
                $this->trans('commands.develop.translation.stats.arguments.library'),
                null
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.develop.translation.stats.options.format'),
                'table'
            )
            ->setAliases(['ts']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $language = $input->getArgument('language');
        $library = $input->getArgument('library');
        $format = $input->getOption('format');

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

        $stats = $this->calculateStats($io, $language, $library, $languages);

        if ($format == 'table') {
            $tableHeaders = [
                $this->trans('commands.develop.translation.stats.messages.language'),
                $this->trans('commands.develop.translation.stats.messages.percentage'),
                $this->trans('commands.develop.translation.stats.messages.iso')
            ];

            $io->table($tableHeaders, $stats);
            return 0;
        }

        if ($format == 'markdown') {
            $arguments['language'] = $this->trans('commands.develop.translation.stats.messages.language');
            $arguments['percentage'] = $this->trans('commands.develop.translation.stats.messages.percentage');

            $arguments['languages'] = $stats;

            $this->renderer->addSkeletonDir(__DIR__ . '/../../templates');

            $io->writeln(
                $this->renderer->render(
                    'core/translation/stats.md.twig',
                    $arguments
                )
            );
        }
    }

    protected function calculateStats($io, $language = null, $library = null, $languages)
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

        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');
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
                                //don't show that language if that repo isn't present
                if (!file_exists($languageDir)) {
                    continue;
                }

                if (isset($language) && $langCode != $language && $language != 'all') {
                    continue;
                }

                if (!isset($statistics[$langCode])) {
                    $statistics[$langCode] = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
                }

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                if (!file_exists($resourceTranslated)) {
                    $englishFileEntries = count($englishFileParsed, COUNT_RECURSIVE);
                    $statistics[$langCode]['total'] += $englishFileEntries;
                    $statistics[$langCode]['equal'] += $englishFileEntries;
                    continue;
                }

                try {
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                }

                $diffStatistics = ['total' => 0, 'equal' => 0, 'diff' => 0];
                $diff = $this->nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, true, $diffStatistics);

                $yamlPending = 0;
                if (!empty($diff)) {
                    $diffFlatten = [];
                    $keyFlatten = '';
                    $this->nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

                    $diffFlatten = array_filter(
                        $diffFlatten,
                        array($this, 'validatePendingTranslation'),
                        ARRAY_FILTER_USE_BOTH
                    );

                    $yamlPending = count($diffFlatten);
                }

                $statistics[$langCode]['total'] += $diffStatistics['total'];
                $statistics[$langCode]['pending'] += $diffStatistics['pending'] + $yamlPending;
            }
        }

        $stats = [];
        foreach ($statistics as $langCode => $statistic) {
            $index = isset($languages[$langCode])? $languages[$langCode]: $langCode;

            $stats[] = [
                'name' => $index,
                'percentage' => round(100 - $statistic['pending']/$statistic['total']*100, 2),
                'iso' => $langCode
            ];
        }

        usort(
            $stats, function ($a, $b) {
                return $a["percentage"] <  $b["percentage"];
            }
        );

        return $stats;
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
