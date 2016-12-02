<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

// load config
require_once(JPATH_ADMINISTRATOR . '/components/com_zoo/config.php');
 
class JFormFieldFLZooItemOrder extends JFormField {
 
	protected $type = 'flzooitemorder';
 
	public function getInput() {

		// get app
        $zoo  = App::getInstance('zoo');
        $attribs = '';
        $options = $html = array();
        $control_name = $this->getName($this->fieldname);
        $item_order = $this->value ? $this->value : array('_itemname');

  //       if ($v = $this->element->attributes()->class) {
  //   		$attribs .= ' class="'.$v.'"';
		// } else {
		//     $attribs .= ' class="inputbox"';
		// }

		// if ($this->element->attributes()->multiple) {
		//     $attribs .= ' multiple="multiple"';
		// }

		foreach ($zoo->application->getApplications() as $application) {
			$types = $application->getTypes();

			// add core elements
			$core = $zoo->object->create('Type', array('_core', $application));
			$core->name = JText::_('Core');
			array_unshift($types, $core);

			$html[] = '<div class="form-inline">';

				foreach ($types as $type) {

					$html[] = '<div style="margin-bottom: 15px;">';

					if ($type->identifier == '_core') {
						$elements = $type->getCoreElements();
						$options = array();
					} else {
						$elements = $type->getElements();
						$options = array($zoo->html->_('select.option', false, '- '.JText::_('Select Element').' -'));
					}

					// filter orderable elements
					$elements = array_filter($elements, create_function('$element', 'return $element->getMetaData("orderable") == "true";'));

					$value = false;
					foreach ($elements as $element) {
						if (in_array($element->identifier, $item_order)) {
							$value = $element->identifier;
						}
						$options[] = $zoo->html->_('select.option', $element->identifier, ($element->config->name ? $element->config->name : $element->getMetaData('name')));
					}
					if ($type->identifier == '_core' && $this->element->attributes()->add_default) {
						array_unshift($options, $this->app->html->_('select.option', '', JText::_('default')));
					}

					$id = $control_name.$type->identifier;
					$html[] = '<div class="type">';
					$html[] = $zoo->html->_('select.genericlist',  $options, "{$control_name}[]", 'class="element"', 'value', 'text', $value, $id);
					$html[] = '<label class="help-inline" for="'.$id.'">' . $type->name . '</label>';
					$html[] = '</div>';
					if ($type->identifier == '_core') {
						$html[] = '<div style="margin-top: 15px;">- '.JText::_('JOR').' -</div>';
					}

					$html[] = '</div>';

				}

			break;
		}

		$html[] = '</div>';
		$html[] = '<div style="margin-bottom: 15px;">';
		$id = "{$control_name}[_reversed]";
		$html[] = "<input type=\"checkbox\" id=\"{$id}\" name=\"{$control_name}[]\"" . (in_array('_reversed', $item_order) ? 'checked="checked"' : '') . ' value="_reversed" />';
		$html[] = '<label class="help-inline" for="'.$id.'">' . JText::_('Reverse') . '</label>';
		$html[] = '</div>';

		$html[] = '<div style="margin-bottom: 15px;">';
		$id = "{$control_name}[_alphanumeric]";
		$html[] = "<input type=\"checkbox\" id=\"{$id}\" name=\"{$control_name}[]\"" . (in_array('_alphanumeric', $item_order) ? 'checked="checked"' : '') . ' value="_alphanumeric" />';
		$html[] = '<label class="help-inline" for="'.$id.'">' . JText::_('Alphanumeric sorting') . '</label>';
		$html[] = '</div>';

		$html[] = '<div style="margin-bottom: 15px;">';
			$id = "{$control_name}[_random]";
			$html[] = "<input type=\"checkbox\" id=\"{$id}\" name=\"{$control_name}[]\"" . (in_array('_random', $item_order) ? 'checked="checked"' : '') . ' value="_random" />';
			$html[] = '<label class="help-inline" for="'.$id.'">' . JText::_('Random') . '</label>';
		$html[] = '</div>';
	

        return implode("\n", $html);
	}
}