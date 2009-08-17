<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for email management functions. 
 */

// $Id$

import('pages.manager.ManagerHandler');

class EmailHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function EmailHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the emails within the current journal.
	 */
	function emails() {
		$this->validate();
		$this->setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('emails');

		$journal =& Request::getJournal();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates =& $emailTemplateDao->getEmailTemplates(Locale::getLocale(), $journal->getJournalId());

		import('core.ArrayItemIterator');
		$emailTemplates =& ArrayItemIterator::fromRangeInfo($emailTemplates, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('helpTopicId','journal.managementPages.emails');
		$templateMgr->display('manager/emails/emails.tpl');
	}

	function createEmail($args = array()) {
		EmailHandler::editEmail($args);
	}

	/**
	 * Display form to create/edit an email.
	 * @param $args array optional, if set the first parameter is the key of the email template to edit
	 */
	function editEmail($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'emails'), 'manager.emails'));

		$emailKey = !isset($args) || empty($args) ? null : $args[0];

		import('manager.form.EmailTemplateForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$emailTemplateForm =& new EmailTemplateForm($emailKey);
		$emailTemplateForm->initData();
		$emailTemplateForm->display();
	}

	/**
	 * Save changes to an email.
	 */
	function updateEmail() {
		$this->validate();

		import('manager.form.EmailTemplateForm');

		$emailKey = Request::getUserVar('emailKey');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$emailTemplateForm =& new EmailTemplateForm($emailKey);
		$emailTemplateForm->readInputData();

		if ($emailTemplateForm->validate()) {
			$emailTemplateForm->execute();
			Request::redirect(null, null, 'emails');

		} else {
			$this->setupTemplate(true);
			$emailTemplateForm->display();
		}
	}

	/**
	 * Delete a custom email.
	 * @param $args array first parameter is the key of the email to delete
	 */
	function deleteCustomEmail($args) {
		$this->validate();
		$journal =& Request::getJournal();
		$emailKey = array_shift($args);

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $journal->getJournalId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $journal->getJournalId());
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $journal->getJournalId());
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * resets all email templates associated with the journal.
	 */
	function resetAllEmails() {
		$this->validate();

		$journal =& Request::getJournal();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByJournal($journal->getJournalId());

		Request::redirect(null, null, 'emails');
	}

	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getJournalId());

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);

					if ($emailTemplate->getAssocId() == null) {
						$emailTemplate->setAssocId($journal->getJournalId());
						$emailTemplate->setAssocType(ASSOC_TYPE_JOURNAL);
					}

					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * enables an email template.
	 * @param $args array first parameter is the key of the email to enable
	 */
	function enableEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getJournalId());

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(1);

					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}

		Request::redirect(null, null, 'emails');
	}

}

?>
