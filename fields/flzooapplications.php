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
 
class JFormFieldFLZooApplications extends JFormField {
 
	protected $type = 'flzooapplications';
 
	public function getInput() {

		// get app
        $zoo  = App::getInstance('zoo');
        $attribs = '';
        $options = array();

        if ($v = $this->element->attributes()->class) {
    		$attribs .= ' class="'.$v.'"';
		} else {
		    $attribs .= ' class="inputbox"';
		}

		if ($this->element->attributes()->multiple) {
		    $attribs .= ' multiple="multiple"';
		}

		foreach ($zoo->application->getApplications() as $application) {
			$options[] = $zoo->html->_('select.option', $application->id, $application->name);	
		}

        return $zoo->html->_('select.genericlist', $options, $this->getName($this->fieldname), trim($attribs), 'value', 'text', $this->value, $this->id);
	}
}