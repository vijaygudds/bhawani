<?php

class page_transactions_tds extends Page {
	public $title = 'TDS Entry';

	public $rows=5;

	function init(){
		parent::init();
		$this->rename('a');

		$this->add('Controller_Acl');
		// Only self branch accounts

		$form = $this->add('Form_Stacked');
		
		$cols = $form->add('Columns');
		$dr_col = $cols->addColumn(6);
		$cr_col = $cols->addColumn(6);

		$cr_col_cols=$cr_col->add('Columns');
		$cr_account_col = $cr_col_cols->addColumn(6);
		$cr_amount_col = $cr_col_cols->addColumn(6);

		$dr_col_cols=$dr_col->add('Columns');
		$dr_account_col = $dr_col_cols->addColumn(6);
		$dr_amount_col = $dr_col_cols->addColumn(6);
		
		$top_col = $form->add('Columns');
		$tds_pan = $top_col->addColumn(6);		
		$tds_per_col = $top_col->addColumn(6);		

		$tds_col_cols = $tds_per_col->add('Columns');
		$tds_acc = $tds_col_cols->addColumn(6);		
		$tds_amount = $tds_col_cols->addColumn(6);
		
		$tds_pan_col_cols = $tds_pan->add('Columns');
		$tds_pan_c = $tds_pan_col_cols->addColumn(11);		

		$cr_account_col->add('H3')->set('Credit');
		$cr_amount_col->add('H3')->set('-');
		$dr_account_col->add('H3')->set('Debit');
		$dr_amount_col->add('H3')->set('-');
		
		$tds_acc->add('H3')->set('TDS');
		$tds_amount->add('H3')->set('-');
		$tds_pan_c->add('H3')->set('PAN NO');

		$account_cr_model=$this->add('Model_Active_Account');
		$account_cr_model->add('Controller_Acl');
		// $account_cr_model->addCondition('branch_id',$this->api->currentBranch->id);
		// $account_cr_model->filter(array($account_cr_model->scheme_join->table_alias.'.SchemeGroup'=>array('%Branch & Divisions%')));

		$account_dr_model=$this->add('Model_Active_Account');
		$account_dr_model->add('Controller_Acl');
		// $account_dr_model->addCondition('branch_id',$this->api->currentBranch->id);
		// $account_dr_model->filter(array($account_dr_model->scheme_join->table_alias.'.SchemeGroup'=>array('%Branch & Divisions%')));

		// $j = 1;

		// for($i=1;$i<=$this->rows;$i++){
		// 	// echo $i."--cr<br/>";
		// 	$j = $j+$i*$i;
			// $section_field = $form->addField('DropDown','section')
			// 								->setValueList(
			// 									['194h'=>'194 H',
			// 									'194j10'=>'194 J (10%)',
			// 									'194j2'=>'194 J (2%)',
			// 									'194c'=>'194 C',
			// 									'194i'=>'194 I',
			// 									'92B'=>'92 B']
			// 								)
			// 				->setEmptyText('Please Select TDS Section');
			// $section_field->js(true)->closest('div.atk-form-row')->appendTo($sec_type_col);
			
			$account_cr = $form->addField('autocomplete/Basic','account_cr');
			// $account->other_field->setAttr('tabindex',$j);
			$account_cr->setModel($account_cr_model,'AccountNumber');
			$account_cr->setCaption(' ');
			$amount_cr = $form->addField('line','amount_cr','');

			$account_cr->js(true)->closest('div.atk-form-row')->appendTo($cr_account_col);
			$amount_cr->js(true)->closest('div.atk-form-row')->appendTo($cr_amount_col);
		// }
			$tds_model=$this->add('Model_Active_Account');
			$tds_model->addCondition('scheme_name','TDS');
			$tds_model->add('Controller_Acl');
			$tds_account_cr = $form->addField('autocomplete/Basic','tds_account');
			// $account->other_field->setAttr('tabindex',$j);
			$tds_account_cr->setModel($tds_model,'AccountNumber');
			$tds_account_cr->setCaption(' ');
			$tds_account_cr->js(true)->closest('div.atk-form-row')->appendTo($tds_acc);
			
			$tds_per = $form->addField('line','tds_amount','');
			$tds_per->js(true)->closest('div.atk-form-row')->appendTo($tds_amount);
			$pan_field = $form->addField('line','pan_no');
			$pan_field->js(true)->closest('div.atk-form-row')->appendTo($tds_pan_c);
		// $j = 0;
		// for($i=1;$i<=$this->rows;$i++){
		// 	$j = $j+$i*$i;

			$account_dr = $form->addField('autocomplete/Basic','account_dr');
			// $account->other_field->setAttr('tabindex',$j);
			$account_dr->setModel($account_dr_model,'AccountNumber');
			$amount_dr = $form->addField('line','amount_dr','');

			$account_dr->setCaption(' ');
			$account_dr->js(true)->closest('div.atk-form-row')->appendTo($dr_account_col);
			$amount_dr->js(true)->closest('div.atk-form-row')->appendTo($dr_amount_col);
		// }

			$account_cr->other_field->js('change',$form->js()->atk4_form('reloadField','pan_no',array($this->api->url(),'selected_account_id'=>$account_cr->js()->val())));
			// $account_cr->other_field->js('change',$pan_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$pan_field->name]),'selected_account_id'=>$account_cr->js()->val()]));

			if($aid = $_GET['selected_account_id']){
				$acc_m = $this->add('Model_Account')->addCondition('AccountNumber',$aid);
				$acc_m->tryLoadAny();
				if($acc_m->loaded()){
					// $form->getElement('pan_no')->set($acc_m['pan_no']);
					if($acc_m['account_type'] == 'Default'){
						$pan_field->set($acc_m['pan_no']);
					}else{
						$member = $this->add('Model_Member')->addCondition('id',$acc_m['member_id']);
						$member->tryLoadAny(); 
						$pan_field->set($member['PanNo']);

					}
				}

			}

		$bottom_cols = $form->add('Columns');
		$jv_type_col = $bottom_cols->addColumn(6);		
		$exec_btn = $bottom_cols->addColumn(6);
		
		$narration_field = $form->addField('Text','narration')->setAttr('tabindex',100);
		$narration_field->js(true)->closest('div.atk-form-row')->appendTo($exec_btn);

		// $jv_type_field = $form->addField('DropDown','jv_type');
		// $transaction_model = $jv_type_field->setModel('TransactionType');
		// $transaction_model->id_field='name';

		// $jv_type_field->js(true)->closest('div.atk-form-row')->appendTo($jv_type_col);
		$form->addSubmit('Execute')->js(true)->closest('div.atk-actions')->appendTo($exec_btn);

		// if($this->api->auth->model['AccessLevel'] < 80)
		// 	$jv_type_field->js(true)->closest('div.atk-form-row')->hide();

		if($form->isSubmitted()){
			$this->validateJV($form);
			// $AccountCredit = $form['amount_dr'];
			try {
				$this->api->db->beginTransaction();
				    $transaction_jv = $this->add('Model_Transaction');
					$transaction_jv->createNewTransaction('Journal Voucher Entry', null,null, $form['narration'],null, array());
					
					// for ($i=1; $i < $this->rows; $i++) {
						if($form['account_dr'])
							$transaction_jv->addDebitAccount($form['account_dr'], $form['amount_dr']);
						if($form['account_cr'])
							$transaction_jv->addCreditAccount($form['account_cr'], $form['amount_cr']);
					// }
						
				    $transaction_tds = $this->add('Model_Transaction');
					$transaction_tds->createNewTransaction('TDS', null,null, "BEING TDS AMOUNT DEDUCT FROM ACCOUNT",null, array());
					// for ($i=1; $i < $this->rows; $i++) {
						if($form['account_cr']){
							// $AccountCredit = $form['amount_cr'];
							$transaction_tds->addDebitAccount($form['account_cr'], $form['tds_amount']);
							// $AccountCredit = $AccountCredit - $form['tds_amount'];
							if($form['tds_amount']){
								$transaction_tds->addCreditAccount($form['tds_account'], $form['tds_amount']);
								// $transaction->addCreditAccount($this->app->current_branch['Code'] . SP . BRANCH_TDS_ACCOUNT_SUPPLIER, $form['tds_amount']);
							}
					}
					
					$transaction_jv->execute();					
					$transaction_tds->execute();					
			    $this->api->db->commit();
			} catch (Exception $e) {
			   	$this->api->db->rollBack();
			   	throw $e;
			}
			
			$form->js(null,$form->js()->reload())->univ()->successMessage('Entry Done')->execute();
		}/*else{
			if($form->hasElement('jv_type'))
				$form->getElement('jv_type')->set('Journal Voucher Entry'); // JV
		}*/

	}

