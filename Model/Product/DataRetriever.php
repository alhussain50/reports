<?php

namespace Harriswebworks\Reports\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Reports\Model\Product\DataRetriever as OriginalDataRetriever;

class DataRetriever extends OriginalDataRetriever
{

     /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

      /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {

        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($productCollectionFactory, $storeManager);
    }

    /**
     * Override of execute method to modify behavior
     *
     * @param array $entityIds
     * @return array
     */
    public function execute(array $entityIds = []): array
    {
        $productCollection = $this->getProductCollection($entityIds);

        // Implement the logic of prepareDataByCollection() here
        $productsData = [];
        foreach ($productCollection as $product) {
            $productsData[$product->getId()] = $product->getData();
        }

        return $productsData;
    }


    protected function getProductCollection(array $entityIds = []): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create(); 
        $productCollection->addAttributeToSelect('name'); 
        $productCollection->addIdFilter($entityIds); 
        $productCollection->addPriceData(null, $this->getWebsiteIdForFilter());
        $productCollection->addCategoryIdswithPids($entityIds);


        return $productCollection;
    }

    /**
     * Retrieve website id for filter collection
     *
     * @return int
     */
    protected function getWebsiteIdForFilter(): int
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        if ($defaultStoreView) {
            $websiteId = (int)$defaultStoreView->getWebsiteId();
        } else {
            $websites = $this->storeManager->getWebsites();
            $website = reset($websites);
            $websiteId = (int)$website->getId();
        }

        return $websiteId;
    }
}
