<?php

namespace MageOS\AutomaticTranslation\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MageOS\AutomaticTranslation\Api\TranslateProductsInterface;

/**
 * Class TestProcedure
 */
class TestProcedure extends Command
{
    protected TranslateProductsInterface $translateProducts;

    /**
     * TestProcedure constructor.
     * @param TranslateProductsInterface $translateProducts
     * @param $name
     */
    public function __construct(
        TranslateProductsInterface $translateProducts,
        $name = null
    ) {
        $this->translateProducts = $translateProducts;
        parent::__construct($name);
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('mage-os:procedure:test');
        $this->setDescription('Test the translation procedure by shell');
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
