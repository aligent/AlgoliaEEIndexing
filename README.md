# MagentoEE vs Algolia

This module has been created to rectify the compatibility issue between Algolia and Magento EE Indexing. 

The issue is that when a product is updated Magento EE updates its indexes asynchronously.
Therefore there is a chance that Algolia will process the update event for a product before the indexes have been updated
causing incorrect information to be sent to Algolia. 
  
The latest version of Algolia does not have the ability to re-process events,
they are always removed assuming they have been sent.
Therfore the queue processor has been overriden simply to update
events that were not successful utilising the retry functionality previously built into Algolia.
The previous functionality will retry events by default three times (this can be changed via an admin config setting).
If they have been retried the maximum amoutn then they are deleted.

*NOTE* Please check compatibility between your version of Algolia run function and the overriden one before enabling to confirm no

By Default no indexes are checked and the queue processor is not overriden, these need to be enabled in the admin section of Magento.

Available index checks:
 - Stock index
 - Price index

A function is available to be overriden to add custom index checks.