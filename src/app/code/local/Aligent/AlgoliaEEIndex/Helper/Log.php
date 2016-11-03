<?php

class Aligent_AlgoliaEEIndex_Helper_Log extends Mage_Core_Helper_Abstract
{
    public function logIndex($vMessage)
    {
        Mage::log($vMessage, false, 'algoliasearch_index_custom.log');
    }
}