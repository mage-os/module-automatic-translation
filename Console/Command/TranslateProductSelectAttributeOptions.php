<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Console\Command;

use MageOS\AutomaticTranslation\Service\TranslateSelectAttributes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TranslateProductSelectAttributeOptions extends Command
{
    /**
     * @param TranslateSelectAttributes $translateSelectAttributes
     * @param string|null $name
     */
    public function __construct(
        protected TranslateSelectAttributes $translateSelectAttributes,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('mage-os:translate:product-select-attribute-options');
        $this->setDescription('Translate product select attribute options');
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
