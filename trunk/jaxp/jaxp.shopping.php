<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of jaxpshopping
 *
 * @author Joel Alejandro
 */

class JaxpShopping extends JaxpModule
{
    function __construct()
    {
        $this->ModuleId = "JaxpShopping";
        $this->ModuleName = "jaXP Shopping";
        $this->ModuleDescription = "Handles a web shop.";
        $this->ModuleVersion = "1.0";
        parent::__construct();
    }
}

class JaxpShoppingCatalog
{
    
}
?>
