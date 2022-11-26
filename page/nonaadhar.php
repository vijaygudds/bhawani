<?php class page_nonaadhar extends Page {

	function init(){
		parent::init();
		$member = $this->add('Model_ActiveMember');
		$acc_join= $member->join('accounts.member_id');
		$acc_join->addField('Amount');
		$aid = $acc_join->addField('account_id');
		$acc_join->addField('account_type');
		$member->addCondition('account_type','SM');
		// $tr_join = $acc_join->leftJoin('transaction_row.account_id');
		// $tr_join->addField('amountCr');
		// $tr_join->addField('amountDr');
		// $member->addCondition('AdharNumber'," ");
		$grid = $this->add('Grid_AccountsBase');
		$btn = $grid->addButton('Do')->set('Mark Member & SM Account is Deactivate');
		$member->getElement('AdharNumber')->sortable(true);
		$member->addExpression('ActiveAccount')->set(function($m,$q){

				return $this->add('Model_Active_Account',['table_alias'=>'saving_accounts'])->addCondition('account_type','<>',"SM")->addCondition('DefaultAC',0)->addCondition('member_id',$q->getField('id'))->count();
		})->sortable(true);
		$member->addExpression('Member_Accounts')->set(function($m,$q){
			return $this->add('Model_Account_SavingAndCurrent',['table_alias'=>'rec_acc_tbl'])
						->addCondition('account_type','<>',"SM")
						->addCondition('member_id',$q->getField('id'))
						->addCondition('DefaultAC',0)
						->_dsql()->del('fields')
						->field('GROUP_CONCAT(AccountNumber)');
		})->sortable(true);

		$member->addExpression('sm_accounts')->set(function($m,$q){
			return $this->add('Model_Account_SM',['table_alias'=>'sm_accounts'])
				->addCondition('member_id',$q->getField('id'))
				->addCondition('ActiveStatus',true)
				// ->fieldQuery('AccountNumber');
				->_dsql()->del('fields')
				->field('GROUP_CONCAT(AccountNumber)');
		})->sortable(true);
		$member->addExpression('sm_amount')->set(function($m,$q){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))->setLimit(1)->fieldQuery('CurrentBalanceDr');
		})->sortable(true);
			$member->addExpression('OpeningCR')->set(function($m,$q){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))/*->refSQL('TransactionRow')*/->sum('OpeningBalanceCr');
		})->sortable(true);
			$member->addExpression('CurrentCR')->set(function($m,$q){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))/*->refSQL('TransactionRow')*/->sum('CurrentBalanceCr');
		})->sortable(true);
		$member->addExpression('OpeningDR')->set(function($m,$q){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))/*->refSQL('TransactionRow')*/->sum('OpeningBalanceDr');
		})->sortable(true);
		$member->addExpression('CurrentDR')->set(function($m,$q){
				return $acc = $this->add('Model_Account_SM')->addCondition('member_id',$q->getField('id'))/*->refSQL('TransactionRow')*/->sum('CurrentBalanceDr');
		})->sortable(true);
		$member->addExpression('member_acc_id')->set(function($m,$q){
			return $tr_a = $this->add('Model_Account_SM')
						->addCondition('member_id',$q->getField('id'))->setLimit(1)->fieldQuery('id');
		});
		$member->addExpression('member_SM')->set(function($m,$q){
			return $tr_a = $this->add('Model_Account_SM')
						->addCondition('member_id',$q->getField('id'))->setLimit(1)->fieldQuery('AccountNumber');
		});
		$member->addExpression('tdr')->set(function($m,$q){
			return $this->add('Model_TransactionRow')->addCondition('account_id',$m->getElement('member_acc_id'))->sum('amountDr');

	});
		$member->addExpression('tcr')->set(function($m,$q){
			return $this->add('Model_TransactionRow')->addCondition('account_id',$m->getElement('member_acc_id'))->sum('amountCr');

		});
		$member->addExpression('balance')->set(function($m,$q){
			// $tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			// $tr_m->addCondition('account_id',$q->getField('id'));
			// $received = $tr_m->sum('amountCr');
			// $premium_paid = $q->expr('([0]*[1])',[$m->getElement('paid_premium_count'),$m->getElement('emi_amount')]);
			return $q->expr('(IFNULL([0],0)+IFNULL([1],0)-IFNULL([1],0)+IFNULL([1],0))',[$m->getElement('OpeningCR'),$m->getElement('tcr'),$m->getElement('OpeningDR'),$m->getElement('tdr')]);
		});
		$member->addExpression('sm_balance')->set(
			$member->dsql()->expr('IFNULL([0],0) + IFNULL([1],0) - IFNULL([2],0) - IFNULL([3],0)',
				array(
					$member->getElement('OpeningCR'),
					$member->getElement('tcr'),
					$member->getElement('OpeningDR'),
					$member->getElement('tdr')
					)
				)
			);
		// $grid->addColumn('grid_balance');
		// $member->addExpression('sum')->set(function($m,$q){
		// return $m->dsql()->expr('sum(amountCr - amountDr)'); //$m->getElement('amountCr') - $m->getElement('amountDr');
		// })->sortable(true);
		$member->addCondition('ActiveAccount','0');
		// $member->addCondition('sm_balance','<','1');
		// $member->addCondition('sm_amount',null);
		// $member->addCondition(
		// $member->dsql()->orExpr()
		// ->where('sm_amount','0')
		// ->where('sm_amount',null)
		// );
		// $member->addCondition($member->dsql()->expr('([0] < 100
		// or [0] = 0)',array($member->getElement('sm_amount'))));
		// $member->addCondition('sm_accounts','<','0');
		$grid->setModel($member,['member_no'/*,'member_acc_id'*/,'member_name','member_SM','sm_accounts',/*'Amount',*/'OpeningCR'/*,'CurrentCR'*/,'tcr','OpeningDR','tdr','CurrentDR','CurrentCR',/*'amountCr','amountDr',*/'sm_balance','ActiveAccount','Member_Accounts','PanNo','AdharNumber']);
		$grid->addSno();
		$grid->addPaginator(1000);
		$grid->addQuickSearch(['sm_accounts','member_SM']);
		$member->_dsql()->group('id');
		if($btn->isClicked()){
		// $member->addCondition('sm_amount','0');
			
		// $member->addCondition('sm_accounts','');
			throw new \Exception($member->count()->getOne(), 1);
				$sql_query= [];
				set_time_limit(30000000);
				foreach ($member as $m) {
					$sql_query[]= 'update members set is_active = 0 where id='.$m->id.' ;';
				$ac = $this->add('Model_Account_SM');
				$ac->addCondition('member_id',$m->id);
				// $ac->tryLoadAny();
				// $ac->deActivate();
				if($ac->loaded()){
					$ac['ActiveStatus'] = false;
					$ac->save();
				}	
				}
				if(count($sql_query) > 0){
					$q = implode(" ",$sql_query);
					$this->api->db->dsql()->expr($q)->execute();
					// $v= $this->add('View')->set("Voucher no" . $voucher);
				}		
			// }
			$grid->js()->reload()->execute();
		}
	}
	function markDeactive(){
	}
}
