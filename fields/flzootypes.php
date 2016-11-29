<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

// load config
require_once(JPATH_ADMINISTRATOR . '/components/com_zoo/config.php');
 
class JFormFieldFLZooTypes extends JFormField {
 
    protected $type = 'flzootypes';
 
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
            foreach ($application->getTypes() as $type) {
                $options[] = $zoo->html->_('select.option', $type->id, $type->name);
            }

            // break after first type
            break;
        }

        return $zoo->html->_('select.genericlist', $options, $this->getName($this->fieldname), trim($attribs), 'value', 'text', $this->value, $this->id);
    }
}