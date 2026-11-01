<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.5
*/
namespace RJCreations\Plugin\Captcha\Qa\Field;

use Joomla\CMS\Factory;
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
		$nam = $this->name;
		$val = $this->value;
		$custf = JPATH_ROOT.'/media/plg_captcha_qa/custom';
		$files = Folder::exists($custf) ? Folder::files($custf) : [];
		$ck = ($val=='0' || $val=='') ? ' checked' : '';
		$html = '<div><label><input type="radio" name="'.$nam.'" value=""'.$ck.'> '.Text::_('PLG_CAPTCHA_QA_NO_CUSTOM').'</label></div>';
		foreach($files as $file) {
			if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}\.json\z/D', $file) || str_contains($file, '..')) continue;
            $ck = $val == $file ? ' checked' : '';
            $safe = htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $jsArg = htmlspecialchars(json_encode($file, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= '<div><label><input type="radio" name="'.$nam.'" value="'.$safe.'"'.$ck.'> '.$safe.'</label> <span class="icon-edit" onclick="editQas('.$jsArg.')"> </span></div>';
		}
		return $html . '<button type="button" class="btn btn-secondary" onclick="editQas()"><span class="icon-plus" aria-hidden="true"></span> Create</button>';
	}

}
