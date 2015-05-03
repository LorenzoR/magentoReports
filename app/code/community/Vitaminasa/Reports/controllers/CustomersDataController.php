<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_CustomersDataController extends Mage_Core_Controller_Front_Action {
 
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function getNewVsExistingCustomersActions() {
        
    }
    
/* *************************** */
/* Orders per Customer         */
/* *************************** */
    
    public function getOrdersPerCustomerAction() {
        
        $query = "SELECT auxTable.qty AS orders, count(auxTable.customer_id) AS customers
                    FROM (
                            SELECT customer_id, COUNT(*) AS qty
                                        FROM sales_flat_order
                                        WHERE sales_flat_order.customer_id IS NOT NULL
                                            AND status IN ('processing','complete')
                                        GROUP BY customer_id 
                        ) auxTable
                    GROUP BY auxTable.qty
                    ORDER BY auxTable.qty";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        while ($row = $readResult->fetch() ) {
            $response[] = array(    "orders" => $row["orders"],
                                    "customers" => $row["customers"]);
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
/* *************************** */
/* Customers by last order     */
/* *************************** */
    
    public function getCustomersByLastOrderAction() {
        
        $customersCollection = Mage::getModel('customer/customer')->getCollection()
           ->addAttributeToSelect('id');
        
        $customersId = array();
        
        $currentDate = new DateTime();
        
        $resp = array();
        
        foreach ($customersCollection as $aCustomer) {
            
            $ordersCollection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', $aCustomer->getEntityId())
                ->addAttributeToFilter('status', array('in' => array('complete', 'processing')))
                ->addAttributeToSort('created_at', 'DESC')
                ->setPageSize(1);
            
            $order = $ordersCollection->getFirstItem();    
            
            if ( $order != null ) {
                
                $orderDate = $order->getCreatedAt();
                
                $d2 = new DateTime($orderDate);
                
                if ( !empty($orderDate) ) {
                
                    $interval = $d2->diff($currentDate);
    
                    $dateDiference = $interval->m + ($interval->y * 12);
                    
                    if ( $dateDiference > 12 ) {
                        $dateDiference = 13;
                    }
                    
                    if ( !isset($resp[$dateDiference]) ) {
                        $resp[$dateDiference] = array('months' => $dateDiference,
                                                        'qty' => 1 );
                    }
                    else {
                        $resp[$dateDiference]['qty']++;
                    }
                
                }
            }
        }
        
        ksort($resp);
        
        $response = array();
        
        foreach ( $resp AS $aResp ) {
            $response[] = $aResp;
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getNewCustomersAction() {
        
        $query = "  SELECT count(*) AS qty,
                    		CONCAT(CONCAT(LPAD(MONTH(customer_entity.created_at), 2, '0'), '-'), 
                    		YEAR(customer_entity.created_at)) AS period
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
    
    public function getCustomersRankingAction() {
        
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
                                            number_format($row['amount'], 2, ',', '.'),
                                            number_format($row['amount'] / $row['qty'], 2, ',', '.'));
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getAllCustomersAction() {
        
        $collection = Mage::getModel('customer/customer')->getCollection()
                        ->addAttributeToSelect('firstname')
                        ->addAttributeToSelect('lastname');
        
        $customers = array();
        
        foreach ($collection as $item) {
            $customers[] = array(0 => $item->getEntityId(),
                                 1 => $item->getCreatedAtTimestamp(),
                                 2 => ucwords(strtolower($item->getName()))); // Created at
        }
        
        $response['data'] = $customers;
        
        echo json_encode($response);
        
        return $this;
        
    }
 
    public function getAllCustomersCreationDateAction() {
        
        $collection = Mage::getModel('customer/customer')->getCollection();
        
        $customers = array();
        
        foreach ($collection as $item) {
            $customers[$item->getEntityId()] = $item->getCreatedAtTimestamp(); // Created at
        }
        
        echo json_encode($customers);
        
        return $this;
    }
    
    public function getCustomersWithOnePurchaseAction() {
        
        $collection = Mage::getModel('customer/customer')->getCollection();
        
        $customers = array();
        
        foreach ($collection as $user){
            $orders = Mage::getModel('sales/order')
                        ->getCollection()
                        ->addFieldToSelect('increment_id')
                        ->addFieldToFilter('customer_id',$user->getId());
            
            if($orders->getSize() == 1){
                $customers[] = $user->getId();
            }
        }
        
        echo json_encode($customers);
        
        return $this;
        
    }
    
    public function getCohortDataAction() {
    
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
                                    "amount" =>  $row['amount'],
                                    "cohortDateKey" => $row['cohortDateKey'],
                                    "cohortPeriod" => $row['cohortPeriod'],
                                    "customers" => $row['customers']);
        }
        
        echo json_encode($response);
        
        return $this;
        
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