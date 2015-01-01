<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Customers Back-end Controller
 */
class Customers extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Bedard.Shop', 'shop', 'customers');
    }
}