<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Console\Command;

use MageOS\AutomaticTranslation\Model\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class TestTranslation extends Command
{
    /**
     * @param Translator $translator
     * @param string|null $name
     */
    public function __construct(
        protected Translator $translator,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = $input->getOption('text');
        $targetlang = $input->getOption('targetlang');
        $sourcelang = $input->getOption('sourcelang') ?: null;

        $output->writeln($this->translator->translate($text, $targetlang, $sourcelang));

        return Command::SUCCESS;
    }
}
