<?php

class Vitaminasa_Reports_Block_Dashboard extends Mage_Core_Block_Template {
    
    public function getCurrentMonth() {
        return $this->getCurrentDate()->format('F');
    }
    
    public function getCurrentYear() {
        return $this->getCurrentDate()->format('Y');
    }
    
    private function getCurrentDate() {
        return new DateTime();
    }
    
}

?>