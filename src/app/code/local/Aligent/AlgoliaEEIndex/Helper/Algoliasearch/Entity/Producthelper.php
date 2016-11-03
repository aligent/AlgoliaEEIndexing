<?php
class Aligent_AlgoliaEEIndex_Helper_Algoliasearch_Entity_Producthelper extends Algolia_Algoliasearch_Helper_Entity_Producthelper {

    /**
     * Override existing getProductCollectionQuery to simply dispatch an event with the products.
     *
     * @param $storeId
     * @param null $productIds
     * @param bool $only_visible
     * @param bool $withoutData
     * @return Mage_Sales_Model_Order
     */
    public function getProductCollectionQuery($storeId, $productIds = null, $only_visible = true, $withoutData = false){
        $return = parent::getProductCollectionQuery($storeId,$productIds,$only_visible,$withoutData);

        if ($productIds !== null) {
            Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_check_indexes', array('products' => $productIds));
        }

        return $return;
    }
}
