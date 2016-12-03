<?php
/**
 * @package   FL Zoo Item Module for Zoo
 * @author    Дмитрий Васюков http://fictionlabs.ru
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

class mod_flzooitemInstallerScript {

	public function install($parent) {}

	public function uninstall($parent) {}

	public function update($parent) {}

	public function preflight($type, $parent) {

		if (strtolower($type) == 'update') {

			// load config
			require_once(JPATH_ADMINISTRATOR.'/components/com_zoo/config.php');

			// get app
			$zoo = App::getInstance('zoo');

			foreach ($zoo->filesystem->readDirectoryFiles($parent->getParent()->getPath('source'), $parent->getParent()->getPath('source').'/', '/(positions\.(config|xml)|metadata\.xml)$/', true) as $file) {
				JFile::delete($file);
			}

		}
	}

	public function postflight($type, $parent) {}
}