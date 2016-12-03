<?php

/**
 * @package   FL Zoo Item Module for Zoo
 * @author    Дмитрий Васюков http://fictionlabs.ru
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

// load config
require_once(JPATH_ADMINISTRATOR . '/components/com_zoo/config.php');
 
class JFormFieldFLZooElements extends JFormField {
 
	protected $type = 'flzooelements';
 
	public function getInput() {

		// get app
        $zoo  = App::getInstance('zoo');
        $attribs = '';
        $options = $html = array();
        $control_name = $this->getName($this->fieldname);

		foreach ($zoo->application->getApplications() as $application) {
			$types = $application->getTypes();

			// add core elements
			$core = $zoo->object->create('Type', array('_core', $application));
			$core->name = JText::_('Core');
			array_unshift($types, $core);

			$options = array();

			foreach ($types as $type) {

				if ($type->identifier == '_core') {
					$elements = $type->getCoreElements();
				} else {
					$elements = $type->getElements();
				}

				// filter orderable elements plus category and tags
				$elements = array_filter($elements, create_function('$element', 'return $element->getMetaData("orderable") == "true" || $element->getMetaData("type") == "itemcategory" || $element->getMetaData("type") == "itemtag";'));

				$value = false;
				foreach ($elements as $element) {
					$options[$type->name][] = $zoo->html->_('select.option', $element->identifier, ($element->config->name ? $element->config->name : $element->getMetaData('name')));
				}

				$id = $control_name.$type->identifier;

			}

			// break after first application
			break;
		}

        return JHtml::_('select.groupedlist',  $options, $this->name, array(
                'list.select' => $this->value,
                'group.items' => null,
            ));
	}
}