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
        
        // Date parameters
        $fromDate = $parameters->getPost('date_from');
        $toDate = $parameters->getPost('date_to');
        
        if ( empty($fromDate) || strlen($fromDate) == 0 ) {
            $fromDate = '1900-01-01 00:00:00';   
        }
        
        if ( empty($toDate) || strlen($toDate) == 0 ) {
            $toDate = date('Y-m-d H:i:s');  
        }
        
        $status = $parameters->getPost('status');
        
        // Get the collection
        $ordersCollection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('base_grand_total');
            
        $ordersCollection->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
        
        // Status parameter
        if (!empty($status)) {
            $ordersCollection->addAttributeToFilter('status', array('in' => $status));
        }
        else {
            $ordersCollection->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
        }
        
        return $ordersCollection;
        
    }
    
    public function getAverageOrderAction() {
        
        $query = "SELECT AVG(amountAux) AS amount, 
							COUNT(*) AS qty,
                    		AVG(qtyPerOrderAux) AS qtyPerOrder,
							CONCAT(CONCAT(YEAR(tableAux.periodAux), '-'), LPAD(MONTH(tableAux.periodAux), 2, '0')) AS period
                    FROM ( 
                    SELECT sales_flat_order.entity_id AS orderIdAux,
                    		sales_flat_order.base_grand_total AS amountAux,
                    		COUNT(sales_flat_order_item.item_id) AS qtyPerOrderAux,
                    		sales_flat_order.created_at AS periodAux
                        FROM sales_flat_order, sales_flat_order_item
                        WHERE sales_flat_order.entity_id = sales_flat_order_item.order_id
                            AND status IN ('processing','complete')
                    	GROUP BY sales_flat_order.entity_id
                    	) AS tableAux
                    GROUP BY CONCAT(CONCAT(YEAR(tableAux.periodAux), '-'), LPAD(MONTH(tableAux.periodAux), 2, '0'))
					ORDER BY CONCAT(CONCAT(YEAR(tableAux.periodAux), '-'), LPAD(MONTH(tableAux.periodAux), 2, '0'))";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(    "period" => $row["period"],
                                    "qty" => $row["qty"],
                                    "amount" => $row["amount"],
                                    "qtyPerOrder" => $row["qtyPerOrder"]);
        }
        
        echo json_encode($response);
        
        return $this;
    }
    
    public function getSalesByPaymentMethodAction() {
        
        $query = "  SELECT  sales_flat_order_payment.method,
                			SUM(base_grand_total) AS amount,
                			COUNT(*) AS qty
                	FROM   sales_flat_order, sales_flat_order_payment
                	WHERE sales_flat_order.entity_id = sales_flat_order_payment.parent_id 
                		AND status IN ('processing','complete')
                	GROUP BY sales_flat_order_payment.method
                	ORDER BY amount DESC";
                    
        $readResult = $this->executeQuery($query);
        
        $paymentMethods = $this->getPaymentMethods();
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            
            if ( $row["method"] != "mercadopago" ) {
                $paymentMethod = $paymentMethods[$row["method"]];
            }
            else {
                $paymentMethod = array("label" => "Mercado Pago");
            }
            
            $response[] = array( "method" => $paymentMethod["label"],
                                 "amount" => $row["amount"], 
                                 "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    private function getPaymentMethods() {
        
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        
        $response = array();
 
	   foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $response[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode,
            );
        }
        
        return $response;
    }
    
    public function getSalesByYearAction() {
            
        $ordersCollection = $this->getSalesCollection($this->getRequest());
            
        $orders = array();
            
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByYearCallback')), array('orders' => &$orders));
        
        echo json_encode($orders);
        
        return $this;
    }
    
    public function getSalesByYearCallback($args) {
        
        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        
        $createdAt = $order->getCreatedAt();
        
        $year = date("Y",strtotime($createdAt));
        
        if ( isset($args['orders'][$year]) ) {
            $args['orders'][$year]['amount'] += $order->getBaseGrandTotal();
            $args['orders'][$year]['qty']++;
        }
        else {
            $args['orders'][$year]['amount'] = $order->getBaseGrandTotal();
            $args['orders'][$year]['qty'] = 1;
        }
        
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
    
    public function getSalesByDayAction() {
            
        $ordersCollection = $this->getSalesCollection($this->getRequest());
            
        $orders = array();
            
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByDayCallback')), array('orders' => &$orders));
        
        echo json_encode($orders);
        
        return $this;
    }
    
    public function getSalesByDayCallback($args) {
        
        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getBaseGrandTotal();
        
        $createdAt = $order->getCreatedAt();
        
        $day = ltrim(date("d",strtotime($createdAt)), '0');
        
        if ( isset($args['orders'][$day]) ) {
            $args['orders'][$day]['amount'] += $order->getBaseGrandTotal();
            $args['orders'][$day]['qty']++;
        }
        else {
            $args['orders'][$day]['amount'] = $order->getBaseGrandTotal();
            $args['orders'][$day]['qty'] = 1;
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
            $args['orders'][$year][$month]['qty'] = 1;
        }
        
    }

    public function getSalesByStateAction() {
        
        $sales = Mage::getModel('sales/order')->getCollection()
                                                ->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
                                                
        //echo $sales->getSelect()->__toString(); exit;
                                                
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
        
        $query = "SELECT catalog_product_flat_1.marca_value AS brand, 
                        SUM( base_grand_total ) AS amount, 
                        COUNT( * ) AS qty
                    FROM sales_flat_order, sales_flat_order_item, catalog_product_flat_1
                    WHERE sales_flat_order.entity_id = sales_flat_order_item.order_id
                    AND catalog_product_flat_1.entity_id = sales_flat_order_item.product_id
                    AND sales_flat_order.status
                    IN ( 'processing',  'complete' )
                    AND catalog_product_flat_1.marca_value IS NOT NULL 
                    GROUP BY catalog_product_flat_1.marca
                    ORDER BY  `amount` DESC
                    LIMIT 0, 20;";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array( "brand" => $row["brand"],
                                 "amount" => $row["amount"], 
                                 "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getSalesByBrandActionBAK() {
        
        $ordersCollection = $this->getSalesCollection($this->getRequest());
                                                
        $response = array();
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByBrandCallback')), array('response' => &$response));
  
        /*$productCollection = Mage::getModel('catalog/product')
				->getCollection()                
				->addAttributeToFilter('entity_id', array('in' => $response))
				->addAttributeToSelect(array('entity_id', 'marca', 'price'));
				*/
		$productsId = $response['productsId'];
		$qty = $response['qty'];
				
		$response = array();
     
        //Mage::getSingleton('core/resource_iterator')->walk($ordersCollection->getSelect(), array(array($this, 'getSalesByBrandCallback2')), array('response' => &$response));
     
        $productCollection = Mage::getModel('catalog/product')
				->getCollection()                
				->addAttributeToFilter('entity_id', array('in' => $productsId))
				//->addAttributeToSelect($attributes);
				->addAttributeToSelect(array('entity_id', 'marca', 'price'));
     
        foreach ( $productCollection AS $aProduct ) {
            
            $productId = $aProduct->getId();
            
            $brandId = intval($aProduct->getMarca());
            
            $brand = $aProduct->getAttributeText('marca');
            
            if ( !empty($brand) ) {
            
                if ( isset($args['response'][$brandId])) {
                    $response[$brandId]['count'] += $qty[$productId];
                    $response[$brandId]['amount'] += $aProduct->getPrice();
                } else {
                    $response[$brandId]['brand'] = $brand;
                    $response[$brandId]['count'] = $qty[$productId];
                    $response[$brandId]['amount'] = $aProduct->getPrice();
                }
            }
            
        }
     
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
            //$productsId[] = $anItem->getProductId();
            $productId = $anItem->getProductId();
            $args['response']['productsId'][] = $productId;
            
            if ( isset($args['response']['qty'][$productId])) {
                $args['response']['qty'][$productId]++;
            }
            else {
                $args['response']['qty'][$productId] = 1;
            }
        }
        
        //$productCollection = Mage::getResourceModel('catalog/product_collection')
        //    ->addFieldsToFilter('entity_id', array($productsId))
        //    ->addAttributeToSelect(array('entity_id, marca'));
            //->addFieldsToFilter('entity_id', array($productsId))
            //->addAttributeToSelect('entity_id');
        
        //$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        
        /*$productCollection = Mage::getModel('catalog/product')
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
            
        } */

    }
    
    public function getSalesByBrandCallback2($args) {

        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getId();
        
        //$order = Mage::getModel("sales/order")->load($orderId); 
        
        $items = $order->getAllVisibleItems();
        
        $productsId = array();
   
        foreach($items as $anItem) {
            //$productsId[] = $anItem->getProductId();
            $args['response'][] = $anItem->getProductId();
        }
        
        //$productCollection = Mage::getResourceModel('catalog/product_collection')
        //    ->addFieldsToFilter('entity_id', array($productsId))
        //    ->addAttributeToSelect(array('entity_id, marca'));
            //->addFieldsToFilter('entity_id', array($productsId))
            //->addAttributeToSelect('entity_id');
        
        //$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        
        /*$productCollection = Mage::getModel('catalog/product')
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
            
        } */

    }
    
    private function executeQuery($query) {
        
         /**
         * Get the resource model
         */
        $resource = Mage::getSingleton('core/resource');
         
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
        
        $readResult = $readConnection->query($query);
        
        return $readResult;
        
    }
    
}

?>