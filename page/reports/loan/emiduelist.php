<?php

class page_reports_loan_emiduelist extends Page {

	public $title="EMI Due List (Recovery List)";

	function init(){
		parent::init();

		$this->api->stickyGET('filter');
		$this->api->stickyGET('dealer');
		$this->api->stickyGET('report_type');
		$this->api->stickyGET('to_date');
		$this->api->stickyGET('from_date');
		$this->api->stickyGET('loan_type');
		$this->api->stickyGET('report_type');
		$this->api->stickyGET('bike_surrendered');
		$this->api->stickyGET('legal_accounts');
		$this->api->stickyGET('time_collapse_nonpaid_months');
		$this->api->stickyGET('account');


		$form=$this->add('Form');
		$grid=$this->add('Grid_AccountsBase'); 
		$from_date = null;
		$to_date = $this->api->today;

		if($_GET['to_date']){
			$to_date=$this->api->stickyGET('to_date');
		}

		if($_GET['from_date']){
			$from_date=$this->api->stickyGET('from_date');
		}

		if(!$from_date)
			$from_date = '2008-04-01';

		if(!$from_date && $to_date){
			$grid->add('H3',null,'grid_buttons')->set('Loan EMI '.$_GET['report_type'].' As On '. date('d-M-Y',strtotime($to_date)));
		}

		if($from_date && $to_date){
			$grid->add('H3',null,'grid_buttons')->set('Loan EMI '.$_GET['report_type'].' From '.date('d-M-Y',strtotime($from_date)).' To '. date('d-M-Y',strtotime($to_date)));
		}

		$account_field = $form->addField('autocomplete/Basic','account');
		$account_field->setModel('Account');
		$dealer_field=$form->addField('dropdown','dealer')->setEmptyText('Please Select');
		$dealer_field->setModel('ActiveDealer');

		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		$form->addField('dropdown','report_type')->setValueList(array('duelist'=>'Due List','hardlist'=>'Hard List','npa'=>'NPA List','time_collapse'=>'Time Collapse'));
		$form->addField('Number','time_collapse_nonpaid_months');
		$form->addField('dropdown','loan_type')->setValueList(array('all'=>'All','vl'=>'VL','pl'=>'PL','fvl'=>'FVL','sl'=>'SL','hl'=>'HL','other'=>'Other'));
		$form->addField('dropdown','dsa')->setEmptyText('All DSA')->setModel('DSA');
		$form->addField('dropdown','bike_surrendered')->setValueList(['include'=>'Include / All','exclude'=>'Exclude','only'=>'Only']);
		$form->addField('dropdown','legal_accounts')->setValueList(['include'=>'Include / All','exclude'=>'Exclude','only'=>'Only']);
		$form->add('HR');

		$document=$this->add('Model_Document');
		$document->addCondition('LoanAccount',true);
		foreach ($document as $junk) {
			$form->addField('CheckBox','doc_'.$document->id, $document['name']);
		}
		$form->addSubmit('GET List');


		$account_model=$this->add('Model_Active_Account_Loan');
		$q=$account_model->dsql();

		$member_join=$account_model->join('members','member_id');
		$member_join->addField('member_name','name');
		$member_join->addField('FatherName');
		$member_join->addField('PhoneNos');
		$member_join->addField('CurrentAddress');
		$member_join->addField('landmark');
		$member_join->addField('tehsil');
		$member_join->addField('district');
		$member_join->addField('city');
		$member_join->addField('pin_code');
		$member_join->addField('state');

		$account_model->addCondition('DefaultAC',false);

		// $account_model_j=$account_model->join('premiums.account_id','id');
		// $account_model_j->addField('DueDate');
		// $account_model->addCondition('MaturedStatus',false); //???

		$grid_column_array = array('AccountNumber','created_at','maturity_date','last_transaction_date','Amount','due_date','scheme','current_balance','member_name','FatherName','CurrentAddress','landmark','tehsil','district','city','pin_code','state','PhoneNos','dealer','guarantor_name','guarantor_phno','guarantor_father','guarantor_address','last_premium','paid_premium_count','due_premium_count','emi_amount','emi_dueamount','due_panelty','received_panelty','remaning_panelty','other_charges','other_received','remaning_other_amount','gst_amount_dr','gst_amount_cr','gst_due','total','bike_surrendered_by','bike_surrendered_on','bike_returned_on','last_premium_date','interest_rate');

		// $account_model->addExpression('loan_remark',function($m,$q){

		// 	// $doc = $m->add('Model_Document')
		// 	// 		->addCondition('LoanAccount',true)
		// 	// 		->addCondition('id',37);
		// 	$doc = $this->add('Model_DocumentSubmitted')
		// 					->addCondition('documents_id',37)
		// 					->addCondition('accounts_id',$m->getElement('id'))
		// 					->setLimit(1);		
		// 	return $doc->fieldQuery('Description');		
		// });
		$account_model->addExpression('current_balance')->set('(CurrentBalanceDr-CurrentBalanceCr)');

		$account_model->addExpression('paid_premium_count')->set(function($m,$q)use($from_date,$to_date){
			$p_m=$m->refSQL('Premium')
						->addCondition('PaidOn','<>',null);
			if($from_date)
				$p_m->addCondition('DueDate','>=',$from_date);
			if($to_date)
				$p_m->addCondition('DueDate','<',$m->api->nextDate($to_date));
			return $p_m->count();
		})->sortable(true);

		$account_model->addExpression('due_premium_count')->set(function($m,$q)use($from_date, $to_date){
			$p_m = $m->refSQL('Premium')
						->addCondition('PaidOn',null);
			if($from_date)
				$p_m->addCondition('DueDate','>=',$from_date);
			if($to_date)
				$p_m->addCondition('DueDate','<',$m->api->nextDate($to_date));
			return $p_m->count();
		});

		$account_model->addExpression('due_date')->set(function($m,$q){
			$t = $m->refSQL('Premium')->setLimit(1);
			return $q->expr("DAY([0])",array($t->fieldQuery('DueDate')));
			return "'due_premium_count'";
		});

		$account_model->addExpression('last_premium')->set(function($m,$q){
			return $m->RefSQL('Premium')->setOrder('id','desc')->setLimit(1)->fieldQuery('DueDate');
			return "'last_premium'";
		});

		$account_model->addExpression('emi_amount')->set(function($m,$q){
			return $m->RefSQL('Premium')->setOrder('id','desc')->setLimit(1)->fieldQuery('Amount');
			return "'emi_amount'";
		});

		$account_model->addExpression('due_panelty')->set(function($m,$q)use($from_date,$to_date){
			$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_ACCOUNT_AMOUNT_DEPOSIT);
			
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'due_panelty_tr'));
			$tr_m->addCondition('transaction_type_id',$trans_type->id); 
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('created_at','>=',$from_date);
			$tr_m->addCondition('created_at','<',$this->app->nextDate($to_date));

