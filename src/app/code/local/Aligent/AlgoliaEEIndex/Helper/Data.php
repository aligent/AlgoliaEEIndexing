<?php
class Aligent_AlgoliaEEIndex_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_overrideAlgoliaRunner = null;
    private $_checkPriceIndex = null;
    private $_checkStockIndex = null;
    private $_customLog = null;
    private $_customLogName = null;
    private $_retryLimit = null;

    public function shouldOverrideAlgoliaRunner()
    {
        if ($this->_overrideAlgoliaRunner === null) {
            $this->_overrideAlgoliaRunner = Mage::getStoreConfigFlag('aligent_algoliaeeindex/settings/override_runner');
        }
        return $this->_overrideAlgoliaRunner;
    }

    public function shouldCheckPriceIndex()
    {
        if ($this->_checkPriceIndex === null) {
            $this->_checkPriceIndex = Mage::getStoreConfigFlag('aligent_algoliaeeindex/settings/check_price_index');
        }
        return $this->_checkPriceIndex;
    }

    public function shouldCheckStockIndex()
    {
        if ($this->_checkStockIndex === null) {
            $this->_checkStockIndex = Mage::getStoreConfigFlag('aligent_algoliaeeindex/settings/check_stock_index');
        }
        return $this->_checkStockIndex;
    }

    public function getRetryLimit()
    {
        if ($this->_retryLimit === null) {
            $this->_retryLimit = Mage::getStoreConfig('aligent_algoliaeeindex/settings/number_times_to_retry_events');
        }
        return $this->_retryLimit;
    }

    public function useCustomLog()
    {
        if ($this->_customLog === null) {
            $this->_customLogName = Mage::getStoreConfig('aligent_algoliaeeindex/settings/custom_log');

            if ($this->_customLogName === null) {
                $this->_customLog = false;
            } else {
                $this->_customLog = true;
            }
        }
        return $this->_customLog;
    }

    public function getCustomLogName()
    {
        $this->useCustomLog();
        return $this->_customLogName;
    }
}