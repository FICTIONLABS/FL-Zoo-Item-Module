<?php
/**
 * @package   FL Zoo Item Module for Zoo
 * @author    Дмитрий Васюков http://fictionlabs.ru
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// load config
require_once dirname(__FILE__) . '/helper.php';

// get app
$zoo = App::getInstance('zoo');

// load zoo frontend language file
$zoo->system->language->load('com_zoo');

// init vars
$path = dirname(__FILE__);

// register base path
$zoo->path->register($path, 'mod_flzooitem');

$items 	= array();
$apps 	= $params->get('applications', array());
$cols 	= $params->get('cols', 3);

// get helper
$flZooItemHelper = new modFlZooItemHelper($zoo, $params);

// get items
$items = $flZooItemHelper->getItems();

// set renderer
$renderer = $zoo->renderer->create('item')->addPath(array($zoo->path->path('component.site:'), dirname(__FILE__)));

$layout = $params->get('layout', 'default');

include(JModuleHelper::getLayoutPath('mod_flzooitem', $params->get('theme', 'list')));