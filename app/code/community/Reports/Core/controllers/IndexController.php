<?php

/* 
* Controller class has to be inherited from Mage_Core_Controller_action 
*/ 
class Reports_Core_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        
        $this->loadLayout();
        $this->renderLayout();
        //Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
        
        //echo 'Foo Index Action';
    }

    public function addAction() {
        echo 'Foo add Action';
    }

    public function deleteAction() {
        echo 'Foo delete Action';
    }
    
}

?>