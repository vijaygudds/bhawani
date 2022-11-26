<?php class page_crdrcheck extends Page {
	
	function init(){
		parent::init();
		$acc_type = $this->api->stickyGET('account_type');
		$f = $this->add('Form');
		$f->addField('DropDown','account_type')->setValueList(array('%'=>'All','Saving'=>'Saving','DDS'=>'DDS','Recurring'=>'Recurring','FD'=>'FD','MIS'=>'MIS','Two Wheeler 
Loan'=>'VL'));
		$f->addSubmit('Get Details');
		$account = $this->add('Model_Account');
		$account->addCondition('ActiveStatus',true);
		$account->addCondition('DefaultAC',false);
		$account->addCondition('account_type','<>','SM');
		$account->addCondition('member_id','<>',['1','2','3','1623','1770','1917','7065','14586','14587','14859','14869','14870','14871','14872','14873','14874','14875','14876','14877','14878','14879','14880']);
		$grid = $this->add('Grid_AccountsBase');
		
		$account->addExpression('tdr')->set(function($m,$q){
			return $this->add('Model_TransactionRow')->addCondition('account_id',$m->getField('id'))->sum('amountDr');
		});
		$account->addExpression('tcr')->set(function($m,$q){
			return $this->add('Model_TransactionRow')->addCondition('account_id',$q->getField('id'))->sum('amountCr');
		});
		$account->addExpression('balance')->set(
			$account->dsql()->expr('IFNULL([0],0) + IFNULL([1],0) - IFNULL([2],0) - IFNULL([3],0)',
				array(
					$account->getElement('OpeningBalanceCr'),
					$account->getElement('tcr'),
					$account->getElement('OpeningBalanceDr'),
					$account->getElement('tdr')
					)
				)
			);
		if($acc_type){
			$account->addCondition('account_type',$acc_type);
		}else{
			$account->addCondition('id',-1);
		}
		$grid->setModel($account,['member','AccountNumber','OpeningBalanceCr','OpeningBalanceDr','CurrentBalanceCr','CurrentBalanceDr','tcr','tdr','balance']);
		$grid->addSno();
		$grid->addPaginator(1000);
		$grid->addQuickSearch(['AccountNumber']);
		if($f->isSubmitted()){
			$grid->js()->reload(
						array(
							'account_type'=>$f['account_type'],
							'filter'=>1
						)
			)->execute();
		}	
	}
}
