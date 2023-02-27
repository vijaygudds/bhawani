<?php

class page_reports_loan_bikelegal_legalcasehearing extends Page {
	public $title = "Legal Case Hearing Report";

	function init(){
		parent::init();

		$form = $this->add('Form');
		$form->addField('autocomplete/Basic','account')->setModel('Account');
		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		$ct_field = $form->addField('DropDown','case_type')->setValueList(array_combine(LEGAL_CASE_TYPES, LEGAL_CASE_TYPES));
		$ct_field->setAttr('multiple','multiple');
		$cst_field = $form->addField('DropDown','stage')->setValueList(array_combine(LEGAL_CASE_STAGES, LEGAL_CASE_STAGES));
		$cst_field->setAttr('multiple','multiple');
		$form->addField('DropDown','case_on')->setEmptyText('Any')->setValueList(array_combine(['Owner','Guarantor'], ['Owner','Guarantor']));

		$form->addSubmit('Go');

		$model = $this->add('Model_LegalCaseHearing');


		$acc_legal_case_j  = $model->join('account_legal_case','legalcase_id');
		$account_join = $acc_legal_case_j->join('accounts','account_id');
		$sch_j = $account_join->join('schemes.id');
		$sch_j->addField('NumberOfPremiums');
		// $doc_sub_j = $account_join->join('documents_submitted.accounts_id');

		// $docsub_m = $this->add('Model_DocumentSubmitted');
		// $docsub_m->addCondition('accounts_id',$model['account_id']);

		// $document_join = $docsub_m->join('documents','documents_id');
		// $doc_name = $document_join->addField('name');
		// $document_join->addField('doc_id','id');
		// $document_join->addField('LoanAccount');
		// // $docsub_m->addCondition
		$field = ['account','total_loan_amount','loan_created_at','total_emi','emi_amount','paid_emi','due_emi','legalcase','cheque_returned_on','cheque_presented_in_bank_on','owner','owner_FatherName','owner_member_no','owner_mobile','owner_address','owner_pin_code','owner_cast','dealer','name','court','bccs_file_no','legal_filing_date','court','case_on','hearing_date','advocate','account_guarantor','gurantor_FatherName','gurantor_member_no','gurantor_mobile','gurantor_cast','guarantor_addres','gurantor_pin_code','autorised_person','stage','remarks'];
		$document=$this->add('Model_Document');
		$document->addCondition('LoanAccount',true);
		$document->addCondition('id','in',[79,9]);
		// $document->addCondition('doc_id',$docsub_m['documents_id']);

		foreach ($document as $junk) {
			$doc_id = $document->id;
			$model->addExpression($this->api->normalizeName($document['name']))->set(function($m,$q)use($doc_id){
				// return "123";
					return $this->add('Model_DocumentSubmitted')->addCondition('accounts_id',$m->getElement('account_id'))->addCondition('documents_id',$doc_id )->setLimit(1)->fieldQuery('Description');
			});
			$field[] = $this->api->normalizeName($document['name']);
		}
		// foreach ($docsub_m as $junk) {
		// 		$model->addExpression($this->api->normalizeName($docsub_m['name']))->set(function($m,$q)use($docsub_m){
		// 		});
		// 		$field[] = $this->api->normalizeName($docsub_m['name']);
		// }
			


		if($account = $this->app->stickyGET('account')){
			$model->addCondition('account_id',$account);
		}

		if($from_date = $this->app->stickyGET('from_date')){
			$model->addCondition('hearing_date','>=', $from_date);
		}

		if($to_date = $this->app->stickyGET('to_date')){
			$model->addCondition('hearing_date','<', $this->app->nextDate($to_date));
		}

		if($case_type = $this->app->stickyGET('case_type')){
			$model->addCondition('case_type', explode(",",$case_type));
		}

		if($stage = $this->app->stickyGET('stage')){
			$model->addCondition('stage', explode(",",$stage));
		}

		if($case_on = $this->app->stickyGET('case_on')){
			$model->addCondition('case_on', $case_on);
		}


		$grid = $this->add('Grid');
		$grid->addSno();

		
		$grid->setModel($model,$field);

		if($form->isSubmitted()){
			$form_data= $form->get();
			$form_data['from_date'] = $form['from_date']?:0;
			$form_data['to_date'] = $form['to_date']?:0;
			$grid->js()->reload($form_data)->execute();
		}

	}
}
