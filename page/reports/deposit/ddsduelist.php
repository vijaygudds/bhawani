<?php

class page_reports_deposit_ddsduelist extends Page {
	public $title="DDS Due List";

	function init(){
		parent::init();
		
		$this->app->stickyGET('mo');

		$form=$this->add('Form');
		$mo_field = $form->addField('autocomplete\Basic','mo');
		$mo_field->setModel('Model_Mo');

		$agent_field=$form->addField('autocomplete/Basic','agent');
		$agent_field->setModel('Agent');

		$form->addSubmit('GET List');

		$grid=$this->add('Grid_AccountsBase');
		$grid->add('H3',null,'grid_buttons')->set('DDS Due List As On ' . date('d-m-Y',strtotime($_GET['on_date']?:$this->api->today)) );
		
		$account_model=$this->add('Model_Active_Account_DDS');
		$member_join=$account_model->join('members','member_id');
		$member_join->addField('member_name','name');
		$member_join->addField('FatherName');
		$member_join->addField('PhoneNos');
		$member_join->addField('CurrentAddress');
		$member_join->addField('landmark');

		$account_model->addCondition('DefaultAC',false);
		$account_model->addCondition('MaturedStatus',false);

		$account_model->getElement('Amount')->caption('Daily/Monthly Deposit');
		
		$account_model->addExpression('total_deposit')->set(function($m, $q){
			return $m->refSQL('TransactionRow')
					->addCondition('amountCr','>',0)
					->addCondition('transaction_type',TRA_DDS_ACCOUNT_AMOUNT_DEPOSIT)
					->sum('amountCr')
					;
		});

		$account_model->addExpression('total_due_till_date')->set(function($m, $q){
			return $q->expr("IF([3]='DDS',
                                        ((DATEDIFF('[0]',[1])+1)*[2]),
                                        ((DATEDIFF('[0]',[1])+1)*([2]/30))
                                )
                                ",[$m->app->today,$m->getElement('created_at'),$m->getElement('Amount'),$m->getElement('dds_type')]);
		});

		$account_model->addExpression('due_amount')->set(function($m, $q){
			return $q->expr("([0]-[1])",[$m->getElement('total_due_till_date'),$m->getElement('total_deposit')]);
		})->caption('Due Till Date');

		$account_model->addExpression('agent')->set($account_model->refSQL('agent_id')->fieldQuery('name'));
		$account_model->addExpression('agent_code')->set($account_model->refSQL('agent_id')->fieldQuery('AgentCode'));
		$account_model->addExpression('agent_phone')->set($this->add('Model_Member')->addCondition('id',$account_model->refSQL('agent_id')->fieldQuery('member_id'))->fieldQuery('PhoneNos'));
		$account_model->addExpression('agent_mo_id')->set($account_model->refSQL('agent_id')->fieldQuery('mo_id'));
		$account_model->addExpression('agent_mo_name')->set($account_model->refSQL('agent_id')->fieldQuery('mo'))->caption('Mo');

		$account_agent = $account_model->refSQL('agent_id');


		$account_model->addExpression('last_transaction_date')->set(function($m,$q){
				return $this->add('Model_TransactionRow',['table_alias'=>'last_cr_tr_date'])
							->addCondition('account_id',$q->getField('id'))
							->addCondition('amountCr','>',0)
							->addCondition('transaction_type','in',[/*TRA_RECURRING_ACCOUNT_AMOUNT_DEPOSIT,*/ TRA_DDS_ACCOUNT_AMOUNT_DEPOSIT])
							->setLimit(1)
							->setOrder('created_at','desc')
							->fieldQuery('created_at');
			})->type('date');

		if($_GET['filter']){
			$this->api->stickyGET('filter');
			// ALREADY IMPLEMENTED IN EXPRESSIONS
			if($_GET['agent']){
				$this->api->stickyGET('agent');
				$account_model->addCondition('agent_id',$_GET['agent']);
			}
			if($_GET['mo']){
				$account_model->addCondition('agent_mo_id',$_GET['mo']);
			}
		}
		// else
		// 	$account_model->addCondition('id',-1);

		$account_model->addCondition('due_amount','>',0);

		$account_model->add('Controller_Acl');
		// $account_model->setLimit(10);
		$grid->setModel($account_model,array('agent_mo_name','AccountNumber','created_at','last_transaction_date','member_name','FatherName','CurrentAddress','landmark','PhoneNos','Amount','total_deposit','total_due_till_date','due_amount','agent','agent_phone','scheme','dds_type'));
		$grid->addFormatter('CurrentAddress','Wrap');
		if($_GET['agent']){
			$grid->removeColumn('agent_code');
		}
		$grid->addSno();
		// $grid->removeColumn('last_premium');

		// $grid->addMethod('format_balance',function($g,$f){
		// 	$bal = $g->model->getOpeningBalance($on_date=$_GET['on_date']?:$g->api->today,$side='both',$forPandL=false);
		// 	$bal = $bal['Cr'] - $bal['Dr'];
		// 	$g->current_row[$f] = $bal .' Cr';
		// });

		// $grid->addColumn('balance','balance');

		$paginator = $grid->addPaginator(200);
		$grid->skip_var = $paginator->skip_var;
		// $js=array(
		// 	$this->js()->_selector('.mymenu')->parent()->parent()->toggle(),
		// 	$this->js()->_selector('#header')->toggle(),
		// 	$this->js()->_selector('#footer')->toggle(),
		// 	$this->js()->_selector('ul.ui-tabs-nav')->toggle(),
		// 	$this->js()->_selector('.atk-form')->toggle(),
		// 	);

		// $grid->js('click',$js);

		if($form->isSubmitted()){
			$grid->js()->reload(array('mo'=>$form['mo'],'agent'=>$form['agent'],'on_date'=>$form['on_date']?:0,'report_type'=>$form['report_type'],'filter'=>1))->execute();
		}	
	}
}