	function validateJV($form){
		$cr_account_no=0;
		$cr_amount_sum=0;
		
		$dr_account_no=0;
		$dr_amount_sum=0;

		// for ($i=1; $i < $this->rows; $i++) { 
			if($form['account_cr']){
				if(!$form['amount_cr'])
					$form->displayError('amount_cr','Amount missing');
				$cr_account_no++;
				$cr_amount_sum += $form['amount_cr']?:0;
				if($form['tds_amount'])
					$cr_amount_sum += $form['tds_amount']?:0;
			}
		// }
		// for ($i=1; $i < $this->rows; $i++) { 
			if($form['account_dr']){
				if(!$form['amount_dr'])
					$form->displayError('amount_dr','Amount missing');
				$dr_account_no++;
				$dr_amount_sum += $form['amount_dr']?:0;
			}
		// }

		// if(abs($cr_amount_sum - $dr_amount_sum) > 0.01)
		// 	$form->js()->univ()->errorMessage('Amount Not Same')->execute();

		if($cr_account_no == 0 or $dr_account_no == 0)
			$form->js()->univ()->errorMessage('Debit or Credit Account not Present')->execute();

		if($cr_account_no > 1 and $dr_account_no > 1)
			$form->js()->univ()->errorMessage('Debit or Credit Account Both must not be more then one')->execute();

	}
}