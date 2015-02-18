<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class BigData_HelloWorld_IndexController extends Mage_Core_Controller_Front_Action {

    /* 
    * this method privides default action. 
    */ 
    public function indexAction() { 
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByStateAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function customerRankingAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function cohortAnalysisAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByDateAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByYearAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByDayAndHourAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function avgSalesByCustomerAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function newCustomersAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function averageOrderSizeAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function averageOrderVolumeAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByPaymentMethodAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesByBrandAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesMapAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function testAction() { 
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function salesPerMonthAction() { 
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function indexAction2() {
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(json_encode($this->nativeSQL()));
    }
    
    private function getAllCategories() {
        /* Get all categories */
        $category = Mage::getModel('catalog/category');
        $tree = $category->getTreeModel();
        $tree->load();
        $ids = $tree->getCollection()->getAllIds();
        
        $categories = array();
        
        if ($ids){
            foreach ($ids as $id){
                $cat = Mage::getModel('catalog/category');
                $cat->load($id);
        
                $entity_id = $cat->getId();
                $name = $cat->getName();
                $url_key = $cat->getUrlKey();
                $url_path = $cat->getUrlPath();
                
                $categories[] = array('entity_id' => $entity_id,
                                      'name' => $name,
                                      'url_key' => $url_key,
                                      'url_path' => $url_path );
            }
        }
        
        return json_encode($categories);
    }
    
    private function getAllOrders() {
        ini_set('memory_limit', '1024M');
        
        $salesModel=Mage::getModel("sales/order");
        $salesCollection = $salesModel->getCollection()
        ->addAttributeToSelect('increment_id')
        ->getColumnValues('increment_id');
        $response = array();
        /*foreach($salesCollection as $order) {
            $orderId= $order->getIncrementId();
            $response[$orderId] = $orderId;
        }*/
        
        return $salesCollection;
    }
    
    private function getShipment($shipmentId) {
        /**
         * @var $shipment Mage_Sales_Model_Order_Shipment
         * @var $order    Mage_Sales_Model_Order
         */
        $shipment    = Mage::getModel('sales/order_shipment')->load($shipmentId);
        
        $city        = $shipment->getShippingAddress()->getRegion();
        
        return $city;
    }
    
    private function getAllShipments() {
        /**
         * @var $shipment Mage_Sales_Model_Order_Shipment
         * @var $order    Mage_Sales_Model_Order
         */
         
        ini_set('memory_limit', '1024M');
        
        $shipmentModel    = Mage::getModel('sales/order_shipment');
        $shipmentsCollection = $shipmentModel->getCollection();
        
        $respone = array();
        
        foreach ( $shipmentsCollection AS $aShipment ) {
            $city = $aShipment->getShippingAddress()->getCity();
            
            if ( isset($response[$city])) {
                $response[$city]++;
            }
            else {
                $response[$city] = 1;
            }
            
        }
        
        return $response;
    }
    
    private function getOrdersBetween2($fromDate, $toDate) {
        /* Format our dates */
        $fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
        $toDate = date('Y-m-d H:i:s', strtotime($toDate));
         
        /* Get the collection */
        $orders = Mage::getModel('sales/order_shipment')->getCollection()
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate));
           // ->addAttributeToFilter('status', array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE));
            
        return $orders;
    }
    
    public function getOrdersBetween($fromDate, $toDate) {
        $orderTotals = Mage::getModel('sales/order')->getCollection()
        //->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
        //->addAttributeToFilter('created_at', array('from'  => '2014-01-01'))
        ->addAttributeToSelect('grand_total')
        //->addAttributeToSelect('increment_id')
        ->getColumnValues('grand_total');
        
        $totalSum = array_sum($orderTotals);
    
        return $totalSum;
    }
    
    public function getDataAction() {
        
        $parameters = $this->getRequest()->getParams();
        
        $dateFrom = $parameters["date_from"];
        $dateTo = $parameters["date_to"];
        
        if ( isset($parameters["status"]) && !empty($parameters["status"])) {
        
            $statusQuery = '(';
            
            foreach ( $parameters["status"] AS $aStatus ) {
                $statusQuery .= "'".$aStatus."', ";    
            }
            
            $statusQuery = substr($statusQuery, 0, -2);
            
            $statusQuery .= ")";
        
        }
        
        if ( isset($parameters["payment_method"]) && !empty($parameters["payment_method"])) {
        
            $paymentMethodQuery = '(';
            
            foreach ( $parameters["payment_method"] AS $aMethod ) {
                $paymentMethodQuery .= "'".$aMethod."', ";    
            }
            
            $paymentMethodQuery = substr($paymentMethodQuery, 0, -2);
            
            $paymentMethodQuery .= ")";
        
        }
        
         /**
         * Get the resource model
         */
        $resource = Mage::getSingleton('core/resource');
         
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
         
         
        if ( empty($paymentMethodQuery) ) {
            
            $query = "SELECT directory_country_region.default_name,
                        SUM( base_subtotal_invoiced - base_discount_invoiced ) AS total
                        FROM sales_flat_order, sales_flat_order_address, directory_country_region                      
                        where sales_flat_order.entity_id = sales_flat_order_address.parent_id
                            AND sales_flat_order_address.region_id = directory_country_region.region_id
                            AND directory_country_region.country_id =  'AR'";
        }
        else {
            
            $query = "SELECT directory_country_region.default_name,
                        SUM( base_subtotal_invoiced - base_discount_invoiced ) AS total
                        FROM sales_flat_order, sales_flat_order_address, directory_country_region,
                                sales_flat_order_payment
                        where sales_flat_order.entity_id = sales_flat_order_address.parent_id
                            AND sales_flat_order_address.region_id = directory_country_region.region_id
                            AND sales_flat_order_payment.entity_id = sales_flat_order.entity_id
                            AND sales_flat_order_payment.method IN ".$paymentMethodQuery."
                            AND directory_country_region.country_id =  'AR'";
        }
        
         
        if ( empty($dateFrom) OR empty($dateTo) ) {
         
           /* $query = "SELECT directory_country_region.default_name,
                        SUM( base_subtotal_invoiced - base_discount_invoiced ) AS total
                        FROM sales_flat_order, sales_flat_order_address, directory_country_region                      
                        where sales_flat_order.entity_id = sales_flat_order_address.parent_id
                            AND sales_flat_order_address.region_id = directory_country_region.region_id
                            AND directory_country_region.country_id =  'AR'"; */
                    
        }
        else {
            $query .= "AND sales_flat_order.created_at > '".$dateFrom."'
                            AND sales_flat_order.created_at < '".$dateTo."'";
                        
        }
        
        if ( !empty($statusQuery) ) {
            $query .= " AND sales_flat_order.status IN ".$statusQuery;
        }
         
        $query .= " GROUP BY sales_flat_order_address.region_id
                        ORDER BY total DESC";
         
        /**
         * Execute the query and store the results in $results
         */
        //$results = $readConnection->fetchAll($query);
        
        $readresult = $readConnection->query($query);
        
        $response = array();
        
       // $response[] = array('default_name' => 'Provincia', 'count' => '# de Ventas');
        $response[] = array( 'Provincia', 'Monto [ARS]');
        
        while ($row = $readresult->fetch() ) {
            //$response[] = array('entity_id' => $row['entity_id'], 'default_name' => utf8_encode($row['default_name']), 'count' => $row['count']);
            $response[] = array(utf8_encode($row['default_name']), $row['total']);
        }
         
        /**
         * Print out the results
         */
         //var_dump($response);
         
         echo json_encode($response);
         
         return $this;
    }
    
    public function getDataBAKAction() {
        
        $parameters = $this->getRequest()->getParams();
        
        $dateFrom = $parameters["date_from"];
        $dateTo = $parameters["date_to"];
        
         /**
         * Get the resource model
         */
        $resource = Mage::getSingleton('core/resource');
         
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
         
        if ( empty($dateFrom) OR empty($dateTo) ) {
         
            $query = 'SELECT sales_flat_order_address.entity_id, directory_country_region.default_name, COUNT( DISTINCT sales_flat_order_address.entity_id ) AS count
                        FROM  `sales_flat_order_address` 
                        JOIN directory_country_region ON sales_flat_order_address.region_id = directory_country_region.region_id
                        WHERE directory_country_region.country_id =  "AR"
                        GROUP BY sales_flat_order_address.region_id
                        ORDER BY count DESC';
                    
        }
        else {
            $query = 'SELECT sales_flat_order_address.entity_id, directory_country_region.default_name, COUNT( DISTINCT sales_flat_order_address.entity_id ) AS count
                    FROM  `sales_flat_order`, `sales_flat_order_address`, directory_country_region
                    WHERE sales_flat_order_address.region_id = directory_country_region.region_id
                        Adirectory_country_region.country_id =  "AR"
                    GROUP BY sales_flat_order_address.region_id
                    ORDER BY count DESC';
        }
         
        /**
         * Execute the query and store the results in $results
         */
        //$results = $readConnection->fetchAll($query);
        
        $readresult = $readConnection->query($query);
        
        $response = array();
        
       // $response[] = array('default_name' => 'Provincia', 'count' => '# de Ventas');
        $response[] = array( 'Provincia', '# de Envios');
        
        while ($row = $readresult->fetch() ) {
            //$response[] = array('entity_id' => $row['entity_id'], 'default_name' => utf8_encode($row['default_name']), 'count' => $row['count']);
            $response[] = array(utf8_encode($row['default_name']), $row['count']);
        }
         
        /**
         * Print out the results
         */
         //var_dump($response);
         
         echo json_encode($response);
         
         return $this;
    }
    
    public function getSalesByYearAction() {
         /**
         * Get the resource model
         */
        $resource = Mage::getSingleton('core/resource');
         
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
         
        $query = "SELECT YEAR( created_at ) AS year , SUM( base_total_invoiced ) AS total
                    FROM  `sales_flat_order` 
                    WHERE state NOT LIKE  'canceled'
                    GROUP BY YEAR( created_at ) ORDER BY year ASC";
         
        /**
         * Execute the query
         */
        $readresult = $readConnection->query($query);
        
        $response = array();
    
        $response[] = array( 'Año', 'Ventas [ARS]');
        
        while ($row = $readresult->fetch() ) {
            $response[] = array(utf8_encode($row['year']), $row['total']);
        }
         
        /**
         * Print out the results
         */
         //var_dump($response);
         
         echo json_encode($response);
         
         return $this;
    }
    
    public function newCustomersByDateAction() {
        $this->loadLayout();

        $this->renderLayout();
        
        return $this;
    }
    
    public function getNewCustomersByDateAction() {
        
        echo json_encode($this->getNewCustomersByDate());
        
        return $this;
    }
    
    private function getNewCustomersByDate() {
        
         /**
         * Get the resource model
         */
        $resource = Mage::getSingleton('core/resource');
         
        /**
         * Retrieve the read connection
         */
        $readConnection = $resource->getConnection('core_read');
        
        $query = "SELECT count(*) AS clients, month, year
                    FROM ( 
                    select customer_id, MONTH(min(created_at)) AS month, year(min(created_at)) AS year, min(created_at) AS firstOrder
                    from sales_flat_order
                    where status <> 'canceled'
                    group by customer_id
                    order by firstOrder DESC ) AS tableAux
                    group by tableAux.month, tableAux.year
                    order by tableAux.year, tableAux.month";
                    
        /**
         * Execute the query
         */
        $readresult = $readConnection->query($query);
        
        $response = array();
    
        $response[] = array( 'Periodo', '# Clientes');
        
        while ($row = $readresult->fetch() ) {
            $response[] = array(utf8_encode($row['month']."-".$row['year']), $row['clients']);
        }
         
        return $response;
                    
    }
    
    private function getBestCustomers() {
        $query = "select customer_id, count(*) AS total
                    from sales_flat_order
                    group by customer_id
                    order by total DESC";
    }
    
    private function getCohort() {
        $query = "SELECT sales_flat_order.customer_id, sales_flat_order.created_at, (
                    sales_flat_order.base_subtotal_invoiced - base_discount_invoiced
                    ) AS amount, cohorts.cohortdate, CONCAT( CONCAT( YEAR( cohorts.cohortdate ) ,  '-' ) , MONTH( cohorts.cohortdate ) ) , TIMESTAMPDIFF( 
                    MONTH , cohorts.cohortdate, sales_flat_order.created_at ) +1 AS cohortPeriod
                    FROM sales_flat_order
                    JOIN (
                    
                    SELECT customer_id, MIN( created_at ) AS cohortDate
                    FROM sales_flat_order
                    WHERE STATUS <>  'canceled'
                    GROUP BY customer_id
                    ) AS cohorts ON sales_flat_order.customer_id = cohorts.customer_id
                    WHERE STATUS <>  'canceled'
                    AND sales_flat_order.base_subtotal_invoiced IS NOT NULL 
                    AND sales_flat_order.base_discount_invoiced IS NOT NULL";
    }
    
    private function getAvgOrder() {
        $query = "SELECT CONCAT(CONCAT(YEAR(created_at), '-'), LPAD(MONTH(created_at), 2, '0')) AS period, 
                        AVG( sales_flat_order.base_subtotal_invoiced - base_discount_invoiced) AS amount 
                    FROM `sales_flat_order` 
                    where status <> 'canceled'
                    group by period";
    }
    
} 

?>