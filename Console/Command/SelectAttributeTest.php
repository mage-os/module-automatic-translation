<?php

namespace MageOS\AutomaticTranslation\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MageOS\AutomaticTranslation\Service\TranslateSelectAttributes;

/**
 * Class SelectAttributeTest
 */
class SelectAttributeTest extends Command
{
    protected TranslateSelectAttributes $translateSelectAttributes;

    /**
     * SelectAttributeTest constructor.
     * @param TranslateSelectAttributes $translateSelectAttributes
     * @param $name
     */
    public function __construct(
        TranslateSelectAttributes $translateSelectAttributes,
        $name = null
    ) {
        $this->translateSelectAttributes = $translateSelectAttributes;

        parent::__construct($name);
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('mage-os:select-attribute:test');
        $this->setDescription('Test select attribute translation');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->translateSelectAttributes->translateOptions();

        return Command::SUCCESS;
    }
}
