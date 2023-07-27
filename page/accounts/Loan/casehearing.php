<?php

class page_accounts_Loan_casehearing extends Page {
	public $title = "Legal Case Hearing Manage";

	function init(){
		parent::init();

		$this->add('Controller_Acl');
		$crud = $this->add('CRUD');
		$m = $this->add('Model_LegalCase');
		$acc_j = $m->leftJoin('accounts','account_id');
		$mem_j = $acc_j->leftJoin('members','member_id');
		$mem_j->addField('PhoneNos');
		$mem_j->addField('PermanentAddress');
		$crud->setModel($m,['account_id','name','bccs_file_no','court','autorised_person','case_type','stage','case_on','file_verified_by','advocate','remarks'],['account','name','bccs_file_no','court','autorised_person','case_type','legalcasehearing_stage','case_on','file_verified_by','advocate','remarks','legal_filing_date','last_hearing_date','owner','account_guarantor','PhoneNos','PermanentAddress']);
                $crud->addRef('LegalCaseHearing',['grid_fields'=>['legalcase','hearing_date','stage','owner','dealer']]);
	}
}
