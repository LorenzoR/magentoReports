<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_CustomersDataController extends Mage_Core_Controller_Front_Action {
 
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function getFirstOrderAction() {
        
        $query = "SELECT auxTable.dateDiff, COUNT( auxTable.customerId ) 
                    FROM (
                        SELECT customer_entity.entity_id AS customerId, DATEDIFF( sales_flat_order.created_at, customer_entity.created_at ) AS dateDiff
                        FROM sales_flat_order, customer_entity
                        WHERE customer_id = customer_entity.entity_id
                        AND status IN ('processing','complete', 'entregado')
                        GROUP BY customer_entity.entity_id
                        ORDER BY  `dateDiff` ASC
                        ) auxTable
                    GROUP BY auxTable.dateDiff
                    ORDER BY auxTable.dateDiff ";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        $customers = array();
        
        while ($row = $readResult->fetch() ) {
            
            if (!isset($response[$row["period"]]['existingCustomers']) ) {
                $response[$row["period"]]['existingCustomers'] = 0;
            }
            
            if (!isset($response[$row["period"]]['newCustomers']) ) {
                $response[$row["period"]]['newCustomers'] = 0;
            }
            
            if ( isset($customers[$row["customerId"]]) ) {
                $response[$row["period"]]['existingCustomers']++;
                $response[$row["period"]]['amount']['existingCustomers'] += $row["baseGrandTotal"];
            }
            else {
                $response[$row["period"]]['newCustomers']++;
                $response[$row["period"]]['amount']['newCustomers'] += $row["baseGrandTotal"];
            }
            
            $customers[$row["customerId"]] = true;
        
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getNewVsExistingCustomersAction() {
        
        /*$query = "SELECT sales_flat_order.entity_id, 
                            CONCAT(CONCAT(LPAD(MONTH(sales_flat_order.created_at), 2, '0'), '-'), 
                    		    YEAR(sales_flat_order.created_at)) AS period, 
                            customer_entity.entity_id AS customerId, 
                            customer_entity.created_at,
                            sales_flat_order.base_grand_total AS baseGrandTotal
                    FROM sales_flat_order, customer_entity
                    WHERE customer_id = customer_entity.entity_id
                        AND status IN ('processing','complete', 'entregado')
                    ORDER BY sales_flat_order.created_at";*/
                    
        $query = "SELECT sales_flat_order.entity_id, 
            		CONCAT( CONCAT( LPAD( MONTH( sales_flat_order.created_at ) , 2,  '0' ) ,  '-' ) , YEAR( sales_flat_order.created_at ) ) AS period, 
            		customer_email AS customerId, 
            		MIN( created_at ) AS createdAt, 
            		sales_flat_order.base_grand_total AS baseGrandTotal
            FROM sales_flat_order
            WHERE customer_id IS NULL 
            AND STATUS IN ( 'processing',  'complete',  'entregado' )
            GROUP BY customer_email
            UNION
            SELECT sales_flat_order.entity_id, 
            		CONCAT(CONCAT(LPAD(MONTH(sales_flat_order.created_at), 2, '0'), '-'), 
            			YEAR(sales_flat_order.created_at)) AS period, 
            		customer_entity.entity_id AS customerId, 
            		customer_entity.created_at AS createdAt,
            		sales_flat_order.base_grand_total AS baseGrandTotal
            FROM sales_flat_order, customer_entity
            WHERE customer_id = customer_entity.entity_id
            	AND status IN ('processing','complete', 'entregado')
            ORDER BY createdAt";
            
        $query = "SELECT sales_flat_order.entity_id, 
                    		CONCAT(CONCAT(LPAD(MONTH(sales_flat_order.created_at), 2, '0'), '-'), 
                    			YEAR(sales_flat_order.created_at)) AS period, 
                    		customer_email AS customerId, 
                    		created_at,
                    		sales_flat_order.base_grand_total AS baseGrandTotal
                    FROM sales_flat_order
                    WHERE status IN ('processing','complete', 'entregado')
                    ORDER BY created_at";
                    
        $readResult = $this->executeQuery($query);
        
        $response = array();
        $customers = array();
        
        while ($row = $readResult->fetch() ) {
            
            if (!isset($response[$row["period"]]['existingCustomers']) ) {
                $response[$row["period"]]['existingCustomers'] = 0;
            }
            
            if (!isset($response[$row["period"]]['newCustomers']) ) {
                $response[$row["period"]]['newCustomers'] = 0;
            }
            
            if ( isset($customers[$row["customerId"]]) ) {
                $response[$row["period"]]['existingCustomers']++;
                $response[$row["period"]]['amount']['existingCustomers'] += $row["baseGrandTotal"];
            }
            else {
                $response[$row["period"]]['newCustomers']++;
                $response[$row["period"]]['amount']['newCustomers'] += $row["baseGrandTotal"];
            }
            
            $customers[$row["customerId"]] = true;
        
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
/* *************************** */
/* Orders per Customer         */
/* *************************** */
    
    public function getOrdersPerCustomerAction() {
        
        $query = "SELECT auxTable.qty AS orders, count(auxTable.customer_email) AS customers
                    FROM (
                            SELECT customer_email, COUNT(*) AS qty
                                        FROM sales_flat_order
                                        WHERE status IN ('processing','complete', 'entregado')
                                        GROUP BY customer_email 
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
        
        //$customers = $this->getAllCustomersFullname();
        
        $query = "SELECT customer_firstname, customer_lastname, COUNT(*) AS qty, SUM(base_grand_total) AS amount
                    FROM sales_flat_order
                    WHERE status IN ('processing','complete', 'entregado')
                    GROUP BY customer_email
                    ORDER BY amount DESC";
        
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        $response["data"] = array();
        
        $i = 1;
        
        while ($row = $readResult->fetch() ) {
            
            $response["data"][] = array(    $i++,
                                            ucwords(htmlentities($row["customer_firstname"]." ".$row["customer_lastname"])), 
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
                           base_grand_total  AS amount, 
                    	   YEAR(created_at) AS year,
                    	   customer_email
                    FROM   sales_flat_order 
                    WHERE status IN ('processing','complete', 'entregado')
                    ORDER BY created_at ASC";
        
        $readResult = $this->executeQuery($query);
        
        $response = array();
        
        $initialDate = null;
        
        $firstBuySet = array();
        
        $cohort = array();
        
        while ($row = $readResult->fetch() ) {
    
            $firstBuy = null;
            $year = $row["year"];
            $email = strtolower($row["customer_email"]);
            
            // Guardo el primer año
            if ( $initialDate == null ) {
                $initialDate = $year;
            }
    
            $index = $year - $initialDate;
            
            // Array con compras por cliente y año
            if ( !isset($firstBuySet[$email]) ) {
                $firstBuySet[$email] = $year;
            }
            else {
                $firstBuy = $firstBuySet[$email];
            }
            
            if ( $firstBuy == null ) {
                $index = $year - $initialDate;
                $subindex = 0;
            }
            else {
                $index = $firstBuy - $initialDate;
                $subindex = $year - $firstBuy;
            }
            
            if ( !isset($response[$index])) {
                $response[$index] = array();
                
                $response[$index][$subindex] = array(    
                        "amount" =>  $row["amount"],
                        "cohortDateKey" => $year,
                        "cohortPeriod" => $subindex,
                        "customers" => 1);
            }
            else {
                
                if ( $firstBuy == null ) {
                    $response[$index][$subindex]['customers']++;
                }
                
                $response[$index][$subindex]['amount'] += $row["amount"];
            }
            
           /* $response[$index][$year] = array(    
                        "amount" =>  $row['amount'],
                        "cohortDateKey" => $row['cohortDateKey'],
                        "cohortPeriod" => $row['cohortPeriod'],
                        "customers" => $row['customers']);
            
            $response[$index][$row["cohortPeriod"]] = array(    
                                    "amount" =>  $row['amount'],
                                    "cohortDateKey" => $row['cohortDateKey'],
                                    "cohortPeriod" => $row['cohortPeriod'],
                                    "customers" => $row['customers']); */
        }
        
        echo json_encode($response);
        
        return $this;
        
    }
    
    public function getCohortDataAction2() {
    
        $query = "  SELECT 
                           SUM(base_grand_total)  AS amount, 
                    	   YEAR(cohorts.cohortdate) AS cohortDateKey,
                    	   YEAR(sales_flat_order.created_at) - YEAR(cohorts.cohortdate) AS cohortPeriod,
                    	   COUNT(distinct sales_flat_order.customer_id) AS customers
                    FROM   sales_flat_order 
                           JOIN (SELECT customer_id, 
                                        Min(created_at) AS cohortDate 
                                 FROM   sales_flat_order 
                    			 WHERE status IN ('processing','complete', 'entregado')
                    			 and customer_id is not null
                                 GROUP  BY customer_id) AS cohorts 
                             ON sales_flat_order.customer_id = cohorts.customer_id
                    		 WHERE status IN ('processing','complete', 'entregado')
                    GROUP BY YEAR(cohorts.cohortdate), YEAR(sales_flat_order.created_at) - YEAR(cohorts.cohortdate)
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