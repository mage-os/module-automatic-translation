<?php

namespace MageOS\AutomaticTranslation\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use MageOS\AutomaticTranslation\Model\Translator;
use Exception;

/**
 * Class TestTranslation
 */
class TestTranslation extends Command
{
    protected Translator $translator;

    /**
     * TestTranslation constructor.
     * @param Translator $translator
     * @param $name
     */
    public function __construct(
        Translator $translator,
        $name = null
    ) {
        $this->translator = $translator;
        parent::__construct($name);
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('mage-os:translation:test');
        $this->setDescription('Command to test Translation');
        $this->addOption(
            'text',
            't',
            InputOption::VALUE_REQUIRED,
            'Text to translate'
        );
        $this->addOption(
            'sourcelang',
            's',
            InputOption::VALUE_OPTIONAL,
            'Source language'
        );
        $this->addOption(
            'targetlang',
            'l',
            InputOption::VALUE_REQUIRED,
            'Target language'
        );
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = $input->getOption('text');
        $targetlang = $input->getOption('targetlang');
        $sourcelang = (!empty($input->getOption('sourcelang'))) ? $input->getOption('sourcelang') : null;

        $output->writeln($this->translator->translate($text, $targetlang, $sourcelang));

        return Command::SUCCESS;
    }
}
