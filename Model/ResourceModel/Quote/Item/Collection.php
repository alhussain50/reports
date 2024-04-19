<?php
namespace Harriswebworks\Reports\Model\ResourceModel\Quote\Item;

use Magento\Reports\Model\ResourceModel\Quote\Item\Collection as ItemCollection;
use Harriswebworks\Reports\Model\Product\DataRetriever as ProductDataRetriever;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory; 

class Collection extends ItemCollection
{
    private const PREPARED_FLAG_NAME = 'reports_collection_prepared';

    protected $categoryCollection;
    protected $categoryList=array();


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
        ProductDataRetriever $productDataRetriever,
        CollectionFactory $categoryCollection,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
       
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
            $resource
        );

        $this->productDataRetriever = $productDataRetriever;
        $this->categoryCollection = $categoryCollection;
    }

    public function prepareActiveCartItems()
    {
        $quoteItemsSelect = $this->getSelect();

        if ($this->getFlag(self::PREPARED_FLAG_NAME)) {
            return $quoteItemsSelect;
        }

        $quoteItemsSelect->reset()
            ->from(['main_table' => $this->getTable('quote_item')], '')
            ->columns(['main_table.product_id', 'main_table.name'])
            ->columns(['carts' => new \Zend_Db_Expr('COUNT(main_table.item_id)')])
            ->columns('quote.base_to_global_rate')
            ->columns('quote.customer_id')//add customer id
            ->columns('quote.created_at')//add customer id

            ->joinInner(
                ['quote' => $this->getTable('quote')],
                'main_table.quote_id = quote.entity_id',
                null
            )->where(
                'quote.is_active = ?',
                1
            )->group(
                'main_table.product_id'
            );
        $this->setFlag(self::PREPARED_FLAG_NAME, true);

        return $quoteItemsSelect;
    }

    private function setCategoryCollection(){

        $categories = $this->categoryCollection->create();
        $categories->addAttributeToSelect('*');

        foreach ($categories as $category) {
            //echo $category->getId().' => '.$category->getName() . '<br />';
            $this->categoryList[$category->getId()] = $category->getName();
         }

    }


    public function getCategoryName($ids) {
        $names=[];
        foreach($ids as $id){
            if( $id>2 && array_key_exists($id,$this->categoryList) && $this->categoryList[$id]){
                $names[]=$this->categoryList[$id];
            }
            
        }
        return implode(',',$names);
        
    }
    protected function _afterLoad()
    {
        $this->setCategoryCollection(); //preapare category list
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

                $categoryNames= $this->getCategoryName($productData[$item->getProductId()]['category_ids']);

                $item->setCategoryNames($categoryNames); //@todo
            }
            $item->setOrders(0);
            if (isset($orderData[$item->getProductId()])) {
                $item->setOrders($orderData[$item->getProductId()]['orders']);
            }
        }

        return $this;
    }
    

}