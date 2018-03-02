<?php

use Website\Controller\Action;
use Pimcore\Model\Object;

class DefaultController extends Action
{
    public function defaultAction()
    {

		$this->view->posts = new Object\BlogPost\Listing;

    }
}
