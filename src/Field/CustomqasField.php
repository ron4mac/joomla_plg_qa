<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
namespace RJCreations\Plugin\Captcha\Qa\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class CustomqasField extends FormField
{
	protected $type = 'customqas';

	protected function getInput ()
	{
		// hack so reload by redirect can happen
		$uri = Uri::getInstance();
		$session = Factory::getApplication()->getSession();
		$session->set('plg-qa-be-edit',(string) $uri);

		$nam = $this->name;
		$val = $this->value;
		$custf = JPATH_ROOT.'/media/plg_captcha_qa/custom';
		$files = Folder::exists($custf) ? Folder::files($custf, '\.json$') : [];
		$ck = $val=='0' ? ' checked' : '';
		$html = '<input type="file" id="qa_file_field" accept=".json,application/json" onchange="uploadQas(this, event)">';
		$html .= '<div class="qa-customs"><div><label><input type="radio" name="'.$nam.'" value=""'.$ck.'> '.Text::_('PLG_CAPTCHA_QA_NO_CUSTOM').'</label></div>';
		foreach($files as $file) {
			$ck = $val==$file ? ' checked' : '';
			$del = $val==$file ? '' : '<span class="fa fa-trash-o error" onclick="deleteQas(\''.$file.'\')"> </span>';
			$html .= '<div class="qa-cfile"><label><input type="radio" name="'.$nam.'" value="'.$file.'"'.$ck.'> '.$file.'</label> ';
			$html .= '<span class="fa fa-pencil" onclick="editQas(\''.$file.'\')"> </span> ';
			$html .= '<span class="fa fa-download" onclick="downQas(\''.$file.'\')"> </span>'.$del.'</div>';
		}
		$html .= '</div><button type="button" class="btn-secondary" onclick="editQas(\'\')"><span class="fa fa-plus" aria-hidden="true"></span> Create</button> ';
		$html .= '<button type="button" class="btn-secondary" onclick="upldSelQas()"><span class="fa fa-upload" aria-hidden="true"></span> Upload</button> ';
		return $html . '<span id="qa-upld-stat"></span>';
	}

}
