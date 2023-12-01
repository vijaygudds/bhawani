<?php


class page_reports_agent_tdsfromtable2 extends Page {
	public $title='TDS Report 2 New';


	function init(){
		parent::init();

		$till_date = $this->api->today;
		$from_date = '01-01-1970';
		if($this->app->stickyGET('to_date')) $till_date = $_GET['to_date'];
		if($this->app->stickyGET('from_date')) $from_date = $_GET['from_date'];
		
		$supplier_id = $this->api->stickyGET("supplier");
		$acc_id = $this->api->stickyGET("acc_id");
		$agent_id = $this->api->stickyGET("agent_id");

		$form = $this->add('Form');
		$acc_model = $this->add('Model_Active_Account');
		$acc_model->addCondition('account_type',['Default','Saving']);
		$acc_field=$form->addField('autocomplete/Basic','account');
		$acc_field->setModel($acc_model);
		// $agent_field=$form->addField('autocomplete/Basic','agent');
		// $agent_field->setModel('Agent');
		$supplier_field=$form->addField('autocomplete/Basic','supplier');
		$supplier_model = $this->add('Model_Supplier');
		$supplier_model->addCondition('is_active',true);
		$supplier_field->setModel($supplier_model);
		$form->addField('DatePicker','from_date')->validateNotNull();
		$form->addField('DatePicker','to_date')->validateNotNull();
		// $form->addField('DropDown','account_type')->setValueList(array('%'=>'All','DDS'=>'DDS','Recurring'=>'Recurring','FD'=>'FD','MIS'=>'MIS'));

		$form->addSubmit('Go');
		$model = $this->add('Model_Account');
		if($this->app->current_branch['name'] != 'Default')
			$model->addCondition('branch_id',$this->app->current_branch['id']);
		$model->addCondition('ActiveStatus',true);
		$model->addCondition('account_type',['Default','Saving']);
		$model->addCondition('Group',['SavingAndCurrent','Sundry Creditor']);

		// $tr_row = $model->join('transaction_row','account_id');
		// // $t_j = $tr_row->leftJoin('transactions','transaction_id');
		// $tr_row->addField('row_trn_id','transaction_id');
		// $model->addExpression('transaction_type')->set(function($m,$q)use($supplier_id){
		// 		$m->add('Model_TransactionRow')->addCondition('account_id')
		// });
		// $agent_tds_j = $model->join('agent_tds','related_account_id');
		// $agent_j = $agent_tds_j->join('agents','agent_id');
		// $agent_tds_j->addField('related_agent_id','agent_id');
		// $agent_tds_j->addField('related_agent_account_id','related_account_id');


		$model->addExpression('agent_account_id')->set(function($m,$q){
			return $this->add('Model_Agent')->addCondition('account_id',$m->getElement('id'))->fieldQuery('id');
		});

		$model->addExpression('agent_tds')->set(function($m,$q){
			$agent_tds = $this->add('Model_AgentTDS')->addCondition('agent_id',$m->getElement('agent_account_id'));
			if($_GET['from_date'])
				$agent_tds->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$agent_tds->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			return $agent_tds->sum('tds');
		});

		// $model->addExpression('to_date_commission')->set(function($m,$q)use($till_date){
		// 	$fy = $this->app->getFinancialYear($till_date);
		// 	$agtds = $this->add('Model_AgentTDS',['table_alias'=>'yc']);
		// 	$agtds->addCondition('created_at','>=',$fy['start_date']);
		// 	$agtds->addCondition('created_at','<',$this->app->nextDate($till_date));
		// 	$agtds->addCondition('agent_id',$m->getElement('agent_account_id'));
		// 	// $agtds->addCondition('branch_id',$m->getElement('branch_id'));
		// 	return $agtds->sum('total_commission');
		// });

		$model->addExpression('agent_total_commission')->set(function($m,$q){
			$agent_tds = $this->add('Model_AgentTDS')->addCondition('agent_id',$m->getElement('agent_account_id'));
			if($_GET['from_date'])
				$agent_tds->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$agent_tds->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			return $agent_tds->sum('total_commission');
		});
		// $model->addExpression('agent_net_commission')->set(function($m,$q){
		// 	$agent_tds = $this->add('Model_AgentTDS')->addCondition('agent_id',$m->getElement('agent_id'));
		// 	if($_GET['from_date'])
		// 		$agent_tds->addCondition('created_at','>=',$_GET['from_date']);
		// 	if($_GET['to_date'])
		// 		$agent_tds->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
		// 	return $agent_tds->sum('net_commission');
		// });


		$model->addExpression('acc_tr_trn_id')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			if($_GET['from_date'])
				$tr_m->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$tr_m->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('transaction_type',[TRA_PURCHASE_ENTRY,TRA_JV_ENTRY]);
			$tr_m->addCondition('side','CR');
			// $tr_m->setLimit(1);
			// return $tr_m->_dsql()->del('fields')->field('COUNT(DISTINCT(transaction_id))');
			return $tr_m->_dsql()->del('fields')->field($q->expr('group_concat(DISTINCT([0]) SEPARATOR ",")',[$tr_m->getElement('transaction_id')]));
			// return $tr_m->_dsql()->del('fields')->field($q->expr('count(DISTINCT([0]))',[$tr_m->getElement('transaction_id')]));
			// return $tr_m->fieldQuery('transaction_id');
		});

		$model->addExpression('agent_trn_id_dr_against_tds_entry')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'tr_trn'));
			if($_GET['from_date'])
				$tr_m->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$tr_m->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
				$tr_m->addCondition('scheme_id','<>',11);
			// $tr_m->addCondition('amountDr','>',0);
			$tr_m->addCondition('transaction_id',$m->getElement('acc_tr_trn_id'));
			// $tr_m->addCondition('transaction_id','in','3478709,3478715,3478741');
			return $tr_m->sum('amountDr');
			// return $tr_m->_dsql()->del('fields')->field($q->expr('group_concat([0] SEPARATOR "+")',[$tr_m->sum('amountDr')]));
		});


		// $model->addExpression('agent_trn_id_dr_against_tds_entry')->set(function($m,$q){
		// 	$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
		// 	if($_GET['from_date'])
		// 		$tr_m->addCondition('created_at','>=',$_GET['from_date']);
		// 	if($_GET['to_date'])
		// 		$tr_m->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
		// 	$tr_m->addCondition('account_id',$q->getField('id'));
		// 	$tr_m->addCondition('transaction_type',[TRA_PURCHASE_ENTRY,TRA_JV_ENTRY]);
		// 	return $tr_m->sum('amountCr');
		// });

		$model->addExpression('total_commissions')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0)+IFNULL([1],0))',
										[
											$m->getElement('agent_total_commission'),
											$m->getElement('agent_trn_id_dr_against_tds_entry')
										]
							);
		});

		// $model = $this->add('Model_AgentTDS');
		$model->addExpression('member_pan')->set($model->refSQL('member_id')->fieldQuery('PanNo'));
		// $model->addExpression('PanNo')->set($model->refSQL('agent_id')->fieldQuery('agent_pan_no'));
		// $model->getElement('branch')->sortable(true);
		if($_GET['filter']){
			$this->api->stickyGET("filter");
			$this->api->stickyGET("from_date");
			$this->api->stickyGET("to_date");
			$this->api->stickyGET("account_type");
			$this->api->stickyGET("supplier");


			// if($_GET['account_type']){
			// 	$model->addCondition('account_type','like',$_GET['account_type']);
			// }

			// if($_GET['from_date']){
			// 	$model->addCondition('created_at','>=',$_GET['from_date']);
			// }

			// if($_GET['to_date']){
			// 	$model->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			// }
			if($supplier_id){
				$model->addCondition('related_type_id',$supplier_id);
			}
			if($acc_id){
				$model->addCondition('id',$acc_id);
			}
			// if($agent_id){
			// 	// throw new \Exception($agent_id, 1);
				
		}else{
			$model->addCondition('id',-1);
		}


		$model->addExpression('tfj')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$transaction_join = $tr_m->join('transactions','transaction_id');
			$transaction_join->addField('narration');
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('side','DR');
			if($_GET['from_date'])
				$tr_m->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$tr_m->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			// $transaction_row_model->addCondition('transaction_acc_id',$supplier_id);
			$tr_m->addCondition('transaction_type_id',[13]);// JV
			$tr_m->addCondition('narration','like','%BEING TDS DEDUCTED%');
			return $tr_m->sum('amountDr');
		});
		$model->addExpression('tft')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$tr_m->addCondition('transaction_type_id',[74]); // TDS
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('side','DR');
			if($_GET['from_date'])
				$tr_m->addCondition('created_at','>=',$_GET['from_date']);
			if($_GET['to_date'])
				$tr_m->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			return $tr_m->sum('amountDr');
			
			// return $tr_m->fieldQuery('account_id');
		});
		
		// $model->addExpression('agent_manully_commission')->set(function($m,$q){
		// 	return $q->expr('SUM([0])',[$m->getElement('agent_trn_id_dr_against_tds_entry')]);
		// });
		$model->addExpression('tds_amount_from_tds')->set(function($m,$q){
			return $q->expr('SUM([0])',[$m->getElement('tft')]);
		});
		$model->addExpression('tds_amount_from_jv')->set(function($m,$q){
			return $q->expr('SUM([0])',[$m->getElement('tfj')]);
		});

		$model->addExpression('amount_from_TDS')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0)+IFNULL([1],0))',
										[
											$m->getElement('tds_amount_from_tds'),
											$m->getElement('agent_tds')
										]
							);
		});

		$model->addExpression('tds_amount')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0)+IFNULL([1],0))',
										[
											$m->getElement('amount_from_TDS'),
											$m->getElement('tds_amount_from_jv')
										]
							);
		});

		$model->addExpression('party_pan_no')->set(function($m,$q){
			return $m->add('Model_Supplier')
						->addCondition('id',$q->getField('related_type_id'))
						->fieldQuery('pan_no');
		
		});
		// $model->addExpression('party_name')->set(function($m,$q){
		// 	return $m->add('Model_Supplier')
		// 				->addCondition('id',$q->getField('related_type_id'))
		// 				->fieldQuery('name');
		
		// })->sortable(true);

		$grid = $this->add('Grid_AccountsBase');
		$grid->addSno();
		$grid->addMethod('format_party',function ($g,$f){
			$supplier = $this->add('Model_Supplier')->addCondition('id',$g->model['related_type_id']);	
			$supplier->tryLoadAny();
			$name = '';
			if($supplier->loaded()){
				$name = $supplier['name'];
			}else{
				$member = $this->add('Model_Member')->load($g->model['member_id']);	
				// $member->tryLoadAny();
				$name = $member['name'];

			}

			$g->current_row[$f] = $name;

		});
		$grid->addMethod('format_pan_nos',function ($g,$f){
			$supplier = $this->add('Model_Supplier')->addCondition('id',$g->model['related_type_id']);	
			$supplier->tryLoadAny();
			$pan = '';
			if($supplier->loaded()){
				$pan = $supplier['pan_no'];
			}elseif ($g->model['account_type'] != 'Saving') {
					$pan = $g->model['pan_no'];
					// $pan = "123";
				// code...
			}
			else{
				$member = $this->add('Model_Member')->load($g->model['member_id']);	
				// $member->tryLoadAny();
				$pan = $member['PanNo'];

			}

			$g->current_row[$f] = $pan;
		});

		$model->addCondition(
				$model->dsql()->orExpr()
				->where($model->getElement('tft','>',0))
				->where($model->getElement('agent_tds','>',0))
			);

		// $grid->addHook('formatRow',function($g){
		// 		if($g->model['to_date_commission'] == ' '){
		// 			$g->setTDParam('party','style/color','red');
		// 			$g->setTDParam('party','style/text-decoration','line-through');
		// 		}
		// 		else{
		// 			$g->model->addCondition('to_date_commission','>=',TDS_ON_COMMISSION);
		// 			$g->setTDParam('party','style/color','');
		// 		}
		// 	});

		// $model->addCondition('amount_from_TDS','>',0);
		// $model->addCondition('tds_amount_from_jv','>',0);
		// $model->addCondition('to_date_commission','>=',TDS_ON_COMMISSION);
		// $model->addCondition(
		// 		$model->dsql()->orExpr()
		// 		->where($model->getElement('to_date_commission','>=',TDS_ON_COMMISSION))
		// 		->where($model->getElement('to_date_commission',""))
		// 	); 

			$model->_dsql()->group($model->dsql()->expr('[0]',[$model->getElement('pan_no')]));
			$model->_dsql()->group($model->dsql()->expr('[0]',[$model->getElement('member_pan')]));
			$model->_dsql()->group($model->dsql()->expr('[0]',[$model->getElement('party_pan_no')]));
		$grid->addColumn('party','party');
		$grid->addColumn('pan_nos','pan_nos');
		$grid->setModel($model,['AccountNumber','account_type','pan_no','pan_nos','party_pan_no','tds_amount'/*,'acc_tr_trn_id'*//*,'to_date_commission'*//*,'agent_total_commission','agent_trn_id_dr_against_tds_entry'*/,/*'agent_manully_commission',*//*'total_commissions'*//*,'party_name'*//*,'amount_from_TDS'*//*,'agent_tds'*//*,'tfj','tft','tds_amount_from_tds'*//*,'tds_amount_from_jv'*/]);

		$grid->addPaginator(1000);
		$grid->addQuickSearch(['member','AccountNumber']);
		// $grid->addTotals(array('total_commission','tds','net_commission'));


		// $grid->removeColumn('party_name');
		$grid->removeColumn('party_pan_no');
		$grid->removeColumn('pan_no');

		$model->setOrder('created_at','desc');

		if($form->isSubmitted()){
			$grid->js()->reload(array(
					'filter'=>1,
					'from_date'=>$form['from_date']?:0,
					// 'agent'=>$form['agent']?:0,
					'to_date'=>$form['to_date']?:0,
					'acc_id'=>$form['account']?:0,
					'agent_id'=>$form['agent']?:0,
					// 'account_type'=>$form['account_type'],
					'supplier'=>$form['supplier']
				))->execute();
		}
	}

}
