<?php

class Vitaminasa_Reports_Block_Filters extends Mage_Core_Block_Template {
    
    public function getStatus() {
        $statusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
        
        $response = array();
        
        foreach( $statusCollection AS $aStatus ) {
            
            if ( $aStatus["status"] === "complete" || $aStatus["status"] === "processing" ) {
                $checked = "checked=\"checked\"";
            }
            else {
                $checked = "";
            }
            
            $response[] = array( 'status' => $aStatus['status'],
                                    'label' => $aStatus['label'],
                                    'checked' => $checked );
        }
        
        return $response;
    }
    
    public function getPaymentMethods() {
        
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        
        $response = array();
 
	   foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $response[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode,
                'checked' => "checked=\"checked\""
            );
        }
        
        return $response;
    }
    
    public function getStates($countryCode) {
        
        $states = Mage::getModel('directory/country')->load($countryCode)->getRegions();
        
        $response = array();
        
        // State names
        foreach ($states as $aState) {       
 
            $response[] = array(   'id' => $aState->getId(),
                                'name' => $aState->getName(),
                                'checked' => "checked=\"checked\"");
        }
        
        return $response;
    }
    
    public function getNumberOfColumns() {
        return 4;
    }
    
    public function partitionFilterArray($array) {
        
        return $this->partition($array, $this->getNumberOfColumns());
    }
    
    public function getColumnClass() {
        return "col-md-".(12 / $this->getNumberOfColumns());
    }
    
    private function partition( $list, $p ) {
        $listlen = count( $list );
        $partlen = floor( $listlen / $p );
        $partrem = $listlen % $p;
        $partition = array();
        $mark = 0;
        for ($px = 0; $px < $p; $px++) {
            $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
            $partition[$px] = array_slice( $list, $mark, $incr );
            $mark += $incr;
        }
        return $partition;
}
    
}
