<?php class page_reports_loan_duestoreceive extends Page {
	public $title="Dues To Receive List";
	function init(){
		parent::init();
		$this->app->stickyGET('acc_type');
		$this->app->stickyGET('rep_mode');
		$this->app->stickyGET('filter');
		$this->app->stickyGET('dealer');
		$form=$this->add('Form');
		$dealer_field=$form->addField('dropdown','dealer')->setEmptyText('Please Select');
		$dealer_field->setModel('Dealer');
		$form->addField('DatePicker','from_date')->validateNotNull();
		$form->addField('DatePicker','to_date')->validateNotNull();
		$form->addField('DropDown','account_type')->setEmptyText('All')->setValueList( array_combine(explode(",", LOAN_TYPES),explode(",", LOAN_TYPES)));
		$form->addField('DropDown','repayment_mode')->setEmptyText('All')->setValueList(array_combine(['Cash','Cheque','NACH/ECS'],['Cash','Cheque','NACH/ECS']));
		$form->addField('dropdown','legal_status','Recovery Status')->setValueList(array('all'=>'All','is_in_legal'=>'Is In Legal','is_given_for_legal_process'=>'Is In Legal 
Process','in_recovery'=>'Is In Recovery'));
		$form->addField('DropDown','status')->setEmptyText('All')->setValueList(['Active'=>'Activate','DeActivate'=>'DeActivate']);
		$form->addSubmit('Go');
		$grid = $this->add('Grid_AccountsBase');
		$from_date = $this->api->today;
		$to_date = $this->app->nextDate($this->api->today);
		
		if($this->app->stickyGET('from_date')){
			$from_date=$this->api->stickyGET('from_date');
		}
		if($this->app->stickyGET('to_date')){
			$to_date=$this->app->nextDate($this->api->stickyGET('to_date'));
		}
		$due_premiums = $this->add('Model_Premium');
		$account_j=$due_premiums->join('accounts','account_id');
		$member_j = $account_j->join('members','member_id');
		$account_j->addField('DefaultAc');
		$account_j->addField('AccountNumber');
		$account_j->addField('ActiveStatus')->sortable(true);
		$account_j->addField('repayment_mode');
		$account_j->addField('account_type');
		$account_j->addField('branch_id');
		$account_j->hasOne('Agent','agent_id');
		$account_j->addField('is_in_legal');
		$account_j->addField('is_given_for_legal_process');
		$account_j->addField('legal_filing_date');
		$account_j->addField('legal_process_given_date');
		$account_j->hasOne('Dealer','dealer_id')->sortable(true);
		$scheme_j = $account_j->join('schemes','scheme_id');
		$scheme_j->addField('SchemeType');
		$member_j->addField('member_name','name');
		$member_j->addField('FatherName');
		$member_j->addField('PermanentAddress');
		$member_j->addField('PhoneNos');
		$due_premiums->setOrder('SchemeType,AccountNumber');
		
		$due_premiums->addExpression('last_transaction_date')->set(function($m,$q){
				return $this->add('Model_TransactionRow',['table_alias'=>'last_cr_tr_date'])
							->addCondition('account_id',$q->getField('account_id'))
							->addCondition('amountCr','>',0)
							->addCondition('transaction_type','in',[TRA_LOAN_ACCOUNT_AMOUNT_DEPOSIT, TRA_PENALTY_AMOUNT_RECEIVED, TRA_OTHER_AMOUNT_RECEIVED])
							->setLimit(1)
							->setOrder('created_at','desc')
							->fieldQuery('created_at');
			})->type('date')->sortable(true);
		if($_GET['filter']){
			$this->api->stickyGET('status');
			$due_premiums->addCondition('DueDate','>=',$from_date);
			$due_premiums->addCondition('DueDate','<',$to_date);
			// $due_premiums->addCondition('Paid',0);
			if($this->api->stickyGET('status')==="Active"){
				$due_premiums->addCondition('ActiveStatus',true);
			}
			if($this->api->stickyGET('status')==='DeActivate'){
				$due_premiums->addCondition('ActiveStatus',false);
			}
			$due_premiums->addCondition('SchemeType','Loan');
			
			if($_GET['dealer'])
				$due_premiums->addCondition('dealer_id',$_GET['dealer']);
			if($act = $_GET['acc_type']){
				$due_premiums->addCondition('account_type',$act);
			}
			if($repm = $_GET['rep_mode']){
				$due_premiums->addCondition('repayment_mode',$repm);
			}
			$this->api->stickyGET('legal_status');
			switch ($_GET['legal_status']) {
				case 'is_in_legal':
					$due_premiums->addCondition('is_in_legal',true);
					break;
				
				case 'is_given_for_legal_process':
					$due_premiums->addCondition('is_in_legal',false);
					$due_premiums->addCondition('is_given_for_legal_process',true);
					break;
				case 'in_recovery':
					$due_premiums->addCondition('is_in_legal',false);
					$due_premiums->addCondition('is_given_for_legal_process',false);
				default:
					# code...
					break;
			}
		}else{
			$due_premiums->addCondition('id',-1);
		}
		$due_premiums->add('Controller_Acl');
		$grid->setModel($due_premiums,array('AccountNumber','member_name','FatherName','PermanentAddress','PhoneNos','last_transaction_date' 
,'Amount','ActiveStatus','DueDate','agent','dealer','dealer_id','repayment_mode','account_type','is_in_legal','is_given_for_legal_process','legal_process_given_date','legal_filing_date'));
		$grid->addSno();
		$grid->addPaginator(500);
		$grid->addTotals(array('Amount'));
		if($form->isSubmitted()){
			
$grid->js()->reload(['from_date'=>$form['from_date'],'to_date'=>$form['to_date'],'acc_type'=>$form['account_type'],'rep_mode'=>$form['repayment_mode'],'status'=>$form['status'],'legal_status'=>$form['legal_status'],'dealer'=>$form['dealer'], 
'filter'=>1])->execute();
		}
	}
}
