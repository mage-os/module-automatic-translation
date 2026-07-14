<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Backend\Block\Store\Switcher as StoreSwitcher;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Ui\DataProvider\Product\Form\Modifier\TranslationStores;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class TranslationStoresTest extends TestCase
{
    private StoreSwitcher&Stub $storeSwitcher;
    private ModuleConfig&Stub $moduleConfig;
    private RequestInterface&Stub $request;
    private ProductRepositoryInterface&MockObject $productRepository;
    private TranslationStores $modifier;

    protected function setUp(): void
    {
        $this->storeSwitcher = $this->createStub(StoreSwitcher::class);
        $this->moduleConfig = $this->createStub(ModuleConfig::class);
        $this->request = $this->createStub(RequestInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);

        $this->storeSwitcher->method('getSwitchUrl')->willReturn('http://example.com/switch');
        $this->storeSwitcher->method('getWebsites')->willReturn([]);

        $this->modifier = new TranslationStores(
            $this->storeSwitcher,
            $this->moduleConfig,
            $this->request,
            $this->productRepository
        );
    }

    /**
     * A new product has no "id" request param; getById() must not be called with a null id
     * (which triggers a "null as array offset" deprecation in PHP 8.5).
     */
    public function testNewProductDoesNotLoadProductById(): void
    {
        $this->request->method('getParam')->willReturn(null);
        $this->productRepository->expects($this->never())->method('getById');

        $stores = $this->getResolvedTranslationStores();

        $this->assertSame([], $stores);
    }

    /**
     * An existing product provides its id; getById() is called with the id cast to int.
     */
    public function testExistingProductLoadsProductById(): void
    {
        $this->request->method('getParam')->willReturn('5');

        $product = $this->createStub(Product::class);
        $product->method('getStoreIds')->willReturn([]);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with(5)
            ->willReturn($product);

        $stores = $this->getResolvedTranslationStores();

        $this->assertSame([], $stores);
    }

    /**
     * Runs the modifier and returns the resolved translationStores config it produced.
     */
    private function getResolvedTranslationStores(): array
    {
        $meta = $this->modifier->modifyMeta([]);

        return $meta['select_store_modal']['children']['translation_store_list']
            ['arguments']['data']['config']['translationStores'];
    }
}
