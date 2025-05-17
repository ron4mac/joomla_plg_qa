<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.0
*/
namespace RJCreations\Plugin\Captcha\Qa\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

class Qa extends CMSPlugin
{
	protected $autoloadLanguage = true;
	protected $timecheck;
	protected $qandas = [];

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
		$lang->load('custom' , dirname(dirname(dirname(__FILE__))), $lang->getTag(), true);
		$this->getQandas('en-GB');
		if ($lang->getTag() != 'en-GB') {
			$this->getQandas($lang->getTag());
		}
		return true;
	}

	/*
	 * Gets the challenge HTML
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay ($name, $id, $class)
	{
		if ($this->qandas) {
			$rq = (rand() % count($this->qandas));
			$tm = time();
			$sf = (($rq+1) * $tm) % 97;
			$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
			$ccd = '<input type="hidden" name="captcha_code" value="'.($rq+1)."-{$tm}-{$sf}".'" />';
			$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
			$q = key($this->qandas[$rq]);
			return $label.'<br>'.trim($q).$fld.$ccd;
		}
		$rq = (rand() % 9) + 1;
		$tm = time();
		$sf = ($rq * $tm) % 97;
		$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
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
	//	$qa = Text::_('PLG_CAPTCHA_QA_Q'.$qn);
	//	list($q,$a) = explode('|',$qa);
	//	$cas = explode(',',trim($a));
		$cas = $this->getQans($qn);
		if (in_array(trim($code), array_map('trim', $cas))) {
			return true;
		}
		$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_INCORRECT'), 'error');
		return false;
	}

	private function getQans ($qn)
	{
		if ($this->qandas) {
			return $this->qandas[$qn-1];
		} else {
			$qa = Text::_('PLG_CAPTCHA_QA_Q'.$qn);
			list($q,$a) = explode('|',$qa);
			return explode(',',trim($a));
		}
	}

	private function getQandas ($ln)
	{
		$qaf = JPATH_ROOT.'/media/plg_captcha_qa/qalang/custom/qandas_'.$ln.'.json';
		if (file_exists($qaf)) {
			try {
				$qas = json_decode(file_get_contents($qaf),true);
				$this->qandas = $qas;
				return;
			} catch (\JsonException $e) {
			}
		}

		$qaf = JPATH_ROOT.'/media/plg_captcha_qa/qalang/qandas_'.$ln.'.json';
		if (file_exists($qaf)) {
			try {
				$qas = json_decode(file_get_contents($qaf),true);
				$this->qandas = $qas;
				return;
			} catch (\JsonException $e) {
				return null;
			}
		}
	}
}
