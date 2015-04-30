<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_ProductsDataController extends Mage_Core_Controller_Front_Action {
    
    public function getMostViewedProductsAction() {

        $storeId    = Mage::app()->getStore()->getId();
        $productCollection = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name', 'price', 'small_image'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addViewsCount();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
 
        $productCollection->setPageSize(15)->setCurPage(1);
        
        $response = array();
        
        foreach ( $productCollection->getItems() as $aProduct ) {
            $response[] = array('name' => $aProduct->getName(),
                                 'views' => $aProduct->getViews() );
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getBestSellingProductsAction() {
 
        $storeId = Mage::app()->getStore()->getId();
        $productCollection = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect(array('name'))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc'); // most best sellers on top
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
 
        $productCollection->setPageSize(15)->setCurPage(1);
        
        $response = array();
        
        foreach ( $productCollection->getItems() as $aProduct ) {
            $response[] = array('name' => $aProduct->getName(),
                                 'qty' => $aProduct->getOrderedQty() );
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
}

?>