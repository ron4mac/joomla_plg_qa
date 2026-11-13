<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
namespace RJCreations\Plugin\Captcha\Qa\Provider;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Captcha\CaptchaProviderInterface;

/**
 * Provider for Qa Captcha
 */
final class QaCaptchaProvider implements CaptchaProviderInterface
{
	protected $qandas = [];

	public function __construct (private Registry $params)
	{
		$cstm = $params->get('customqa', '', 'STRING');
		if ($cstm) {
			$this->getQandas($cstm, true);
			return true;
		}
		$lang = Factory::getLanguage();
		$this->getQandas('en-GB');
		if ($lang->getTag() != 'en-GB') {
			$this->getQandas($lang->getTag());
		}
	}

	public function getName (): string
	{
		return 'qa';
	}

	public function display (string $name = '', array $attributes = []): string
	{
		if ($this->qandas) {
			$rq = (rand() % count($this->qandas));
			$tm = time();
			$code = base64_encode($this->simpleCrypt(($rq+1).'.'.$tm));
			['id'=>$id,'class'=>$class] = $attributes;
			$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
			$ccd = '<input type="hidden" name="captcha_code" value="'.($rq+1)."-{$tm}-{$code}".'" />';
			$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
			$q = key($this->qandas[$rq]);
			return $label.'<br>'.trim($q).$fld.$ccd;
		}
		$secret = Factory::getApplication()->get('secret');
		$rq = (rand() % 9) + 1;
		$tm = time();
		$code = base64_encode($this->simpleCrypt($rq.'.'.$tm));
		['id'=>$id,'class'=>$class] = $attributes;
		$fld = '<br><input type="text" class="form-control'.($class?' '.$class:'').'" id="'.$id.'" name="'.$name.'" required="required" aria-required="true" value="" />';
		$ccd = '<input type="hidden" name="captcha_code" value="'."{$rq}-{$tm}-{$code}".'" />';
		$label = '<span>'.Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE').'</span>';
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$rq);
		[$q, $a] = explode('|',$qa);
		return $label.'<br>'.trim($q).$fld.$ccd;
	}

	public function checkAnswer (?string $ans = null): bool
	{
		$app = Factory::getApplication();
		$secret = $app->get('secret');
		$input = $app->getInput();
		$ccd = $input->get('captcha_code', '--', 'cmd');
		[$qn, $tm, $ck] = explode('-', $ccd);
		$key = $this->simpleCrypt($qn.'.'.$tm);
		if (base64_decode($ck) !== $key) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		if (!$qn) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_GENERAL'), 'error');
			return false;
		}
		$timecheck = $this->params->get('time_check', 10, 'INT');
		if ($timecheck && ((time()-$tm) < $timecheck)) {
			$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN').' '.Text::_('PLG_CAPTCHA_QA_ERROR_TOO_QUICK'), 'error');
			return false;
		}

		$session = $app->getSession();
		$cooldown = $this->params->get('cool_down', 300, 'INT');
		$remaining = 0;
		$last = $session->get('plg_qa_last_use', 0);
		if ($last) {
			$elapsed = time() - $last;
			if ($elapsed < $cooldown) {
				$remaining = $cooldown - $elapsed;
			} else {
				$session->clear('plg_qa_last_use');
			}
		}
		if ($remaining) {
			$app->enqueueMessage(Text::sprintf('PLG_CAPTCHA_QA_ERROR_TOO_HOT', ceil($remaining/60)), 'warning');
			return false;
		} else {
			$session->set('plg_qa_last_use', time());
		}

		$cas = $this->getQans($qn);
		if (in_array(trim($ans), array_map(trim(...), $cas))) {
			return true;
		}
		$app->enqueueMessage(Text::_('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN'), 'error');
		return false;
	}

	public function setupField (FormField $field, \SimpleXMLElement $element): void
	{
		// Hide the label for this captcha type
	//	$element['hiddenLabel'] = 'true';
	}

	private function getQans (string $qn)
	{
		if ($this->qandas) {
			return array_values($this->qandas[$qn-1])[0];
		}
		$qa = Text::_('PLG_CAPTCHA_QA_Q'.$qn);
		[$q, $a] = explode('|',$qa);
		return explode(',',trim($a));
	}

	private function getQandas (string $ln, bool $cstm=false): void
	{
		foreach (['/custom',''] as $subd) {
			if ($cstm) {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa'.$subd.'/custom/'.$ln;
			} else {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa/qalang'.$subd.'/qandas_'.$ln.'.json';
			}
			if (file_exists($qaf)) {
				try {
					$qas = json_decode(file_get_contents($qaf), true);
					$this->qandas = $qas;
					return;
				} catch (\JsonException $e) {
				}
			}
		}
	}

	private function simpleCrypt (string $str): string
	{
		$key = Factory::getApplication()->get('secret');
		$l = strlen($key);
		$r = '';
		while ($str) {
			$r .= substr($str, 0, $l) ^ substr($key, 0, strlen($str));
			$str = substr($str, $l);
		}
		return $r;
	}

}