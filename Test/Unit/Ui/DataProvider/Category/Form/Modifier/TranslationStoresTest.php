<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Test\Unit\Ui\DataProvider\Category\Form\Modifier;

use Magento\Backend\Block\Store\Switcher as StoreSwitcher;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Ui\DataProvider\Category\Form\Modifier\TranslationStores;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class TranslationStoresTest extends TestCase
{
    private StoreSwitcher&Stub $storeSwitcher;
    private ModuleConfig&Stub $moduleConfig;
    private RequestInterface&Stub $request;
    private CategoryRepositoryInterface&MockObject $categoryRepository;
    private TranslationStores $modifier;

    protected function setUp(): void
    {
        $this->storeSwitcher = $this->createStub(StoreSwitcher::class);
        $this->moduleConfig = $this->createStub(ModuleConfig::class);
        $this->request = $this->createStub(RequestInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);

        $this->storeSwitcher->method('getSwitchUrl')->willReturn('http://example.com/switch');
        $this->storeSwitcher->method('getWebsites')->willReturn([]);

        $this->modifier = new TranslationStores(
            $this->storeSwitcher,
            $this->moduleConfig,
            $this->request,
            $this->categoryRepository
        );
    }

    /**
     * A new category has no "id" request param; get() must not be called with a null id
     * (which triggers a "null as array offset" deprecation in PHP 8.5).
     */
    public function testNewCategoryDoesNotLoadCategory(): void
    {
        $this->request->method('getParam')->willReturn(null);
        $this->categoryRepository->expects($this->never())->method('get');

        $stores = $this->getResolvedTranslationStores();

        $this->assertSame([], $stores);
    }

    /**
     * An existing category provides its id; get() is called with the id cast to int.
     */
    public function testExistingCategoryLoadsCategoryById(): void
    {
        $this->request->method('getParam')->willReturn('5');

        $category = $this->createStub(Category::class);
        $category->method('getStoreIds')->willReturn([]);

        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->with(5)
            ->willReturn($category);

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
