<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Vitaminasa_Reports_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        
        $this->loadLayout();
        $this->renderLayout();
        //Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
        
        //echo 'Foo Index Action';
    }
    
    public function salesByStateAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function salesByDateAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function salesByDayAndHourAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function salesByBrandAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function salesByYearAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function salesByPaymentMethodAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function averageOrderAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function allCustomersAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function customersRankingAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function newCustomersAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function customersByLastOrderAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function ordersPerCustomerAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function cohortAnalysisAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function bestSellingProductsAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function mostViewedProductsAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
}

?>