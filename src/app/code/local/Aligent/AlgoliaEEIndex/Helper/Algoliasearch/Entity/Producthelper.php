<?php
class Aligent_AlgoliaEEIndex_Helper_Algoliasearch_Entity_Producthelper extends Algolia_Algoliasearch_Helper_Entity_Producthelper {

    public function getProductCollectionQuery($storeId, $productIds = null, $only_visible = true){
        $return = parent::getProductCollectionQuery($storeId,$productIds,$only_visible);

        Mage::dispatchEvent('algolia_rebuild_store_product_index_collection_check_indexes', array('products' => $productIds));

        return $return;
    }
}
