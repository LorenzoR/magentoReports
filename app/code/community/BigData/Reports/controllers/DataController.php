<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class BigData_Reports_DataController extends Mage_Core_Controller_Front_Action {
    
    public function getPaymentMethodAction() {
        
        $query = "SELECT count(*) AS qty,
                        SUM(base_grand_total) AS amount,
                        sales_flat_order_payment.method
                    FROM sales_flat_order, sales_flat_order_address, directory_country_region,
                    		sales_flat_order_payment
                    WHERE sales_flat_order.entity_id = sales_flat_order_address.parent_id
                    	AND sales_flat_order_address.region_id = directory_country_region.region_id
                    	AND sales_flat_order_payment.entity_id = sales_flat_order.entity_id
                    	AND directory_country_region.country_id =  'AR'
                    	AND sales_flat_order.status IN ('processing','complete')
                    GROUP BY sales_flat_order_payment.method";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(utf8_encode($row['method']), $row['qty'], $row['amount']);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getCustomerRankingAction() {
        
        $customers = $this->getAllCustomersFullname();
        
        $query = "SELECT customer_id, COUNT(*) AS qty, SUM(base_grand_total) AS amount
                    FROM sales_flat_order
                    WHERE sales_flat_order.customer_id IS NOT NULL
                        AND status IN ('processing','complete')
                    GROUP BY customer_id
                    ORDER BY amount DESC";
        
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        $response["data"] = array();
        
        $i = 1;
        
        while ($row = $readResult->fetch() ) {
            
            $response["data"][] = array(    $i++,
                                            htmlentities($customers[$row['customer_id']]), 
                                            $row['qty'], 
                                            $row['amount'],
                                            $row['amount'] / $row['qty']);
        }
        
        echo json_encode($response);
        
        return $this;
        
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
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array( "method" => $row["method"],
                                 "amount" => $row["amount"], 
                                 "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
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
    
    public function getSalesByYearAction() {
        
        $query = "  SELECT  YEAR(created_at) AS year, 
                    	    SUM(base_grand_total)  AS amount,
                    		COUNT(*) AS qty
                    FROM   sales_flat_order 
                    WHERE status IN ('processing','complete')
                    GROUP BY YEAR(created_at)
                    ORDER BY year ASC";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[$row["year"]][$row["month"]] = array( "year" => $row["year"],
                                                            "amount" => $row["amount"], 
                                                            "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getSalesByDateAction() {
        
        $query = "  SELECT  YEAR(created_at) AS year, 
                            LPAD(MONTH(created_at), 2, '0') AS month,
                    	    SUM(base_grand_total)  AS amount,
                    		COUNT(*) AS qty
                    FROM   sales_flat_order 
                    WHERE status IN ('processing','complete')
                    GROUP BY YEAR(created_at), LPAD(MONTH(created_at), 2, '0')
                    ORDER BY year ASC, month ASC";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[$row["year"]][$row["month"]] = array( "year" => $row["year"],
                                                            "month" => $row["month"],
                                                            "amount" => $row["amount"], 
                                                            "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getCustomerRegistrationDateAction() {
        
        $query = "SELECT CONCAT(CONCAT(LPAD(MONTH(customer_entity.created_at), 2, '0'), '-'), YEAR(customer_entity.created_at)) AS registrationDate,
                    		COUNT(*) AS qty
                    FROM customer_entity
                    GROUP BY CONCAT(CONCAT(YEAR(customer_entity.created_at), '-'), LPAD(MONTH(customer_entity.created_at), 2, '0'))
                    ORDER BY CONCAT(CONCAT(YEAR(customer_entity.created_at), '-'), LPAD(MONTH(customer_entity.created_at), 2, '0'))";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array( "registrationDate" => $row["registrationDate"],
                                                            "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getCohortDataAction() {
    
        /*$customers = $this->getAllCustomersCreationDate();
        
        var_dump($customers);*/
        
        /*
        $customersByDate = array();
        
        foreach ( $customers AS $id => $aCustomer ) {
            if ( isset($customersByDate[date("Y", $aCustomer)][date("m", $aCustomer)]) ) {
                $customersByDate[date("Y", $aCustomer)][date("m", $aCustomer)]++;
            }
            else {
                $customersByDate[date("Y", $aCustomer)][date("m", $aCustomer)] = 1; 
            }
            
        }*/
        
        /*
        $query = "  SELECT sales_flat_order.customer_id, 
                           sales_flat_order.created_at, 
                           base_grand_total  AS amount, 
                           cohorts.cohortdate,
                    	   CONCAT(CONCAT(YEAR(cohorts.cohortdate), '-'), LPAD(MONTH(cohorts.cohortdate), 2, '0')) AS cohortDateKey,
                    	   TIMESTAMPDIFF(MONTH, cohorts.cohortdate, sales_flat_order.created_at) + 1 AS cohortPeriod
                    FROM   sales_flat_order 
                           JOIN (SELECT customer_id, 
                                        Min(created_at) AS cohortDate 
                                 FROM   sales_flat_order 
                    			 WHERE sales_flat_order.status IN ('processing','complete')
                                 GROUP  BY customer_id) AS cohorts 
                             ON sales_flat_order.customer_id = cohorts.customer_id
                    		 WHERE sales_flat_order.status IN ('processing','complete')
                    			AND sales_flat_order.base_subtotal_invoiced IS NOT NULL
                    			AND sales_flat_order.base_discount_invoiced IS NOT NULL
                    		ORDER BY cohortDateKey ASC, cohortPeriod ASC";
        
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            if ( isset($response[$row['cohortDateKey']][$row['cohortPeriod']]) ) {
                $response[$row['cohortDateKey']][$row['cohortPeriod']] += $row['amount'];
            }
            else {
                $response[$row['cohortDateKey']][$row['cohortPeriod']] = $row['amount'];
            }
            
        }
        
        foreach ( $response AS $aResponse ) {
            foreach ( $aResponse AS $aSubResponse ) {
                $aResponse[0] = $aSubResponse;
                break;
            }
        }
        
        echo json_encode($response);
        
        return $this;
        */
        
        $query = "  SELECT 
                           SUM(base_grand_total)  AS amount, 
                    	   YEAR(cohorts.cohortdate) AS cohortDateKey,
                    	   YEAR(sales_flat_order.created_at) - YEAR(cohorts.cohortdate) + 1 AS cohortPeriod,
                    	   COUNT(distinct sales_flat_order.customer_id) AS customers
                    FROM   sales_flat_order 
                           JOIN (SELECT customer_id, 
                                        Min(created_at) AS cohortDate 
                                 FROM   sales_flat_order 
                    			 WHERE status IN ('processing','complete')
                    			 and customer_id is not null
                                 GROUP  BY customer_id) AS cohorts 
                             ON sales_flat_order.customer_id = cohorts.customer_id
                    		 WHERE status IN ('processing','complete')
                    GROUP BY YEAR(cohorts.cohortdate), YEAR(sales_flat_order.created_at) - YEAR(cohorts.cohortdate) + 1
                    ORDER BY cohortDateKey, cohortPeriod";
        
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        $initialDate = null;
        
        while ($row = $readResult->fetch() ) {
            
            if ( $initialDate == null ) {
                $initialDate = $row["cohortDateKey"];
            }
            
            $index = $row["cohortDateKey"] - $initialDate;
            
            $response[$index][$row["cohortPeriod"]] = array(    
                                    "amount" => $row["amount"],
                                    "cohortDateKey" => $row["cohortDateKey"],
                                    "cohortPeriod" => $row["cohortPeriod"],
                                    "customers" => $row["customers"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getAllShippingAddressActionBAK() {
        
        $orders = Mage::getModel("sales/order")->getCollection();
                   //->addAttributeToSelect('*');
                   
        $orders->setPageSize(100);
        
        $pages = $orders->getLastPageNumber();
        $currentPage = 1;


        $response = array();

        do {
            $orders->setCurPage($currentPage);
            $orders->load();
        
            foreach($orders as $anOrder) {
                //display shipping address
                //print_r($order->getShippingAddress()->getData());
                $orderData = $anOrder->getShippingAddress()->getData();
                //$response[] = array();
                //$response["region"] = $orderData["region"];
                //$response["street"] = $orderData["street"];
                    
                if ( $orderData["region"] == "Buenos Aires Gba (hasta 60km De Capital Federal)" ) {
                    $orderData["region"] = "Buenos Aires";
                }
                    
                if ( !empty($orderData["region"]) && !empty($orderData["city"]) && !empty($orderData["street"]) ) { 
                    $response[] = ucwords(strtolower($orderData["street"])).", ".ucwords(strtolower($orderData["city"])).", ".ucwords(strtolower($orderData["region"]));
                }
            }
            
            $currentPage++;
            
            //clear collection and free memory
            $orders->clear();
        } while ($currentPage <= $pages);
        
        $uniqueResult = array_unique($response);
        
        echo "<table>";
        
        foreach ($uniqueResult AS $aResult) {
            echo "<tr>";
            echo "<td>".utf8_decode($aResult)."</tr>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        //echo json_encode($uniqueResult);
        
        return $this;
  
        
    }
    
    public function getSalesByDayAction() {
        
        $query = "  SELECT date(sales_flat_order.created_at), 
            	            COUNT(*) AS qty,
                            SUM(sales_flat_order.base_grand_total) AS amount
                    FROM   sales_flat_order 
                    WHERE sales_flat_order.status IN ('processing','complete')
                    GROUP BY date(sales_flat_order.created_at)
                    ORDER BY date(sales_flat_order.created_at) ASC";
    }
    
    public function getSalesByDaynameAction() {
        
        $query = "SELECT DAYNAME( sales_flat_order.created_at ) , 
                            COUNT( * ) AS qty, 
                            SUM( sales_flat_order.base_grand_total ) AS amount
                    FROM sales_flat_order
                    WHERE sales_flat_order.status IN ('processing','complete')
                    GROUP BY DAYNAME( sales_flat_order.created_at ) 
                    ORDER BY DAYNAME( sales_flat_order.created_at ) ASC";
    }
    
    public function getSalesByDayAndHourAction() {
        
        $parameters = $this->parseParameters($this->getRequest()->getParams());
        
        $whereClause = "";
        
        if ( isset($parameters["status"]) && !empty($parameters["status"]) ) {
            $whereClause = "WHERE status IN ".$parameters["status"];
        }
        
        if ( isset($parameters["dateFrom"]) && !empty($parameters["dateFrom"]) ) {
            if ( empty($whereClause)) {
                $whereClause = "WHERE sales_flat_order.created_at >= '".$parameters["dateFrom"]."'";
            }
            else {
                $whereClause .= "AND sales_flat_order.created_at >= '".$parameters["dateFrom"]."'";
            }
            
        }
        
        if ( isset($parameters["dateTo"]) && !empty($parameters["dateTo"]) ) {
            if ( empty($whereClause)) {
                $whereClause = "WHERE sales_flat_order.created_at <= '".$parameters["dateTo"]."'";
            }
            else {
                $whereClause .= "AND sales_flat_order.created_at <= '".$parameters["dateTo"]."'";
            }
            
        }
        
        if ( isset($parameters["paymentMethod"]) && !empty($parameters["paymentMethod"]) ) {
            //$whereClause = "WHERE status IN ".$parameters["paymentMethod"];
        }
        
        $query = "  SELECT WEEKDAY( sales_flat_order.created_at ) AS weekday, 
                        HOUR( sales_flat_order.created_at ) AS hour, 
                        COUNT( * ) AS qty, SUM( sales_flat_order.base_grand_total ) AS amount
                    FROM sales_flat_order "
                    .$whereClause
                    ." GROUP BY WEEKDAY( sales_flat_order.created_at ) , HOUR( sales_flat_order.created_at ) 
                    ORDER BY WEEKDAY( sales_flat_order.created_at ) ASC ";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(    "weekday" => $row["weekday"],
                                    "hour" => $row["hour"],
                                    "amount" => $row["amount"], 
                                    "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
    }
    
    public function getAvgByCustomerAction() {
        
        $query = "  SELECT  CONCAT(LPAD(MONTH(created_at), 2, '0'), CONCAT('-', YEAR(created_at))) AS period,
                    		COUNT(distinct customer_id) AS qty, 
                    		SUM(base_grand_total) AS amount,
                            (SUM(base_grand_total) / COUNT(distinct customer_id) ) AS average
                    FROM sales_flat_order
                        WHERE status IN ('processing','complete')
                    GROUP BY CONCAT(LPAD(MONTH(created_at), 2, '0'), CONCAT('-', YEAR(created_at)))
                    ORDER BY CONCAT(CONCAT(YEAR(created_at), '-'), LPAD(MONTH(created_at), 2, '0'))";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(    "period" => $row["period"],
                                    "average" => $row["average"]);
        }
        
        echo json_encode($response);
        
        return $this;
    }
    
    public function getNewCustomersAction() {
        
        $query = "  SELECT count(*) AS qty,
                    		CONCAT(CONCAT(LPAD(MONTH(customer_entity.created_at), 2, '0'), '-'), YEAR(customer_entity.created_at)) AS period
                    FROM `customer_entity`
                    GROUP BY CONCAT(CONCAT(LPAD(MONTH(customer_entity.created_at), 2, '0'), '-'), YEAR(customer_entity.created_at))
                    ORDER BY CONCAT(CONCAT(YEAR(customer_entity.created_at), '-'), LPAD(MONTH(customer_entity.created_at), 2, '0'))";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(    "period" => $row["period"],
                                    "qty" => $row["qty"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getAverageOrderSizeAction() {
        
        $query = "SELECT AVG(amountAux) AS amount, 
							COUNT(*) AS qty,
                    		AVG(qtyPerOrderAux) AS qtyPerOrder,
							CONCAT(CONCAT(LPAD(MONTH(tableAux.periodAux), 2, '0'), '-'), YEAR(tableAux.periodAux)) AS period
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
                    GROUP BY CONCAT(CONCAT(LPAD(MONTH(tableAux.periodAux), 2, '0'), '-'), YEAR(tableAux.periodAux))
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
    
    public function getBestsellerProductsAction() {
        
        $bestSellers = $this->getBestsellerProducts();
        
        
        $response = array();
        
        foreach ( $bestSellers AS $aBestSeller ) {
            var_dump($aBestSeller);
            //echo "<p>".$aBestSeller->getName()."</p>";
            /*
            $response[] = array(    "period" => $row["period"],
                                    "qty" => $row["qty"],
                                    "amount" => $row["amount"],
                                    "qtyPerOrder" => $row["qtyPerOrder"]); */
        }
        
        echo json_encode($response);
        
        return $this;
    }
    
    private function getBestsellerProducts() {
        
        echo "http://www.zodiacmedia.co.uk/blog/magento-bestselling-products";
        
        $storeId = (int) Mage::app()->getStore()->getId();
 
        // Date
        $date = new Zend_Date();
        $toDate = $date->setDay(1)->getDate()->get('Y-MM-dd');
        $fromDate = $date->subMonth(1)->getDate()->get('Y-MM-dd');
 
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addStoreFilter()
            ->addPriceData()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->setPageSize(600);
 
        $collection->getSelect()
            ->joinLeft(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                "e.entity_id = aggregation.product_id 
                AND aggregation.store_id={$storeId} 
                AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'
                AND aggregation.qty_ordered IS NOT NULL",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->group('e.entity_id')
            ->order(array('sold_quantity DESC', 'e.created_at'));
 
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
 
        return $collection;
    }
    
    public function getAllShippingAddressAction() {
        
        echo "Id,Calle,Ciudad,Provincia,Pais<br />";
        
        $sales = Mage::getModel('sales/order')->getCollection()
                                                ->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
                                                
        $response = array();
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($sales->getSelect(), array(array($this, 'getAllShippingAddressCallback')), array('response' => &$response));
  
        
    }
    
    public function getAllShippingAddressCallback($args) {

        $order = Mage::getModel('sales/order'); // get customer model
        $order->setData($args['row']); // map data to customer model
        $orderId =  $order->getId();
        
        $order = Mage::getModel("sales/order")->load($orderId); 
        
        $orderData = $order->getShippingAddress()->getData();
        
        if ( htmlentities($orderData["region_id"]) === "534" ) {
            $orderData["region"] = "Buenos Aires";
        }
                    
        if ( !empty($orderData["region"]) && !empty($orderData["city"]) && !empty($orderData["street"]) ) { 
            $args['response'][] = ucwords(strtolower(htmlentities($orderData["street"]))).", ".ucwords(strtolower(htmlentities($orderData["city"]))).", ".ucwords(strtolower(htmlentities($orderData["region"])));
        }
        
        if ( $orderData['country_id'] === "Ar" ) {
            $country = "Argentina";
        }
        
        $countryModel = Mage::getModel('directory/country')->loadByCode($orderData['country_id']);
        
        echo "\"".$orderId."\",\"".ucwords(strtolower(htmlentities($orderData["street"])))."\",\"".ucwords(strtolower(htmlentities($orderData["city"])))."\",\"".ucwords(strtolower(htmlentities($orderData["region"])))."\",\"".$countryModel->getName()."\"<br />";

    }
    
    public function getAllOrdersAndItemsAction() {
        
        echo "Lista de Ordenes: <br />";
        // get customer collection
        $sales = Mage::getModel('sales/order')->getCollection()
                                                ->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($sales->getSelect(), array(array($this, 'customerCallback')));
    }
    
    public function getAllOrdersAction($args) {
        
        echo "Lista de Ordenes: <br />";
        // get customer collection
        $sales = Mage::getModel('sales/order')->getCollection();
        
        $datesArray = array();
        
        $args["status"] = array('complete');
        
        if ( isset($args["date_from"])) {
            $datesArray["from"] = $args["date_from"];
        }
        
        if ( isset($args["date_to"])) {
            $datesArray["to"] = $args["date_to"];
        }
        
        if ( !empty($datesArray) ) {
            $sales->addAttributeToFilter('created_at', $datesArray);
        }
        
        if ( isset($args["payment_method"])) {
            $sales->join(array('payment'=>'sales/order_payment'),'main_table.entity_id=parent_id','method');
            $sales->addFieldToFilter('method', array('in' => $args["payment_method"]));
        }
        
        if ( isset($args["status"])) {
            $sales->addAttributeToFilter('status', array('in' => $args["status"]));
            //$sales->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
        }
        
        if ( isset($args["state"])) {
            
        }
                                                //->addAttributeToFilter('status', array('in' => array('complete', 'processing')));
    
        // call iterator walk method with collection query string and callback method as parameters
        Mage::getSingleton('core/resource_iterator')->walk($sales->getSelect(), array(array($this, 'getAllOrdersCallback')), array('response' => &$response));
    
        echo count($response);
        
    }
    
    public function getAllOrdersCallback($args) {
        
        $customer = Mage::getModel('sales/order'); // get customer model
        $customer->setData($args['row']); // map data to customer model
        $orderId =  $customer->getId();
        
        $order = Mage::getModel("sales/order")->load($orderId); 
        
        $args['response'][$orderId] = $orderId;
    }
    
    public function customerCallback($args) {
        //echo "CALLBACK <br />";
        $customer = Mage::getModel('sales/order'); // get customer model
        $customer->setData($args['row']); // map data to customer model
        $orderId =  $customer->getId();
        
        $order = Mage::getModel("sales/order")->load($orderId); 
        
        //load order by order id 
        $ordered_items = $order->getAllVisibleItems(); 
        
        //echo "Order ".$orderId."(".$order->getStatus().")";
        
        //echo "<ul>";
        
        foreach($ordered_items as $item){     
            //item detail     
            //echo $item->getItemId(); 
            //product id     
            //echo $item->getSku();     
            //echo $item->getQtyOrdered();
            //ordered qty of item     
            //echo "<li>".$item->getName()."</li>";     
            // etc. 
            //echo $orderId.",".htmlentities($item->getName()).",".htmlentities($item->getAttributeText('marca'))."<br />";
            
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            
            echo $orderId.",".$item->getProductId().",".htmlentities($product->getName()).",".htmlentities($product->getAttributeText('marca'))."<br />";
            
            //var_dump($product); 
        }
        
        //echo "</ul>";
        
        //$customer->setFirstname(strtoupper($customer->getFirstname())); // set value of firstname attribute
        //$customer->getResource()->saveAttribute($customer, 'firstname'); // save only changed attribute instead of whole object
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
    
    private function getAllCustomersFullname() {
        
        $collection = Mage::getModel('customer/customer')->getCollection()
           ->addAttributeToSelect('firstname')
           ->addAttributeToSelect('lastname');
        
        $customers = array();
        
        foreach ($collection as $item) {
            $customers[$item->getEntityId()] = ucwords(strtolower($item->getName())); // Full Name
        }
        
        return $customers;
    }
    
    private function getAllCustomersCreationDate() {
        
        $collection = Mage::getModel('customer/customer')->getCollection();
        
        $customers = array();
        
        foreach ($collection as $item) {
            $customers[$item->getEntityId()] = $item->getCreatedAtTimestamp(); // Created at
        }
        
        return $customers;
    }
    
    private function parseParameters($parameters) {
        
        $response = array();
        
        $response["dateFrom"] = $parameters["date_from"];
        $response["dateTo"] = $parameters["date_to"];
        
        if ( isset($parameters["status"]) && !empty($parameters["status"])) {
        
            $statusQuery = '(';
            
            foreach ( $parameters["status"] AS $aStatus ) {
                $statusQuery .= "'".$aStatus."', ";    
            }
            
            $statusQuery = substr($statusQuery, 0, -2);
            
            $statusQuery .= ")";
        
        }
        
        $response["status"] = $statusQuery;
        
        if ( isset($parameters["payment_method"]) && !empty($parameters["payment_method"])) {
        
            $paymentMethodQuery = '(';
            
            foreach ( $parameters["payment_method"] AS $aMethod ) {
                $paymentMethodQuery .= "'".$aMethod."', ";    
            }
            
            $paymentMethodQuery = substr($paymentMethodQuery, 0, -2);
            
            $paymentMethodQuery .= ")";
        
        }
        
        $response["paymentMethod"] = $paymentMethodQuery;
        
        return $response;
    }
}

?>