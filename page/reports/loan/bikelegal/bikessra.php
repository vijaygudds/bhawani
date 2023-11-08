<?php

class page_reports_loan_bikelegal_bikessra extends Page {
	public $title="Bike In Stock Report";
	
	function init(){
		parent::init();

		$form = $this->add('Form');
		$form->addField('DatePicker','from');
		$form->addField('DatePicker','to');
		$form->addField('DropDown','type')->setValueList(['surrender'=>'Surrendered Bikes','returned'=>'Returned Bikes','auctioned'=>'Auctioned Bikes']);

		$document=$this->add('Model_Document');
		$document->addCondition('LoanAccount',true);
		$document->addCondition('name','Bike Location');
		foreach ($document as $junk) {
			$form->addField('CheckBox','doc_'.$document->id, $document['name']);
		}


		$form->addSubmit('Get List');

		$account_model = $this->add('Model_Account_Loan');

		$account_model->addExpression('no_of_emi')->set(function($m,$q){
			return $m->refSQL('Premium')->count();
		});

		$account_model->addExpression('emi_amount')->set(function($m,$q){
			return $m->refSQL('Premium')->setLImit(1)->fieldQuery('Amount');
		});

		$account_model->addExpression('due_premium_count')->set(function($m,$q){
			$p_m = $m->refSQL('Premium')
						->addCondition('PaidOn',null);
			$p_m->addCondition('DueDate','<',$m->api->nextDate($this->app->now));
			return $p_m->count();
		});

		$account_model->addExpression('paid_premium_count')->set(function($m,$q){
			$p_m=$m->refSQL('Premium')
						->addCondition('PaidOn','<>',null);
			$p_m->addCondition('DueDate','<',$m->api->nextDate($this->app->today));
			return $p_m->count();
		})->sortable(true);

		$account_model->addExpression('due_premium_amount')->set(function($m,$q){
			return $q->expr('[0]*[1]',[$m->getElement('due_premium_count'),$m->getElement('emi_amount')]);
		});

		$account_model->addExpression('due_panelty')->set(function($m,$q){
			$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_ACCOUNT_AMOUNT_DEPOSIT);
			
			$tr_m_due = $m->add('Model_TransactionRow',array('table_alias'=>'charged_panelty_tr'));
			$tr_m_due->addCondition('transaction_type_id',$trans_type->id); 
			$tr_m_due->addCondition('account_id',$q->getField('id'));
			$tr_m_due->addCondition('created_at','<',$this->app->nextDate($this->app->today));

			$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_AMOUNT_RECEIVED);
			
			$tr_m_received = $m->add('Model_TransactionRow',array('table_alias'=>'received_panelty_tr'));
			$tr_m_received->addCondition('transaction_type_id',$trans_type->id); 
			$tr_m_received->addCondition('account_id',$q->getField('id'));
			$tr_m_received->addCondition('created_at','<',$this->app->nextDate($this->app->today));

			return $q->expr('([0]-[1])',[$tr_m_due->sum('amountDr'),$tr_m_received->sum('amountCr')]);
		});

		$account_model->addExpression('other_charges')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$tr_m->addCondition('transaction_type_id',[13, 46, 39]); // JV, TRA_VISIT_CHARGE, LegalChargeReceived
			$tr_m->addCondition('account_id',$q->getField('id'));
			return $tr_m->sum('amountDr');
		});

		$account_model->addExpression('other_received')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$received = $tr_m->sum('amountCr');

			$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_AMOUNT_RECEIVED);
			
			$tr_m_received = $m->add('Model_TransactionRow',array('table_alias'=>'other_received_panelty_tr'));
			$tr_m_received->addCondition('transaction_type_id',$trans_type->id); 
			$tr_m_received->addCondition('account_id',$q->getField('id'));
			$tr_m_received->addCondition('created_at','<',$this->app->nextDate($this->app->today));

			$premium_paid = $q->expr('([0]*[1])',[$m->getElement('paid_premium_count'),$m->getElement('emi_amount')]);
			return $q->expr('([0]-([1]+[2]))',[$received,$premium_paid,$tr_m_received->sum('amountCr')]);
		});

		$account_model->addExpression('other_charges_due')->set(function($m,$q){
			return $q->expr('([0]-[1])',[$m->getElement('other_charges'),$m->getElement('other_received')]);
		});

		$account_model->addExpression('total_due')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0)+IFNULL([1],0)+IFNULL([2],0))',[$m->getElement('due_premium_amount'),$m->getElement('due_panelty'),$m->getElement('other_charges_due')]);
		});



		$field="";
		$grid_column_array = array('AccountNumber',$field,'FatherName'.'PermanentAddress','bike_auctioned_on','bike_returned_on','PhoneNos','landmark','tehsil','district','dealer','bike_surrendered_by','total_due');
		if($this->app->stickyGET('filter')){
			switch ($this->app->stickyGET('type')) {
				case 'surrender':
					$field='bike_surrendered_on';
					break;
				case 'returned':
					$field='bike_returned_on';
					break;
				case 'auctioned':
					$field='bike_auctioned_on';
					break;
			}

			$account_model->addCondition($field,'>=',$this->app->stickyGET('from'));
			$account_model->addCondition($field,'<=',$this->app->nextDate($this->app->stickyGET('to')));

			foreach ($document as $junk) {
				$doc_id = $document->id;
				if($_GET['doc_'.$document->id]){
					$this->api->stickyGET('doc_'.$document->id);
					$account_model->addExpression($this->api->normalizeName($document['name']))->set(function($m,$q)use($doc_id ){
						return $m->refSQL('DocumentSubmitted')->addCondition('documents_id',$doc_id )->setLimit(1)->fieldQuery('Description');
					});
					$grid_column_array[] = $this->api->normalizeName($document['name']);
				}
			}

		}else{
			$account_model->addCondition('id',-1);
		}

		$member_j = $account_model->join('members','member_id');
		$member_j->addField('FatherName');
		$member_j->addField('PermanentAddress');
		$member_j->addField('PhoneNos');
		$member_j->addField('landmark');
		$member_j->addField('tehsil');
		$member_j->addField('district');

		$grid = $this->add('Grid_AccountsBase')->addSno();

		$grid->setModel($account_model,$grid_column_array);
		$grid->addPaginator(100);
		$grid->addTotals(['total_due']);

		if($form->isSubmitted()){
			$send = ['filter'=>1,'from'=>$form['from']?:'0','to'=>$form['to']?:'0','type'=>$form['type']];
			foreach ($document as $junk) {
				if($form['doc_'.$document->id])
					// echo "<pre>";
					// print_r($form['doc_'.$document->id]);
					// echo "</pre>";
					$send['doc_'.$document->id] = $form['doc_'.$document->id];
			}
			$grid->js()->reload($send)->execute();
		}

	}
}