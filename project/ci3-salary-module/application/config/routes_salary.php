<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Salary module routes (merge in application/config/routes.php)
| -------------------------------------------------------------------------
*/

$route['salary-structure'] = 'Salary_structure/index';
$route['salary-structure/calculate'] = 'Salary_structure/calculate';
$route['salary-structure/component/save'] = 'Salary_structure/save_component';
$route['salary-structure/component/save/(:num)'] = 'Salary_structure/save_component/$1';
$route['salary-structure/component/delete/(:num)'] = 'Salary_structure/delete_component/$1';
