<?php

class Vitaminasa_Reports_Block_Dashboard extends Mage_Core_Block_Template {
    
    public function getCurrentMonth() {
        return '12'; //$this->getCurrentDate()->format('F');
    }
    
    public function getCurrentYear() {
        return '2013'; //$this->getCurrentDate()->format('Y');
    }
    
    public function getCurrentMonthName() {
        return 'Diciembre'; //$this->getCurrentDate()->format('F');
    }
    
    private function getCurrentDate() {
        return new DateTime();
    }
    
    private function getDateRange() {
        
        $month = $this->getCurrentMonth();
        $year = $this->getCurrentYear();
        
        $fromDate = $year.'-'.$month.'-'.'01';
        $toDate = date("Y-m-t", strtotime($fromDate));
        $toDate = date('Y-m-d H:i:s', strtotime($toDate . ' + 1 day'));
        
        return array('from' => $fromDate, 'to' => $toDate);
    }
    
    public function getNewCustomers() {
        
        $dateRange = $this->getDateRange();
        
        $query = "  SELECT count(DISTINCT entity_id) AS qty
                    FROM `customer_entity`
                    WHERE customer_entity.created_at >= '".$dateRange['from']."'
                            AND customer_entity.created_at < '".$dateRange['to']."'";
                    
        $readResult = $this->executeQuery($query);
        
        $newCustomers = 0;
        
        if ($row = $readResult->fetch() ) {
            $newCustomers = $row["qty"];
        }
        
        return $newCustomers;
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
    
    public function getSales() {
        
        $dateRange = $this->getDateRange();
        
        $orderTotals = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('status', array('in' => array('complete', 'processing')))
            ->addAttributeToFilter('created_at', $dateRange)
            ->addAttributeToSelect('grand_total')
            ->getColumnValues('grand_total');
            
        $totalSum = array_sum($orderTotals);
        
        // If you need the value formatted as a price...
        return Mage::helper('core')->currency($totalSum, true, false);
    }
    
    public function getNewOrders() {
        
        $dateRange = $this->getDateRange();
        
        $orderTotals = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', array('in' => array('complete', 'processing')))
            ->addFieldToFilter('created_at', $dateRange);
            
        return $orderTotals->getSize();
    }
    
    public function getTotalSales() {
        return number_format(8123543.53, 2, ',', '.');
    }
    
}

?>