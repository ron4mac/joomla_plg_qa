<?php
/*
 * @package    Plugin Captcha Q&A
 * @copyright  (C) 2013 - 2022 RJCreations. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class PlgCaptchaQa extends JPlugin
{
	protected $autoloadLanguage = true;
	protected $timecheck;

	public function __construct ($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		$this->timecheck = $this->params->get('time_check', 10, 'INT');
	}

	/*
	 * Initialise the captcha
	 * @param   string	$id	The id of the field.
	 * @return  Boolean	True on success, false otherwise
	 */
	public function onInit ($id)
	{
		$lang = Factory::getLanguage();
		$lang->load('custom' , dirname(__FILE__), $lang->getTag(), true);
		return true;
	}

	/*
	 * Gets the challenge HTML
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay ($name, $id, $class)
	{
		$rq = (rand() % 9) + 1;
		$tm = time();
		$sf = ($rq * $tm) % 97;
		$fld = '<br><input type="text" '.$class.' id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
		$ccd = '<input type="hidden" name="captcha_code" value="'."{$rq}-{$tm}-{$sf}".'" />';
		$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$rq);
		list($q,$a) = explode('|',$qa);
		return $label.'<br>'.trim($q).$fld.$ccd;
	}

	/*
	 * Calls an HTTP POST function to verify if the user's guess was correct
	 * @return  True if the answer is correct, false otherwise
	 */
	public function onCheckAnswer ($code)
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$ccd = $input->get('captcha_code', '--', 'cmd');
		list($qn,$tm,$ck) = explode('-', $ccd);
		if ((((int)$qn * (int)$tm) % 97) != (int)$ck) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		if (!$qn) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		if ($this->timecheck && ((time()-$tm) < $this->timecheck)) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_TOO_QUICK'), 'error');
			return false;
		}
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$qn);
		list($q,$a) = explode('|',$qa);
		$cas = explode(',',trim($a));
		if (in_array(trim($code),$cas)) {
			return true;
		}
		$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_INCORRECT'), 'error');
		return false;
	}

}
