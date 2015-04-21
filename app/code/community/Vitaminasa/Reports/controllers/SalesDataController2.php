<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_SalesDataController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    private function getSalesCollection($parameters) {
        
        $fromDate = $parameters->getPost('date_from');
        $toDate = $parameters->getPost('date_to');
        
        if ( empty($fromDate) || strlen($fromDate) == 0 ) {
            $fromDate = '1900-01-01 00:00:00';   
        }
        
        if ( empty($toDate) || strlen($toDate) == 0 ) {
            $toDate = date('Y-m-d H:i:s');  
        }
        
        $status = $parameters->getPost('status');
        
        /* Get the collection */
        $ordersCollection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id');
            
        $ordersCollection->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
        
        if (!empty($status)) {
            $ordersCollection->addAttributeToFilter('status', array('in' => $status));
        }
        else {
            $ordersCollection->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
        }
        
        return $ordersCollection;
        
    }
    
    public function getSalesByDayAndHourAction() {
        
        $ordersCollection = $this->getSalesCollection($this->getRequest());
            
        $orders = array();
            
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByDayAndHourCallback')), array('orders' => &$orders));
        
        echo json_encode($orders);
        
        return $this;
        
    }
    
    public function getSalesByDayAndHourCallback($args) {
        
        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getBaseGrandTotal();
        
        $createdAt = $order->getCreatedAt();
        
        $weekday = date("N",strtotime($createdAt)) - 1;
        $hour = date("H",strtotime($createdAt));
        
        if ( isset($args['orders'][$weekday][$hour]) ) {
            $args['orders'][$weekday][$hour]['amount'] += $order->getBaseGrandTotal();
            $args['orders'][$weekday][$hour]['qty']++;
        }
        else {
            $args['orders'][$weekday][$hour]['amount'] = $order->getBaseGrandTotal();
            $args['orders'][$weekday][$hour]['qty'] = $order->getBaseGrandTotal();
        }
        
    }
    
    public function getSalesByDateAction() {
            
        $ordersCollection = $this->getSalesCollection($this->getRequest());
            
        $orders = array();
            
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByDateCallback')), array('orders' => &$orders));
        
        echo json_encode($orders);
        
        return $this;
    }
    
    public function getSalesByDateCallback($args) {
        
        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getBaseGrandTotal();
        
        $createdAt = $order->getCreatedAt();
        
        $month = date("m",strtotime($createdAt));
        $year = date("Y",strtotime($createdAt));
        
        if ( isset($args['orders'][$year][$month]) ) {
            $args['orders'][$year][$month]['amount'] += $order->getBaseGrandTotal();
            $args['orders'][$year][$month]['qty']++;
        }
        else {
            $args['orders'][$year][$month]['amount'] = $order->getBaseGrandTotal();
            $args['orders'][$year][$month]['qty'] = $order->getBaseGrandTotal();
        }
        
    }

    public function getSalesByStateAction() {
        
        $sales = Mage::getModel('sales/order')->getCollection()
                                                ->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
                                                
        $response = array();
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($sales->getSelect(), array(array($this, 'getSalesByStateActionCallback')), array('response' => &$response));
  
        echo json_encode($response);
        
        return $this;
    }
    
    public function getSalesByStateActionCallback($args) {

        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getId();
        
        $order = Mage::getModel("sales/order")->load($orderId); 
        
        $orderData = $order->getShippingAddress()->getData();
        
        if ( !empty($orderData['region_id'])) {
            if ( isset($args['response'][$orderData['region_id']])) {
                $args['response'][$orderData['region_id']]['count']++;
                $args['response'][$orderData['region_id']]['amount'] += $order->getGrandTotal();
            } else {
                $args['response'][$orderData['region_id']] = array('id' => $orderData['region_id'],
                                                                    'count' => 1, 
                                                                    'amount' => $order->getGrandTotal(),
                                                                    'region' => $orderData["region"] );
            }
        }

    }
    
    public function getSalesByBrandAction() {
        
        $ordersCollection = $this->getSalesCollection($this->getRequest());
                                                
        $response = array();
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByBrandCallback')), array('response' => &$response));
  
        echo json_encode($response);
        
        return $this;
    }
    
    public function getSalesByBrandCallback($args) {

        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getId();
        
        //$order = Mage::getModel("sales/order")->load($orderId); 
        
        $items = $order->getAllVisibleItems();
        
        $productsId = array();
   
        foreach($items as $anItem) {
            $productsId[] = $anItem->getProductId();
        }
        
        //$productCollection = Mage::getResourceModel('catalog/product_collection')
        //    ->addFieldsToFilter('entity_id', array($productsId))
        //    ->addAttributeToSelect(array('entity_id, marca'));
            //->addFieldsToFilter('entity_id', array($productsId))
            //->addAttributeToSelect('entity_id');
        
        //$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        
        $productCollection = Mage::getModel('catalog/product')
				->getCollection()                
				->addAttributeToFilter('entity_id', array('in' => $productsId))
				//->addAttributeToSelect($attributes);
				->addAttributeToSelect(array('entity_id', 'marca', 'price'));
         
            
            //$product = Mage::getModel('catalog/product')->load($anItem->getProductId());
            //Zend_Debug::dump($product);
            //printf("<p>%d: %s</p>", $product->getEntityId(), $product->getAttributeText('marca'));
            
        foreach ( $productCollection AS $aProduct ) {
            
            $brandId = intval($aProduct->getMarca());
            
            $brand = $aProduct->getAttributeText('marca');
            
            if ( !empty($brand) ) {
            
                if ( isset($args['response'][$brandId])) {
                    $args['response'][$brandId]['count']++;
                    $args['response'][$brandId]['amount'] += $aProduct->getPrice();
                } else {
                    $args['response'][$brandId]['brand'] = $brand;
                    $args['response'][$brandId]['count'] = 1;
                    $args['response'][$brandId]['amount'] = $aProduct->getPrice();
                }
            }
            
        }

    }
    
}

?>