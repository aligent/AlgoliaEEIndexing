<?php

class Aligent_AlgoliaEEIndex_Helper_Log extends Mage_Core_Helper_Abstract
{
    public function logIndex($vMessage)
    {
        if (Mage::helper('aligent_algoliaeeindex')->useCustomLog()) {
            Mage::log($vMessage, false, Mage::helper('aligent_algoliaeeindex')->getCustomLogName());
        } else {
            Mage::helper('algoliasearch/logger')->log($vMessage);
        }
    }
}