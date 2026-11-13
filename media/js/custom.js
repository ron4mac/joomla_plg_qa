/**
* @package		plg_captcha_qa (Plugin Captcha Q&A)
* @copyright	(C) 2013-2026 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		2.0.0
*/

function editQas (qasn) {
	const frm = document.getElementById('qasEditFrame');
	frm.src = 'index.php?option=com_ajax&group=captcha&plugin=qa&format=raw&act=edit&itemid=' + encodeURIComponent(qasn);
	const modalElement = document.getElementById('complexDataModal');
	const modalInstance = bootstrap.Modal.getInstance(modalElement);
	if (modalInstance) {
		modalInstance.show();
	} else {
		const myModal = new bootstrap.Modal(modalElement);
		modalElement.addEventListener('hidden.bs.modal', evt => {
			modalElement.querySelector('iframe').src = '';
		});
		myModal.show();
	}
};

function downQas (qasn) {
	const dlframe = document.createElement('iframe');
	dlframe.src = 'index.php?option=com_ajax&group=captcha&plugin=qa&format=raw&act=down&itemid=' + encodeURIComponent(qasn);
	dlframe.style.display = 'none';
	document.body.appendChild(dlframe);
};

function deleteQas (qasn) {
	if (!confirm(Joomla.Text._('PLG_CAPTCHA_QA_UI_CONFIRMD')+"\n"+qasn)) return;
	const url = 'index.php?option=com_ajax&group=captcha&plugin=qa&format=raw&act=delete&itemid=' + encodeURIComponent(qasn);
	fetch(url)
	.then(resp => {if (!resp.ok) throw new Error(`HTTP error! Status: ${resp.status}`); return resp.text();})
	.then(data => {if (data) alert(data); else window.location.reload()});
};

function upldSelQas () {
	const fs = document.getElementById('qa_file_field');
	fs.click();
};

function uploadQas (elm, evt) {
	evt.preventDefault();
	if (!elm.files.length) return;
	const ups = document.getElementById('qa-upld-stat');
	ups.className = 'fa fa-circle-o-notch fa-spin fa-lg';
	ups.style.color = '';
	const url = 'index.php?option=com_ajax&group=captcha&plugin=qa&format=json&act=upload';
	const file = elm.files[0];
	const formData = new FormData();
	formData.append('qaafile', file);
	fetch(url, {method: 'POST', body: formData})
	.then(resp => resp.json())
	.then(data => {
		if (data.success) {
			ups.className = 'fa fa-check-circle-o fa-lg';
			ups.style.color = 'green';
			if (typeof data.message == 'object') {
				const joomlaAlert = {[data.message.type]: [data.message.msg]};
				sessionStorage.setItem('qaa-pending-msg', JSON.stringify(joomlaAlert));
			}
			window.location.reload();
		} else {
			ups.className = 'fa fa-exclamation-triangle fa-lg';
			ups.style.color = 'red';
			Joomla.renderMessages({error:[data.message]});
		}
	})
	.catch(error => {
		console.error('Error: ', error);
		ups.className = '';
		ups.innerText = Joomla.Text._('PLG_CAPTCHA_QA_UI_UPLDERR');
		ups.style.color = "red";
	});
};

window.addEventListener('message', evt => {
	console.log(evt);
	const modalElement = document.getElementById('complexDataModal');
	const modalInstance = bootstrap.Modal.getInstance(modalElement);
	if (modalInstance) {
		const frm = document.getElementById('qasEditFrame');
		frm.src = '';
		modalInstance.hide();
		if (evt.data=="saved") window.location.reload();
		Joomla.renderMessages({success:[Joomla.Text._('PLG_CAPTCHA_QA_UI_UPDOK')]});
	}
});

document.addEventListener('DOMContentLoaded', () => {
	const savedMsg = sessionStorage.getItem('qaa-pending-msg');
	if (savedMsg) {
		sessionStorage.removeItem('qaa-pending-msg');
		const msgs = JSON.parse(savedMsg);
		if (window.Joomla && typeof Joomla.renderMessages === 'function') {
			Joomla.renderMessages(msgs);
		}
	}
});
