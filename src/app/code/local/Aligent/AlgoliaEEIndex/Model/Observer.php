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

    /**
     * Find the latest version ID for a particular changeLog
     *
     * @param $changeLogTable
     * @return mixed
     */
    private function findLatestVersion($changeLogTable) {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from('enterprise_mview_metadata', 'version_id')
            ->where('changelog_name = ?', $changeLogTable);
        return $connection->fetchOne($select);
    }

    /**
     * Find all product ID's who have a version ID greater then the maxVersion.
     *
     * @param $changeLogTable
     * @param $idColumn
     * @param $maxVersion
     *
     * @return mixed - array of ID's
     */
    private function findProductIdsPending($changeLogTable, $idColumn, $maxVersion) {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->distinct()
            ->from($changeLogTable, $idColumn)
            ->join(array('catalog_product_entity' => 'catalog_product_entity'), "$changeLogTable.$idColumn = catalog_product_entity.entity_id", array())
            ->where('version_id > ?', $maxVersion);

        $this->getAdditionalRestrictions($select);

        return $connection->fetchCol($select);
    }

    /**
     * Find all productId's pending a price reindex.
     *
     * @return Array
     */
    private function pendingPriceIndex() {
        $returnArray = array();

        if (Mage::helper('aligent_algoliaeeindex')->shouldCheckPriceIndex()) {
            $maxVersion = $this->findLatestVersion('catalog_product_index_price_cl');
            $returnArray = $this->findProductIdsPending('catalog_product_index_price_cl', 'entity_id', $maxVersion);
        }

        return $returnArray;
    }

    /**
     * Find all productId's pending a stock reindex.
     *
     * @return Array
     */
    private function pendingStockIndex()
    {
        $returnArray = array();

        if (Mage::helper('aligent_algoliaeeindex')->shouldCheckStockIndex()) {
            $maxVersion = $this->findLatestVersion('cataloginventory_stock_status_cl');
            $returnArray = $this->findProductIdsPending('cataloginventory_stock_status_cl', 'product_id', $maxVersion);
        }

        return $returnArray;
    }

    /**
     * Observe the dispatched event and check if any of the productId's are in a pending index state.
     * If they are, throw an error to cancel the Algolia Processing of this particular event.
     *
     * @param $oEvent
     * @throws Exception
     */
    public function algoliaRebuildStoreProductIndexCollectionCheckIndexes($oEvent)
    {
        // Check if we are processing products and that those products are not pending an index change.
        if($oEvent->getProducts() && count($oEvent->getProducts())) {
            if ($this->_productIDsPending === null) {
                $priceIndexes = $this->pendingPriceIndex();
                $stockIndexes = $this->pendingStockIndex();
                $additionalIndexes = $this->getAdditionalIndexes();

                $this->_productIDsPending = array_unique(array_merge($priceIndexes, $stockIndexes, $additionalIndexes));
            }

            // If we have items that are pending an index change, check the current products against them.
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

    /**
     * This function can be used to add client specific conditions to filter the pending ID's to reduce array for items we know will be ignored.
     *
     * eg.
     *    Restrict configurables by SKU's - $select = $select->where("sku like ?", '%_one_way_%');
     *
     * @param $select
     */
    private function getAdditionalRestrictions(&$select) {}

    /**
     * This function allows for client specific additional indexes to check.
     * The returned array of entity_id will also be skipped from the Algolia indexing.
     *
     * This can be useful if a client is using a multi-warehouse module and not standard indexes.
     *
     * @return array
     */
    private function getAdditionalIndexes() {
        return array();
    }
}