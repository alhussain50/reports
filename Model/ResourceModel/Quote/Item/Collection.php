<?php

namespace Harriswebworks\Reports\Model\ResourceModel\Quote\Item;

use Magento\Reports\Model\ResourceModel\Quote\Item\Collection as ItemCollection;
use Magento\Reports\Model\Product\DataRetriever as ProductDataRetriever;
use Magento\Framework\App\ObjectManager;

class Collection extends ItemCollection
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productResource;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $customerResource;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $orderResource;

    /**
     * @var ProductDataRetriever
     */
    private $productDataRetriever;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productResource,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderResource,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        ?ProductDataRetriever $productDataRetriever = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $productResource,
            $customerResource,
            $orderResource,
            $connection,
            $resource,
        );
        $this->productDataRetriever = $productDataRetriever
            ?? ObjectManager::getInstance()->get(ProductDataRetriever::class);
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        $items = $this->getItems();
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }
        $productData = $this->productDataRetriever->execute($productIds);
        $orderData = $this->getOrdersData($productIds);
        foreach ($items as $item) {
            $item->setId($item->getProductId());
            if (isset($productData[$item->getProductId()])) {
                $item->setPrice($productData[$item->getProductId()]['price'] * $item->getBaseToGlobalRate());
                $item->setName($productData[$item->getProductId()]['name']);
                $item->setCategoryIds(implode('#', $productData[$item->getProductId()]['category_ids']));
            }
            $item->setOrders(0);
            if (isset($orderData[$item->getProductId()])) {
                $item->setOrders($orderData[$item->getProductId()]['orders']);
            }
        }

        return $this;
    }
}