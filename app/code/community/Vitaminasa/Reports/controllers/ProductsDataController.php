<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_ProductsDataController extends Mage_Core_Controller_Front_Action {
    
    public function getMostViewedProductsAction() {

        $storeId    = Mage::app()->getStore()->getId();
        $products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addViewsCount();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
 
        $products->setPageSize(5)->setCurPage(1);
        
        foreach ( $products->getItems() as $aProduct ) {
            printf("%s - %d<br>", $aProduct->getName(), $aProduct->getViews());
        }
        
        
    }
    
    public function getBestSellingProductsAction() {
 
        $storeId = Mage::app()->getStore()->getId();
        $products = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc'); // most best sellers on top
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
 
        $products->setPageSize(3)->setCurPage(1);
        
        foreach ( $products->getItems() as $aProduct ) {
            printf("%s - %d<br>", $aProduct->getName(),  $aProduct->getOrderedQty());
        }
        
    }
    
}

?>