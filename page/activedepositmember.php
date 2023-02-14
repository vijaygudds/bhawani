<?php

class page_activedepositmember extends Page {
	public $title="Member Deposite and Loan Repots";
	function page_index(){
		// parent::init();
	set_time_limit(3000000000);
	
		$this->api->stickyGET('filter');
		$this->api->stickyGET('status');

		$as_on_date = $this->api->today;
		if($this->api->stickyGET('as_on_date'))
			$as_on_date = $_GET['as_on_date'];

		$form=$this->add('Form');
		$form->addField('DatePicker','as_on_date');
		$account_type=$form->addField('DropDown','status')->setValueList(array(1=>'Active',0=>'Inactive'));
		$form->addSubmit('GET List');

		$grid=$this->add('Grid_Report_MemberDepositeAndLoan',array('as_on_date'=>$as_on_date));
		// $grid=$this->add('Grid');
		
		$grid->add('H3',null,'grid_buttons')->set('Member Deposite and Loan Report As On '. date('d-M-Y',strtotime($as_on_date)));

		$member = $this->add('Model_Member');
		$member->addExpression('sm_no')->set(function($m,$q){
			return $m->refSQL('Account')->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital')->setLimit(1)->fieldQuery('AccountNumber');
		});

		$member->addExpression('deposit_active_accounts')->set(function($m,$q){
			

				return $m->add('Model_Account')
					->addCondition('member_id',$q->getField('id'))
					->addCondition('account_type',['FD','DDS','Recurring'])
					->addCondition('ActiveStatus',true)
					->_dsql()->del('fields')
					->field('GROUP_CONCAT(AccountNumber)');
		});
		// $member->addExpression('sm_count')->set(function($m,$q){
		// 	return  $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])->addCondition('ActiveStatus',true)->addCondition('member_id',$q->getField('id'))->count();
		// });
		$member->addExpression('Loan_count')->set(function($m,$q){
			return  $this->add('Model_Account_Loan',['table_alias'=>'sm_accounts'])->addCondition('ActiveStatus',true)->addCondition('member_id',$q->getField('id'))->_dsql()->del('fields')
					->field('GROUP_CONCAT(AccountNumber)');
		});
		// $this->member->addExpression('sm_accounts')->set(function($m,$q){
		// 	return $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])
		// 		->addCondition('member_id',$q->getField('id'))
		// 		->addCondition('ActiveStatus',true)
		// 		->_dsql()->del('fields')
		// 		->field('GROUP_CONCAT(AccountNumber)');
		// });
		// $member_model->addExpression('share_account_amount')->set(function($m,$q){
		// 	$model = $m->refSQL('Account')->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital');
		// 	return  $model->sum('CurrentBalanceCr');
		// });

		$member->addExpression('add_fees')->set(function($m,$q)use($member){
			return 10.000;

			//Temporary Hide and Fixed with 10 Rupees
			// $transaction_type_model = $m->add('Model_TransactionType');
			// $transaction_type_model->addCondition('name','NewMemberRegistrationAmount');
			// return $m->add('Model_Transaction')->addCondition('transaction_type_id',$transaction_type_model->fieldQuery('id'))->addCondition('reference_id',$member_model->getElement('id'))->sum('cr_sum');
		});

		$member->addExpression('share_cr')->set(function($m,$q){
			return $m->refSQL('Account')->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital')->setLimit(1)->fieldQuery('CurrentBalanceCr');
		});
		$member->addExpression('share_dr')->set(function($m,$q){
			return $m->refSQL('Account')->addCondition('SchemeType','Default')->addCondition('scheme_name','Share Capital')->setLimit(1)->fieldQuery('CurrentBalanceDr');
		});

		$member->addExpression('share_account_amount')->set(function($m,$q){
			return $q->expr('[0]-[1]',array($m->getElement('share_cr'),$m->getElement('share_dr')));
		});


		// $member->addCondition('sm_count','>',0);
		// $member->addCondition('sm_accounts','!=','');
		// $member->addCondition('non_active_accounts','<',1);
		// $member->addCondition('Loan_count','<',1);
		$member->addCondition(
				$member->dsql()->orExpr()
					->where($member->getElement('deposit_active_accounts'),'<',1)
					->where($member->getElement('Loan_count'),'<',1)
			);

		if($this->api->stickyGET('filter')){
			if($_GET['as_on_date'])
				$member->addCondition('created_at','<=',$as_on_date);
			
			$member->addCondition('is_active',$_GET['status']);
			// throw new \Exception($member->count()->getOne(), 1);
			
		}else
			$member->addCondition('id',-1);
		$grid->addPaginator(1000);
		$member->setOrder('created_at','desc');	
		$grid->addQuickSearch(['member_name','sm_no']);	
		$grid->setModel($member,
								array('member_name',
										'FatherName',
										'sm_no',
										'share_account_amount',
										'add_fees',
										'Loan_count',
										'deposit_active_accounts',
									)
						);

		if($form->isSubmitted()){
			$grid->js()->reload(array('as_on_date'=>$form['as_on_date']?:0,'status'=>$form['status'],'filter'=>1))->execute();
		}

	}

}
