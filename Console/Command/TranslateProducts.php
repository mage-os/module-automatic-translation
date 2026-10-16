<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Console\Command;

use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TranslateProducts extends Command
{
    /**
     * @param TranslateProductsInterface $translateProducts
     * @param string|null $name
     */
    public function __construct(
        protected TranslateProductsInterface $translateProducts,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('mage-os:translate:products');
        $this->setDescription('Translate products');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->translateProducts->translateProducts();

        return Command::SUCCESS;
    }
}
