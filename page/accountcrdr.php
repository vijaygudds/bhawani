<?php class page_accountcrdr extends Page {
	public $title="Active Accounts List";
	
function init(){
		parent::init();
		$grid=$this->add('Grid_AccountsBase');
		
		$grid->add('H3',null,'grid_buttons')->set('Saving Accounts List As On ' . date('d-m-Y',strtotime($_GET['on_date']?:$this->api->today)) );
		
		$account_model=$this->add('Model_Account');
		$account_model->addCondition('ActiveStatus',true);
		$account_model->addCondition('DefaultAC',false);
		$account_model->addCondition('account_type','<>','Default');
		;
		// $account_model->addCondition(
		// 		$account_model->dsql()->orExpr()
		// 		->where($account_model->getElement('CurrentBalanceDr'),0)
		// 		->where($account_model->getElement('CurrentBalanceDr'),null)
		// 	);
		// $account_model->addCondition(
		// 		$account_model->dsql()->orExpr()
		// 		->where($account_model->getElement('CurrentBalanceCr'),0)
		// 		->where($account_model->getElement('CurrentBalanceCr'),null)
		// 	);
		// $account_model->addExpression('current_cr',['table_alias'=>'rowvxv'])->set(function($m,$q){
		// 	return $transaction_row=$this->add('Model_TransactionRow')
		// 						->addCondition('account_id',$q->getField('id'))
		// 						->sum('amountCr');
		// });
		// $account_model->addExpression('tr_dr',['table_alias'=>'rowvccxv'])->set(function($m,$q){
		// 	return $transaction_row=$this->add('Model_TransactionRow')
		// 						->addCondition('account_id',$q->getField('id'))
		// 						->sum('amountDr');
		// });

		$account_model->addExpression('balance')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)-IFNULL([1],0)',
					[
						$m->getElement('CurrentBalanceCr'),
						$m->getElement('CurrentBalanceDr'),
						// $m->getElement('tr_dr'),
						// $m->getElement('OpeningBalanceDr')
					]
			);
		});

		$account_model->addCondition('balance',0);

		// throw new \Exception($account_model->count(), 1);
		

		// $member_join=$account_model->join('members','member_id');
		// $member_join->addField('member_name','name');
		// $member_join->addField('FatherName');
		// $member_join->addField('PhoneNos');
		// $member_join->addField('CurrentAddress');
		// $member_join->addField('landmark');
		
		
		$account_model->add('Controller_Acl');
		$grid->setModel($account_model,array('member_name','scheme','ActiveStatus','created_at','Amount','AccountNumber','CurrentBalanceDr','CurrentBalanceCr','balance'));
		$grid->addSno();
		$order =$grid->addOrder();
		$order->move('s_no', 'first')->now();
		$paginator = $grid->addPaginator(1000);
	}
}
