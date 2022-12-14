<?php

class page_stocknew_ledgers extends Page {
	
	function page_index(){

		// $tabs = $this->add('Tabs');
		// $tabs->addTabURL($this->app->url('./item'),'Item');
		$this->add('Controller_Acl',['default_view'=>false]);
		$this->page_item();
	}

	function page_item(){

		$form = $this->add('Form');
		$form->addField('DropDown','branch')->setEmptyText('All')->setModel('Branch')->addCondition('id',$this->app->current_branch->id);
		$form->addField('DropDown','container')->setEmptyText('All')->setModel('StockNew_Container')->addCondition('branch_id',$this->app->current_branch->id);
		$form->addField('DropDown','container_row')->setEmptyText('All')->setModel('StockNew_ContainerRow')->addCondition('branch_id',$this->app->current_branch->id);
		$form->addField('autocomplete/Basic','member')->setModel('StockNew_Member');
		$form->addField('autocomplete/Basic','item')->setModel('StockNew_Item');
		$form->addField('DatePicker','from_date');
		$form->addField('DatePicker','to_date');
		$type=$form->addField('DropDown','type')->setEmptyText('Please Select Transaction Type')->setModel('StockNew_TransactionTemplate');
		// $type->setValueList(array('Purchase'=>'Purchase','Issue'=>'Issue','Consume'=>'Consume','Submit'=>'Submit','PurchaseReturn'=>'PurchaseReturn','DeadSubmit'=>'DeadSubmit','Transfer'=>'Transfer','Move'=>'Move','Openning'=>'Openning','Sold'=>'Sold','DeadSold'=>'DeadSold','UsedSubmit'=>'UsedSubmit'))->setEmptyText('Please Select Transaction Type');

		$form->addSubmit('Filter');
		$form->add('Controller_StockNewFieldFilter',['branch_field'=>'branch','container_field'=>'container','container_row_field'=>'container_row']);

		$model = $this->add('Model_StockNew_Transaction');

		if($this->api->stickyGET('tr_type')){
			$model->addCondition('transaction_template_type_id',$this->api->stickyGET('tr_type'));
		}	

		if($branch = $this->app->stickyGET('branch')){
			$model->addCondition([['from_branch_id',$branch],['to_branch_id',$branch]]);
		}

		if($container = $this->app->stickyGET('container')){
			$model->addCondition([['from_container_id',$container],['to_container_id',$container]]);
		}

		if($container_row = $this->app->stickyGET('container_row')){
			$model->addCondition([['from_container_row_id',$container_row],['to_container_row_id',$container_row]]);
		}

		if($member = $this->app->stickyGET('member')){
			$model->addCondition([['from_member_id',$member],['to_member_id',$member]]);
		}


		if($item = $this->app->stickyGET('item')){
			$model->addCondition('item_id',$item);
		}

		if($from_date= $this->app->stickyGET('from_date')){
			$model->addCondition('created_at','>=',$from_date);
		}

		if($to_date= $this->app->stickyGET('to_date')){
			$model->addCondition('created_at','<',$this->app->nextDate($to_date));
		}

		if(!$this->app->stickyGET('filter')){
			$model->addCondition('id',-1);
		}
		$exp_array = array('transaction_template_type','from_branch','from_member','from_container','from_container_row','to_branch','to_member','to_container','to_container_row','item','qty','rate','narration','created_at');
		$grid = $this->add('Grid');
		$grid->setModel($model);
		$grid->add('Controller_xExport',array('fields'=>$exp_array ,'output_filename'=>$_GET['tr_type'].' lilst_as_on '. $to_date.".csv"));
		$grid->addPaginator(100);

		if($form->isSubmitted()){
			
			$grid->js()->reload([
				'filter'=>1,
				'branch'=>$form['branch']?:0,
				'container'=>$form['container']?:0,
				'container_row'=>$form['container_row']?:0,
				'member'=>$form['member']?:0,
				'item'=>$form['item'],
				'from_date'=>$form['from_date']?:0,
				'to_date'=>$form['to_date']?:0,
				'tr_type'=>$form['type']
			])->execute();
		}
	}
}
