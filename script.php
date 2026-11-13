<?php
/**
* @package		plg_captcha_qa
* @copyright	Copyright (C) 2015-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

class plgCaptchaQaInstallerScript extends InstallerScript
{
	protected $minimumJoomla = '4.0';
	protected $deleteFiles = ['/plugins/captcha/qa/qa.php'];

	public function install ($parent) 
	{
	}

	public function uninstall ($parent) 
	{
	}

	public function update ($parent) 
	{
	}

	public function preflight ($type, $parent) 
	{
	}

	public function postflight ($type, $parent) 
	{
		if ($type === 'update') {
			$this->removeFiles();
		}
		return true;
	}

}
