/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.2
*/

function editQas (qasn) {
	const frm = document.getElementById('qasEditFrame');
	frm.src = 'index.php?option=com_ajax&group=captcha&plugin=qa&format=raw&itemid=' + encodeURIComponent(qasn);
	const modalElement = document.getElementById('complexDataModal');
	const modalInstance = bootstrap.Modal.getInstance(modalElement);
	if (modalInstance) {
		modalInstance.show();
	} else {
		const myModal = new bootstrap.Modal(modalElement);
		myModal.show();
	}
};

window.addEventListener('message', function(event) {
	if (event.origin !== window.location.origin) return;
	const modalElement = document.getElementById('complexDataModal');
	const modalInstance = bootstrap.Modal.getInstance(modalElement);
	if (modalInstance) {
		modalInstance.hide();
		if (event.data=="saved") window.location.reload();
	}
});