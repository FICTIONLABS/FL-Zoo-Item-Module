<?php
/**
 * @package   FL Zoo Item Module for Zoo
 * @author    Дмитрий Васюков http://fictionlabs.ru
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// create label
$label = '';
if (isset($params['showlabel']) && $params['showlabel']) {
	$label = ($params['altlabel']) ? $params['altlabel'] : $element->config->get('name');
}

// create class attribute
$class = 'element element-'.$element->getElementType().($params['first'] ? ' first' : '').($params['last'] ? ' last' : '');

?>
<li>
	<?php echo $label.' '.$element->render($params); ?>
</li>