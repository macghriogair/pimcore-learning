<?php

use Website\Controller\Action;
use Pimcore\Model\Object;

class DefaultController extends Action
{
    public function defaultAction()
    {

		$posts = new Object\BlogPost\Listing;

		$paginator = \Zend_Paginator::factory($posts);
    	$paginator->setCurrentPageNumber($this->_getParam('page'));
    	$paginator->setItemCountPerPage(2);

    	$this->view->paginator = $paginator;
    }
}
