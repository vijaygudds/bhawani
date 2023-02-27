<?php

class Model_LegalCaseHearing extends Model_Table {
	public $table ="account_legal_case_hearing";

	function init(){
		parent::init();


		$this->hasOne('LegalCase','legalcase_id')->sortable(true);
		$this->addField('hearing_date')->type('date')->sortable(true);
		$this->addField('stage')->enum(LEGAL_CASE_STAGES)->defaultValue('Investigation')->sortable(true);
		$this->addField('remarks');

		$this->setOrder('hearing_date','desc');

		$this->addExpression('account_id')->set($this->refSQL('legalcase_id')->fieldQuery('account_id'));
		$this->addExpression('account')->set($this->refSQL('legalcase_id')->fieldQuery('account'));
		$this->addExpression('case_on')->set($this->refSQL('legalcase_id')->fieldQuery('case_on'));
		$this->addExpression('case_type')->set($this->refSQL('legalcase_id')->fieldQuery('case_type'));
		$this->addExpression('legal_filing_date')->set($this->refSQL('legalcase_id')->fieldQuery('legal_filing_date'));
		$this->addExpression('court')->set($this->refSQL('legalcase_id')->fieldQuery('court'));
		$this->addExpression('remarks')->set($this->refSQL('legalcase_id')->fieldQuery('remarks'));
		$this->addExpression('autorised_person')->set($this->refSQL('legalcase_id')->fieldQuery('autorised_person'));
		$this->addExpression('advocate')->set($this->refSQL('legalcase_id')->fieldQuery('advocate'));
		$this->addExpression('account_guarantor')->set($this->refSQL('legalcase_id')->fieldQuery('account_guarantor'));
		$this->addExpression('bccs_file_no')->set($this->refSQL('legalcase_id')->fieldQuery('bccs_file_no'));

		$this->addExpression('owner')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('member_name_only');
		});
		$this->addExpression('cheque_presented_in_bank_on')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('cheque_presented_in_bank_on');
		});
		$this->addExpression('total_loan_amount')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('Amount');
		});
		$this->addExpression('loan_created_at')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('created_at');
		});
		$this->addExpression('total_emi')->set(function ($m,$q){
			return $this->add('Model_Premium')
						->addCondition('account_id',$m->getElement('account_id'))
						->count('id');
		});
		$this->addExpression('emi_amount')->set(function ($m,$q){
			return $this->add('Model_Premium')
						->addCondition('account_id',$m->getElement('account_id'))
						->setLimit(1)->fieldQuery('Amount');
		});
		$this->addExpression('paid_emi')->set(function ($m,$q){
			return $this->add('Model_Premium')
						->addCondition('account_id',$m->getElement('account_id'))
						->addCondition('PaidOn','<>',null)
						->count('id');
		});

		$this->addExpression('due_emi')->set(function($m,$q){
			return $this->add('Model_Premium')
						->addCondition('account_id',$m->getElement('account_id'))
						->addCondition('PaidOn',null)
						->count('id');
		})->sortable(true);

		$this->addExpression('cheque_returned_on')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('cheque_returned_on');
		});


		$this->addExpression('owner_member_id')->set($this->refSQL('legalcase_id')->fieldQuery('owner_member_id'));
		$this->addExpression('g_member_id')->set($this->refSQL('legalcase_id')->fieldQuery('g_member_id'));
		$this->addExpression('owner_mobile')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('PhoneNos');
		});
		$this->addExpression('owner_address')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('PermanentAddress');
		});
		$this->addExpression('owner_member_no')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('member_no');
		});
		$this->addExpression('owner_pin_code')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('pin_code');
		});
		$this->addExpression('owner_cast')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('Cast');
		});
		$this->addExpression('owner_FatherName')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('owner_member_id'))
						->fieldQuery('FatherName');
		});

		


		// $this->addExpression('gurantor_mobile')->set(function ($m,$q){
		// 	return $this->add('Model_Member')
		// 				->addCondition('id',$m->getElement('g_member_id'))
		// 				->fieldQuery('PhoneNos');
		// });
		$this->addExpression('gurantor_pin_code')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('g_member_id'))
						->fieldQuery('PhoneNos');
		});
		$this->addExpression('gurantor_FatherName')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('g_member_id'))
						->fieldQuery('FatherName');
		});
		$this->addExpression('gurantor_cast')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('g_member_id'))
						->fieldQuery('Cast');
		});
		$this->addExpression('gurantor_member_no')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('g_member_id'))
						->fieldQuery('member_no');
		});
		$this->addExpression('guarantor_addres')->set(function ($m,$q){
			return $this->add('Model_Member')
						->addCondition('id',$m->getElement('g_member_id'))
						->fieldQuery('PermanentAddress');
		});

		$this->addExpression('dealer')->set(function ($m,$q){
			return $this->add('Model_Account')
						->addCondition('id',$m->getElement('account_id'))
						->fieldQuery('dealer');
		});
	}
}