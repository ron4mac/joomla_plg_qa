<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
namespace RJCreations\Plugin\Captcha\Qa\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use RJCreations\Plugin\Captcha\Qa\Provider\QaCaptchaProvider;

final class Qa extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;
	protected $timecheck;
	protected $rawFile;
	protected $qandas = [];

	public function __construct ($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		$this->timecheck = $this->params->get('time_check', 10, 'INT');
	}

	public static function getSubscribedEvents (): array
	{
		return ['onCaptchaSetup' => 'onCaptchaSetup', 'onAjaxQa' => 'onAjaxQa'];
	}

	public function onCaptchaSetup (CaptchaSetupEvent $event): void
	{
		$event->getCaptchaRegistry()->add(new QaCaptchaProvider($this->params));
	}

	public function onAjaxQa (): void
	{
		$app = $this->getApplication();
		if (!$app->isClient('administrator') || !$app->getIdentity()->authorise('core.manage', 'com_plugins')) {
			throw new \RuntimeException('Not authorized', 403);
		}
		$input = $app->getInput();
		if ($input->post->count()) {
			$this->saveQandas($input->post);
		} else {
			$act = $input->get('act', '', 'cmd');
			$item = $input->get('itemid', null, 'path');
			switch ($act) {
				case 'edit':
					$this->sendForm($item);
					break;
				case 'down':
					$this->sendFile($item);
					break;
				case 'delete':
					$this->deleteFile($item);
					break;
				case 'upload':
					echo $this->receiveFile($input->files);
					break;
				default:
					echo 'Not Implemented';
			}
		}
		$app->close();
	}

	private function loadComplexData ($id): array
	{
		if ($id) $this->getQandas($id, true);
		if ($this->qandas) {
			$qans = [];
			foreach ($this->qandas as $qa) {
				if (!is_array($qa)) return [];
				foreach ($qa as $q=>$a) {
					$qans[$q] = implode(' | ',$a);
				}
			}
			return [
				'questions' => $qans,
				'customnum' => $id
			];
		}
		return [
			'questions' => [''=>''],
			'customnum' => $id
		];
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

	private function getQandas ($ln, bool $cstm=false): void				//<<< @@@@@@@@@	 fix logic
	{
		foreach (['/custom',''] as $subd) {
			if ($cstm) {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa'.$subd.'/custom/'.$ln;
			} else {
				$qaf = JPATH_ROOT.'/media/plg_captcha_qa/qalang'.$subd.'/qandas_'.$ln.'.json';
			}
			if (file_exists($qaf)) {
				try {
					$this->rawFile = file_get_contents($qaf);
					$this->qandas = json_decode($this->rawFile, true);
					return;
				} catch (\JsonException $e) {
				}
			}
		}
	}

	private function receiveFile (\Joomla\Input\Input $files)
	{
		$file = $files->get('qaafile');
		if (!$file || empty($file['name'])) return $this->jsonResp(Text::_('PLG_CAPTCHA_QA_UI_UPLD_NF'));
		if ($file['error'] !== UPLOAD_ERR_OK) return $this->jsonResp(Text::sprintf('PLG_CAPTCHA_QA_UI_UPLDERRC', $file['error']));
		$json = file_get_contents($file['tmp_name']);
		json_decode($json);
		if (json_last_error() !== JSON_ERROR_NONE) return $this->jsonResp(Text::_('PLG_CAPTCHA_QA_UI_UPLDBAD'));
		$custf = $this->customFolder();
		$safeFilename = File::makeSafe($file['name']);
		$destinationPath = $custf . $safeFilename;
		if (!File::upload($file['tmp_name'], $destinationPath)) return $this->jsonResp(Text::_('PLG_CAPTCHA_QA_UI_UPLD_MV'));
		$rsp = '';
		if ($this->loadComplexData($safeFilename)) {
			$rsp = ['type'=>'message', 'msg'=>$safeFilename.'<br>'.Text::_('PLG_CAPTCHA_QA_UI_UPLDOK')];
		} else {
			$rsp = ['type'=>'warning', 'msg'=>$safeFilename.'<br>'.Text::_('PLG_CAPTCHA_QA_UI_MALFILE')];
		}
		return $this->jsonResp($rsp, true);
	}

	private function saveQandas (\Joomla\Input\Input $post): void
	{
		$fn = $post->get('FN', null, 'path');
		if (!$fn) throw new \RuntimeException('Invalid filename', 400);
		if (!str_ends_with(strtolower($fn), '.json')) {
			$fn .= '.json';
		}
		$custf = $this->customFolder();
		$fpath = $custf.$fn;
		$new = !file_exists($fpath);
		$qas = [];
		$Q = $post->get('Q', [], 'array');
		$A = $post->get('A', [], 'array');
		$cnt = count($Q);
		for ($i=0; $i<$cnt; $i++) {
			$qas[] = [$Q[$i] => array_map(trim(...), explode('|',$A[$i]))];
		}
		file_put_contents($fpath, json_encode($qas, JSON_PRETTY_PRINT));
		echo json_encode(['success'=>true, 'new'=>$new]);
	}

	private function sendForm ($qnum): void
	{
		$data = $this->loadComplexData($qnum);
		if (!$data) {
			echo strtoupper(Text::_('PLG_CAPTCHA_QA_UI_MALFILE'));
			return;
		}
		$basePath = JPATH_PLUGINS . '/captcha/qa/layouts';
		$layout = new FileLayout('modal.questions', $basePath);
		$htmlOutput = $layout->render([
			'questions' => $data['questions'],
			'customnum' => $data['customnum']
		]);
		echo $htmlOutput;
	}

	private function sendFile ($qnum): void
	{
		$this->getQandas($qnum, true);
		Factory::getApplication()->getDocument()->setMimeEncoding('application/download');
		header("Pragma: public");
		header("Expires: 0"); 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Cache-Control: private",false); // required for certain browsers 
		header('Content-Disposition: attachment; filename="'.$qnum.'"');
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: '.strlen($this->rawFile));
		echo $this->rawFile;
	}

	private function deleteFile ($qnum): void
	{
		$qaf = $this->customFolder(false).$qnum;
		if (file_exists($qaf)) {
			unlink($qaf);
		} else {
			echo 'does not exist';
		}
	}

	private function customFolder ($create=true): string
	{
		$custf = JPATH_ROOT.'/media/plg_captcha_qa/custom/';
		if ($create && !Folder::exists($custf)) {
			Folder::create($custf);
			file_put_contents($custf . 'index.html', '<html<body></body></html>');
		}
		return $custf;
	}

	private function jsonResp (mixed $msg='', bool $scs=false)
	{
		return json_encode(['success' => $scs, 'message' => $msg]);
	}

}