			return $tr_m->sum('amountDr');

			// Previously this was running, and was including un entered amount also, but
			// this was changed as per request ... 
			// Reason, old accounts was not included in penalty
			$p_m = $m->refSQL('Premium');
			if($from_date)
				$p_m->addCondition('DueDate','>=',$from_date);
			if($to_date)
				$p_m->addCondition('DueDate','<',$m->api->nextDate($to_date));
			return $p_m->sum($m->dsql()->expr('IFNULL(PaneltyCharged,0)'));
		});

		$account_model->addExpression('received_panelty')->set(function($m,$q)use($from_date,$to_date){
			$trans_type = $this->add('Model_TransactionType')->tryLoadBy('name',TRA_PENALTY_AMOUNT_RECEIVED);
			
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'received_panelty_tr'));
			$tr_m->addCondition('transaction_type_id',$trans_type->id); 
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('created_at','>=',$from_date);
			$tr_m->addCondition('created_at','<',$this->app->nextDate($to_date));

			return $tr_m->sum('amountCr');
		});


		$account_model->addExpression('remaning_panelty')->set(function($m,$q){
			// return $q->expr('([0]-[1])',[$m->getElement('due_panelty'),$m->getElement('received_panelty')]);
			return $q->expr('(IFNULL([0],0))-(IFNULL([1],0))',[
					$m->getElement('due_panelty'),
					$m->getElement('received_panelty'),
				]);

		});

		$account_model->addExpression('other_charges')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			// $tr_m->addCondition('transaction_type_id',[13, 46, 39]); // JV, TRA_VISIT_CHARGE, LegalChargeReceived
			$tr_m->addCondition('transaction_type','<>',
				[
					'Visit Charge',
					'LEGAL NOTICE CHARGE RECEIVED',
					'CHEQUE RETURN 
					CHARGES RECEIVED',
					'VECHICLE GODOWN RENT RECEIVED',
					'LEGAL EXPENSES RECEIVED',
					'LEGAL NOTICE SENT FOR BIKE AUCTION CHARGE RECEIVED','FINAL RECOVERY NOTICE CHARGE RECEIVED',
					'CHEQUE RETURN NOTICE CHARGE RECEIVED',
					'INSURANCE PROCESSING FEES','SOCIETY NOTICE CHARGE RECEIVED',
					'NACH REGISTRATION FEES CHARGE RECEIVED',
					'NACH TRANSACTION FILE CANCELING CHARGE RECEIVED','NOC HANDLING CHARGE', 
					'FILE CANCEL CHARGE RECEIVED',
					'PRINTING & STATIONERY CHARGE RECEIVED',
					'GST OTHER CHARGE RECEIVED'
				]);
			// $tr_m->addCondition('transaction_type','<>','MEMORANDUM_TRA_ARRAY');
			$tr_m->addCondition('transaction_type','<>','LoanAccountOpen'); 
			$tr_m->addCondition('transaction_type','<>','InterestPostingsInLoanAccounts'); 
			$tr_m->addCondition('transaction_type','<>','PenaltyAccountAmountDeposit'); 
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('account_id',$q->getField('id'));

			return $tr_m->sum('amountDr');
		});

		$account_model->addExpression('other_received')->set(function($m,$q){
			$tr_m = $m->add('Model_TransactionRow',array('table_alias'=>'other_charges_tr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$tr_m->addCondition('transaction_type','<>','PenaltyAmountReceived');
			$tr_m->addCondition('transaction_type','<>',
								[
								'Visit Charge',
								'LEGAL NOTICE CHARGE RECEIVED',
								'CHEQUE RETURN CHARGES RECEIVED',
								'VECHICLE GODOWN RENT RECEIVED',
								'LEGAL EXPENSES RECEIVED',
								'LEGAL NOTICE SENT FOR BIKE AUCTION CHARGE RECEIVED',
								'FINAL RECOVERY NOTICE CHARGE RECEIVED',
								'CHEQUE RETURN NOTICE CHARGE RECEIVED',
								'SOCIETY NOTICE CHARGE RECEIVED',
								'INSURANCE PROCESSING FEES',
								'NACH REGISTRATION FEES CHARGE RECEIVED',
								'NACH TRANSACTION FILE CANCELING CHARGE RECEIVED',
								'NOC HANDLING CHARGE', 
								'FILE CANCEL CHARGE RECEIVED',
								'PRINTING & STATIONERY CHARGE RECEIVED',
								'GST OTHER CHARGE RECEIVED'
								]);
			$received = $tr_m->sum('amountCr');
			$premium_paid = $q->expr('([0]*[1])',[$m->getElement('paid_premium_count'),$m->getElement('emi_amount')]);
			$value = $q->expr('([0]-[1])',[$received,$premium_paid]);

			return $value;
		
		});

		$account_model->addExpression('remaning_other_amount')->set(function($m,$q){
			return $q->expr('(IFNULL([0],0))-(IFNULL([1],0))',[
					$m->getElement('other_charges'),
					$m->getElement('other_received'),
				]);
		});

		$account_model->addExpression('gst_amount_cr')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_cr = $tr_m->sum('amountCr');
			return $memo_amount_cr;
		});
		$account_model->addExpression('gst_amount_dr')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_dr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_dr = $tr_m->sum('amountDr');
			return $memo_amount_dr;
		});

		$account_model->addExpression('gst_due')->set(function($m,$q){
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_cr = $tr_m->sum('amountCr');
			$tr_m = $m->add('Model_Memorandum_TransactionRow',array('table_alias'=>'memo_amount_cr'));
			$tr_m->addCondition('account_id',$q->getField('id'));
			$memo_amount_dr = $tr_m->sum('amountDr');
			// $premium_paid = $q->expr('([0]*[1])',[$memo_amount_cr,$m->getElement('emi_amount')]);
			return $q->expr('([0]-[1])',[$memo_amount_dr,$memo_amount_cr]);
		});

		

		$account_model->addExpression('guarantor_name')->set(function($m,$q){
			$guarantor_m = $m->add('Model_Member',array('table_alias'=>'guarantor_name_q'));
			$ac_join = $guarantor_m->join('account_guarantors.member_id');
			$ac_join->addField('account_id');
			$guarantor_m->addCondition('account_id',$q->getField('id'));
			$guarantor_m->setLimit(1);
			$guarantor_m->setOrder('id');
			return $guarantor_m->_dsql()->del('fields')->field($guarantor_m ->table_alias.'.name');
			return "'guarantor_name'";
		});


		$account_model->addExpression('guarantor_phno')->set(function($m,$q){
			$guarantor_m = $m->add('Model_Member',array('table_alias'=>'guarantor_name_q'));
			$ac_join = $guarantor_m->join('account_guarantors.member_id');
			$ac_join->addField('account_id');
			$guarantor_m->addCondition('account_id',$q->getField('id'));
			$guarantor_m->setLimit(1);
			$guarantor_m->setOrder('id');
			return $guarantor_m->_dsql()->del('fields')->field($guarantor_m ->table_alias.'.PhoneNos');
			return "'guarantor_phno'";
		});

		$account_model->addExpression('guarantor_address')->set(function($m,$q){
			$guarantor_m = $m->add('Model_Member',array('table_alias'=>'guarantor_addr_q'));
			$ac_join = $guarantor_m->join('account_guarantors.member_id');
			$ac_join->addField('account_id');
			$guarantor_m->addCondition('account_id',$q->getField('id'));
			$guarantor_m->setLimit(1);
			$guarantor_m->setOrder('id');
			return $guarantor_m->_dsql()->del('fields')->field($guarantor_m ->table_alias.'.PermanentAddress');
			return "'guarantor_phno'";
		});

		$account_model->addExpression('guarantor_father')->set(function($m,$q){
			$guarantor_m = $m->add('Model_Member',array('table_alias'=>'guarantor_father_q'));
			$ac_join = $guarantor_m->join('account_guarantors.member_id');
			$ac_join->addField('account_id');
			$guarantor_m->addCondition('account_id',$q->getField('id'));
			$guarantor_m->setLimit(1);
			$guarantor_m->setOrder('id');
			return $guarantor_m->_dsql()->del('fields')->field($guarantor_m ->table_alias.'.FatherName');
			return "'guarantor_phno'";
		});

		$account_model->addExpression('dsa_id')->set(function($m,$q){
			return $m->refSQL('dealer_id')->fieldQuery('dsa_id');
		});
		

		
		if($_GET['filter']){

			// $account_model->addCondition('DueDate','<=',$till_date);
			if($_GET['account']){
				$account_model->addCondition('id',$_GET['account']);
			}else{
				if($_GET['dealer']){
					$account_model->addCondition('dealer_id',$_GET['dealer']);
				}


				// don't know why was this condition was made by devendra sir, but now as per again call this is commented and he says now everything is okay
			//if($_GET['bike_surrendered']==='include' AND $_GET['legal_accounts']==='include'){
				switch ($_GET['report_type']) {
					case 'duelist':
						$account_model->addCondition('due_premium_count','>',0);
						$account_model->addCondition('due_premium_count','<=',2);
						$account_model->addCondition('last_premium','>=',$to_date);
						break;
					case 'hardlist':
						$account_model->addCondition('due_premium_count','>',2);
						$account_model->addCondition('due_premium_count','<=',4);
						$account_model->addCondition('last_premium','>=',$to_date);
						break;
					case 'npa':
						$account_model->addCondition('due_premium_count','>=',5);
						$account_model->addCondition('last_premium','>=',$to_date);
						break;

					case 'time_collapse':
						$account_model->addCondition($account_model->dsql()->expr('[0] < "[1]"',array($account_model->getElement('last_premium'),$to_date)));
						break;
					
					default:
						# code...
						break;
				}



				switch ($_GET['loan_type']) {
					case 'vl':
						$account_model->addCondition('AccountNumber','like','%vl%');
						$account_model->addCondition('AccountNumber','not like','%fvl%');
						break;
					case 'pl':
						$account_model->addCondition('AccountNumber','like','%pl%');
						break;
					case 'fvl':
						$account_model->addCondition('AccountNumber','like','%FVL%');
						break;
					case 'sl':
						$account_model->addCondition('AccountNumber','like','%SL%');
						break;
					case 'hl':
						$account_model->addCondition('AccountNumber','like','%HL%');
						break;
					case 'other':
						$account_model->addCondition('AccountNumber','not like','%hl%');
						$account_model->addCondition('AccountNumber','not like','%pl%');
						$account_model->addCondition('AccountNumber','not like','%vl%');
						// $account_model->_dsql()->where('(accounts.AccountNumber not like "%pl%" and accounts.AccountNumber not like "%pl%")');
						break;
				}

				if($this->app->stickyGET('dsa')){
					$account_model->addCondition('dsa_id',$_GET['dsa']);
					if(!$_GET['dealer']) $grid_column_array[] ='dealer';
				}

				switch ($_GET['bike_surrendered	']) {

					case 'only':
						$account_model->addCondition('bike_surrendered',true);
						$account_model->addCondition('bike_surrendered','<>',null);
						$account_model->addCondition('is_bike_returned',false);
						
						break;
					case 'exclude':
						$account_model->addCondition('bike_surrendered',false);
						$account_model->addCondition('is_bike_returned',true);
						$account_model->addCondition('is_bike_returned','<>',null);
						// $account_model->addCondition('is_given_for_legal_process',false);
						break;
					case 'include':
					default:
						break;
				}

				switch ($_GET['legal_accounts']) {
					case 'only':
						$account_model->addCondition('is_given_for_legal_process',true);
						$account_model->addCondition('is_given_for_legal_process','<>',null);
						break;
					case 'exclude':
						$account_model->addCondition('is_given_for_legal_process',false);
						break;
					case 'include':
					default:
						break;
				}

				if($_GET['bike_surrendered'] === "only" AND $_GET['legal_accounts'] === "only"){
					throw new \Exception("Please Select Only One Case", 1);
					
				}


				if($_GET['report_type'] == 'time_collapse' && $_GET['time_collapse_nonpaid_months']){
					$allowed_last_date = date('Y-m-d',strtotime($this->app->today.' -'.$_GET['time_collapse_nonpaid_months'].' months'));
					
					$account_model->addExpression('last_transaction_date_in_time')->set(function($m,$q)use($allowed_last_date){
						return $this->add('Model_TransactionRow',['table_alias'=>'last_cr_tr'])
									->addCondition('account_id',$q->getField('id'))
									->addCondition('amountCr','>',0)
									->addCondition('created_at','>',$allowed_last_date)
									->addCondition('transaction_type','in',[TRA_LOAN_ACCOUNT_AMOUNT_DEPOSIT, TRA_PENALTY_AMOUNT_RECEIVED, TRA_OTHER_AMOUNT_RECEIVED])
									->count();
					})->type('boolean');

					$account_model->addCondition('last_transaction_date_in_time',false);
					$account_model->addCondition($account_model->dsql()->expr('[0] < "[1]"',array($account_model->getElement('last_premium'),$allowed_last_date)));
				}


			}
			

			$account_model->addExpression('last_transaction_date')->set(function($m,$q){
				return $this->add('Model_TransactionRow',['table_alias'=>'last_cr_tr_date'])
							->addCondition('account_id',$q->getField('id'))
							->addCondition('amountCr','>',0)
							->addCondition('transaction_type','in',[TRA_LOAN_ACCOUNT_AMOUNT_DEPOSIT, TRA_PENALTY_AMOUNT_RECEIVED, TRA_OTHER_AMOUNT_RECEIVED])
							->setLimit(1)
							->setOrder('created_at','desc')
							->fieldQuery('created_at');
			})->type('date');

			// $grid->addMethod('format_total',function($g,$f){
			// 	$temp  = $g->current_row_html[$f]= ($g->model['due_premium_count'] * $g->model['emi_amount']) +$g->model['due_panelty']+$g->model['other_charges'];
			// 	if(!isset($g->total)) $g->total=0;
			// 	$g->total += $temp;
			// });

			// $grid->addMethod('format_totals_total',function($g,$f){
			// 	$g->current_row_html[$f]= $g->total;
			// });

			// $grid->addMethod('format_emidue',function($g,$f){

			// 	$temp  = $g->current_row_html[$f]=$g->model['due_premium_count']*$g->model['emi_amount'];
			// 	if(!isset($g->emidue)) $g->emidue=0;
			// 	$g->emidue += $temp;

			// });

			// $grid->addMethod('format_totals_emidue',function($g,$f){

			// 	$g->current_row_html[$f]= $g->emidue;

			// });


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
		$account_model->addExpression('last_premium_date')->set($account_model->refSQL('Premium')->setLimit(1)->setOrder('DueDate', 'desc')->fieldQuery('DueDate'));

		$account_model->addExpression('interest_rate')->set($account_model->refSQL('scheme_id')->fieldQuery('Interest'));

		$grid->addMethod('format_time_over_charge', function ($g, $f) {
			if (strtotime($g->model['last_premium_date'] . '+1 month') > strtotime($g->api->today)) {
				$g->time_over_charge = 0;
				$g->current_row[$f] = 0;
				return;
			}
			$current_interest=0;
			$trans = $this->add('Model_TransactionRow')->addCondition('account_id',$g->model->id);
			$trans->addCondition('created_at','>=',$g->model['last_premium_date']);
			$trans->setOrder('created_at');
			$on_date = $trans['created_at'];
			if(!$on_date) $on_date = $this->api->today;

			$bal = $g->model->getOpeningBalance($g->api->nextDate(date("Y-m-d", strtotime($g->model['last_premium_date'] . '+1 month'))));
			$on_amount = ($bal['CR'] - $bal['DR']) > 0 ? ($bal['CR'] - $bal['DR']) :0;


			$days = $g->api->my_date_diff($on_date, date("Y-m-d", strtotime($g->model['last_premium_date'] . '+1 month')));
			foreach ($trans as $junk) {
				if(!isset($last_transaction_date) or $last_transaction_date !== date('Y-m-d',strtotime($trans['created_at']))){
					$current_interest += round(($on_amount) * ($g->model['interest_rate'] / 100) / 365 * $days['days_total']);
				}

				if($trans['amountDr']!=0) $on_amount -= $trans['amountDr'];
				if($trans['amountCr']!=0) $on_amount += $trans['amountCr'];
				$after_date_not_included = $trans['created_at'];
				$last_transaction_date = date("Y-m-d",strtotime($trans['created_at']));
			}


			$current_interest +=  round(($on_amount) * ($g->model['interest_rate'] / 100) / 365 * $days['days_total']);


			

			// $g->time_over_charge = round(($bal['Dr'] - $bal['Cr']) * ($g->model['interest_rate'] / 100) / 365 * $days['days_total']);

			// $interest = $on_amount * $at_interest_rate * $days['days_total'] / 36500;

			$g->current_row[$f] = $current_interest;
		});

		// $grid->addColumn('time_over_charge', 'time_over_charge');

		$account_model->add('misc\Field_Callback','total')->set(function($m)use($grid){
			return ($m['due_premium_count'] * $m['emi_amount']) +$m['remaning_panelty']+$m['remaning_other_amount'] + $m['gst_due'];
		});




		$account_model->add('misc\Field_Callback','emi_dueamount')->set(function($m){
			return $m['due_premium_count']*$m['emi_amount'];
		});

		// $account_model->_dsql()->group('id');
		$account_model->add('Controller_Acl');

		$grid->setModel($account_model,$grid_column_array);

		if($_GET['filter']){
			// $grid->addColumn('emidue','emi_dueamount');
			// $grid->addColumn('total','total');
			$grid->addOrder()
				->move('emi_dueamount','after','emi_amount')
				->move('gst_amount_dr','after','remaning_other_amount')
				->move('gst_amount_cr','after','gst_amount_dr')
				->move('gst_due','after','gst_amount_cr')
				->move('total','after','gst_due')
				// ->move('time_over_charge','after','emi_dueamount')
				->now();

			$grid->addFormatter('guarantor_address','wrap');
			$grid->addFormatter('CurrentAddress','wrap');
		}
		// $grid->addColumn('text','openning_date');

		$grid->addPaginator(500);
		$grid->addSno();
		$grid->addTotals(array('total','emi_dueamount','other_charges','emi_amount','due_panelty','other_received'));
		$grid->add('Controller_xExport',array('fields'=>array_merge($grid_column_array,array('emi_dueamount','total')),'totals'=>array('total','emi_dueamount','other_charges','emi_amount','due_panelty') ,'output_filename'=>$_GET['report_type'].' lilst_as_on '. $to_date.".csv"));

		$grid->removeColumn('last_premium');
		$grid->removeColumn('last_premium_date');
		$grid->removeColumn('interest_rate');
		// $js=array(
		// 	// $this->js()->_selector('.atk-layout-row')->toggle(),
		// 	$this->js()->_selector('#header')->toggle(),
		// 	$this->js()->_selector('#footer')->toggle(),
		// 	$this->js()->_selector('ul.ui-tabs-nav')->toggle(),
		// 	$this->js()->_selector('.atk-form')->toggle(),
		// 	$this->js()->_selector('.mymenu')->parent()->parent()->toggle(),
		// 	);

		// $grid->js('click',$js);

		if($form->isSubmitted()){


			// if($form['time_collapse_nonpaid_months'] && !is_integer($form['time_collapse_nonpaid_months']))
			// 	$form->displayError('time_collapse_nonpaid_months','Only Integers');

			$send = array('dealer'=>$form['dealer'],'account'=>$form['account'],'to_date'=>$form['to_date']?:0,'from_date'=>$form['from_date']?:0,'report_type'=>$form['report_type'], 'loan_type'=>$form['loan_type'], 'dsa'=>$form['dsa'], 'filter'=>1 ,'legal_accounts'=>$form['legal_accounts'],'bike_surrendered'=>$form['bike_surrendered'],'time_collapse_nonpaid_months'=>$form['time_collapse_nonpaid_months']);
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

