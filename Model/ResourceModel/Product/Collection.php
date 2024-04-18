<?php
namespace Harriswebworks\Reports\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Catalog\Model\CategoryFactory;

class Collection extends ProductCollection
{

    protected $categoryFactory;

    /**
     * Product entity identifier
     *
     * @var int
     */
    protected $_productEntityId;

    /**
     * Product entity table name
     *
     * @var string
     */
    protected $_productEntityTableName;

    /**
     * Product entity attribute set identifier
     *
     * @var int
     */
    protected $_productEntityAttributeSetId;

    /**
     * Select count
     *
     * @var int
     */
    protected $_selectCountSqlType = 0;

    /**
     * @var \Magento\Reports\Model\Event\TypeFactory
     */
    protected $_eventTypeFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_productType;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    protected $quoteResource;

    /**
     * @var DataObject
     */
    private $_totals;

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Reports\Model\Event\TypeFactory $eventTypeFactory
     * @param \Magento\Catalog\Model\Product\Type $productType
     * @param \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteResource
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param ProductLimitationFactory|null $productLimitationFactory
     * @param \Magento\Framework\EntityManager\MetadataPool|null $metadataPool
     * @param \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer|null $tableMaintainer
     * @param \Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver|null $priceTableResolver
     * @param \Magento\Framework\Indexer\DimensionFactory|null $dimensionFactory
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Reports\Model\Event\TypeFactory $eventTypeFactory,
        \Magento\Catalog\Model\Product\Type $productType,
        \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteResource,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ProductLimitationFactory $productLimitationFactory = null,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool = null,
        \Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer $tableMaintainer = null,
        \Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver $priceTableResolver = null,
        \Magento\Framework\Indexer\DimensionFactory $dimensionFactory = null
    ) {
        // $this->setProductEntityId($product->getEntityIdField());
        // $this->setProductEntityTableName($product->getEntityTable());
        // $this->setProductAttributeSetId($product->getEntityType()->getDefaultAttributeSetId());
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection,
            $productLimitationFactory,
            $metadataPool,
            $tableMaintainer,
            $priceTableResolver,
            $dimensionFactory
        );
        $this->categoryFactory = $categoryFactory;
        $this->_eventTypeFactory = $eventTypeFactory;
        $this->_productType = $productType;
        $this->quoteResource = $quoteResource;
    }

    public function addCategoryIdswithPids($ids)
    {
        if (empty($ids)) {
            return $this;
        }

        $select = $this->getConnection()->select();

        $select->from($this->_productCategoryTable, ['product_id', 'category_id']);
        $select->where('product_id IN (?)', $ids, \Zend_Db::INT_TYPE);

        $data = $this->getConnection()->fetchAll($select);

        $categoryIds = [];
        foreach ($data as $info) {
            if (isset($categoryIds[$info['product_id']])) {
                $categoryIds[$info['product_id']][] = $info['category_id'];
            } else {
                $categoryIds[$info['product_id']] = [$info['category_id']];
            }
        }

        // foreach ($this->getItems() as $item) {
        //     $productId = $item->getId();
        //     if (isset($categoryIds[$productId])) {
        //         $item->setCategoryIds($categoryIds[$productId]);
        //     } else {
        //         $item->setCategoryIds([]);
        //     }
        // }

        foreach ($this->getItems() as $item) {
            $productId = $item->getId();
            if (isset($categoryIds[$productId])) {
                $categoryNames = [];
                foreach ($categoryIds[$productId] as $categoryId) {
                    // Load category by ID
                    $category = $this->categoryFactory->create()->load($categoryId);
                    // Get category name
                    $categoryNames[] = $category->getName();
                }
                $item->setCategoryIds($categoryIds[$productId]);
                // Set category names
                $item->setCategoryNames($categoryNames);
            } else {
                $item->setCategoryIds([]);
                $item->setCategoryNames([]);
            }
        }

        // dd($categoryNames);

        $this->setFlag('category_ids_added', true);
        return $this;
    }
}
