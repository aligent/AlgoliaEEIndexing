<?php
class Aligent_AlgoliaEEIndex_Model_Observer
{
    private $_productIDsPending = null;
    private $_connection = null;

    private function getConnection() {
        if ($this->_connection === null) {
            $this->_connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        }
        return $this->_connection;
    }

    private function findLatestVersion($changelLogTable) {
        $connection = $this->getConnection();

        // Find the latest price_index version.
        $select = $connection->select()
            ->from('enterprise_mview_metadata', 'version_id')
            ->where('changelog_name = ?', $changelLogTable);
        return $connection->fetchOne($select);
    }

    private function findProductIdsPending($changelLogTable, $idColumn, $maxVersion) {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->distinct()
            ->from($changelLogTable, $idColumn)
            ->join(array('catalog_product_entity' => 'catalog_product_entity'), "$changelLogTable.$idColumn = catalog_product_entity.entity_id", array())
            ->where('version_id > ?', $maxVersion);

        $this->getAdditionalRestrictions($select);

        return $connection->fetchCol($select);
    }

    private function pendingPriceIndex() {
        $maxVersion = $this->findLatestVersion('catalog_product_index_price_cl');
        return $this->findProductIdsPending('catalog_product_index_price_cl', 'entity_id', $maxVersion);
    }

    private function pendingStockIndex()
    {
        $maxVersion = $this->findLatestVersion('cataloginventory_stock_status_cl');
        return $this->findProductIdsPending('cataloginventory_stock_status_cl', 'product_id', $maxVersion);
    }

    public function algoliaRebuildStoreProductIndexCollectionCheckIndexes($oEvent)
    {
        // Check if we are processing products and that those products are not pending a price_index change.
        if($oEvent->getProducts() && count($oEvent->getProducts())) {
            if ($this->_productIDsPending === null) {
                $priceIndexes = $this->pendingPriceIndex();
                $stockIndexes = $this->pendingStockIndex();
                $additionalIndexes = $this->getAdditionalIndexes();

                $this->_productIDsPending = array_unique(array_merge($priceIndexes, $stockIndexes, $additionalIndexes));
            }

            // If we have items that are pending a price_index check the current products against them.
            if ($this->_productIDsPending && count($this->_productIDsPending)) {
                foreach ($oEvent->getProducts() as $id) {
                    if (in_array($id, $this->_productIDsPending)) {
                        // Throw an error to retry next time Algolia is processed.
                        Mage::helper('aligent_algoliaeeindex/log')->logIndex('Skipping, index still pending for entityid ' . $id);
                        throw new Exception('Price index still pending for entityid ' . $id);
                    }
                }
            }
        }
    }

    //Override me for specific situations.
    private function getAdditionalRestrictions(&$select) {
        //$select = $select->where("sku like ?", '%_one_way_%');
    }
    //Override me for specific situations.
    private function getAdditionalIndexes() {
        // add custom $stockIndexes = $this->pendingStockIndex(); //for multiwarehouse?
        return array();

    }
}