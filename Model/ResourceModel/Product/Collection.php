<?php
namespace Harriswebworks\Reports\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Collection extends ProductCollection
{
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

        foreach ($this->getItems() as $item) {
            $productId = $item->getId();
            if (isset($categoryIds[$productId])) {
                $item->setCategoryIds($categoryIds[$productId]);
            } else {
                $item->setCategoryIds([]);
            }
        }

        $this->setFlag('category_ids_added', true);
        return $this;
    }

    
}
