<?php
/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/
namespace RJCreations\Plugin\Captcha\Qa\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class ModalField extends FormField
{
	protected $type = 'modal';

	public function __construct ($form = null)
	{
		HTMLHelper::_('bootstrap.modal');
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->registerAndUseStyle('plg_captcha_qa.custom', 'plg_captcha_qa/custom.css')
			->registerAndUseScript('plg_captcha_qa.custom', 'plg_captcha_qa/custom.js');
		Text::script('PLG_CAPTCHA_QA_UI_CONFIRMD');
		Text::script('PLG_CAPTCHA_QA_UI_UPDOK');
		Text::script('PLG_CAPTCHA_QA_UI_UPLDERR');
		parent::__construct($form);
	}

	protected function getInput ()
	{
		$cq = TEXT::_('PLG_CAPTCHA_QA_CUSTOMQAS');
		return <<<EOD
<div class="modal fade" id="complexDataModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalLabel">$cq</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="complexDataContainer">
				<div class="ratio ratio-16x9">
					<iframe id="qasEditFrame" src="" loading="lazy" style="border:0; width:100%;"></iframe>
				</div>
			</div>
		</div>
	</div>
</div>
EOD;
	}

	public function renderField ($options = [])
	{
		$wrapperClass = $this->element['control_class'] ?? '';
		if ($wrapperClass) {
			// Joomla uses 'class' inside the layout options array for the wrapper wrapper row
			if (empty($options['class'])) {
				$options['class'] = $wrapperClass;
			} else {
				$options['class'] .= ' ' . $wrapperClass;
			}
		}
		return parent::renderField($options);
	}

}
