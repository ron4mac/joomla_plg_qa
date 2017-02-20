<?php
/* Plugin Captcha Q&A
 * Copyright (C) 2013 - 2017 RJCreations. All rights reserved.
 * License GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('_JEXEC') or die;

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

	/**
	 * Initialise the captcha
	 * @param   string	$id	The id of the field.
	 * @return  Boolean	True on success, false otherwise
	 */
	public function onInit ($id)
	{
		$lang = JFactory::getLanguage();
		$lang->load('custom' , dirname(__FILE__), $lang->getTag(), true);
		return true;
	}

	/**
	 * Gets the challenge HTML
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay ($name, $id, $class)
	{
		$rq = (rand() % 9) + 1;
		$tm = time();
		$sf = ($rq * $tm) % 97;
		$fld = '<br /><input type="text" '.$class.' id="'.$id.'" name="'.$name.'" value="" />';
		$ccd = '<input type="hidden" name="captcha_code" value="'."{$rq}-{$tm}-{$sf}".'" />';
		$label = '<span>'.JText::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
		$qa = JText::_('PLG_CAPTCHA_QA_Q'.$rq);
		list($q,$a) = explode('|',$qa);
		return $label.'<br />'.trim($q).$fld.$ccd;
	}

	/**
	  * Calls an HTTP POST function to verify if the user's guess was correct
	  * @return  True if the answer is correct, false otherwise
	  */
	public function onCheckAnswer ($code)
	{
		$input = JFactory::getApplication()->input;
		$ccd = $input->get('captcha_code', '--', 'cmd');
		list($qn,$tm,$ck) = explode('-', $ccd);
		if ((($qn * $tm) % 97) != $ck) {
			$this->_subject->setError(JText::_('PLG_CAPTCHA_QA_ERROR_GENERAL'));
			return false;
		}
		if (!$qn) {
			$this->_subject->setError(JText::_('PLG_CAPTCHA_QA_ERROR_GENERAL'));
			return false;
		}
		if ($this->timecheck && ((time()-$tm) < $this->timecheck)) {
			$this->_subject->setError(JText::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.JText::_('PLG_CAPTCHA_QA_ERROR_TOO_QUICK'));
			return false;
		}
		$qa = JText::_('PLG_CAPTCHA_QA_Q'.$qn);
		list($q,$a) = explode('|',$qa);
		$cas = explode(',',trim($a));
		if (in_array(trim($code),$cas)) {
			return true;
		}
		$this->_subject->setError(JText::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.JText::_('PLG_CAPTCHA_QA_ERROR_INCORRECT'));
		return false;
	}

}
