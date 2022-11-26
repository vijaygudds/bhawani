<?php

class Grid_DayBook extends Grid_AccountsBase{
	public $voucher_no=0;
	function init(){
		parent::init();
		$this->addclass('report-font report-margin');
		// $this->addclass('report-margin');
		$this->addColumn('Text','Narra','Narration');
	}
	function setModel($model,$fields=array()){
		parent::setModel($model,$fields);
		$this->addFormatter('voucher_no','smallWrap');
		$this->addFormatter('account','400Wrap');
		$this->addFormatter('amountDr','80Wrap');
		// $this->addFormatter('Narration','text');
		$this->addFormatter('amountCr','80Wrap');
		$this->addTotals(array('amountDr','amountCr'));
	}

	function format_voucherNo($field){
			$this->voucher_no=$this->model->get('voucher_no');
		// if($this->voucher_no==$this->model->get('voucher_no'))
			// $this->current_row[$field]=$this->model->get('Narration');
		// else{
		// }
		parent::format_voucherNo($field);
	}
	function formatRow(){
			// $this->voucher_no=$this->model->get('voucher_no');
		// if($this->voucher_no==$this->model->get('voucher_no'))
			$this->current_row['Narra']=$this->model->get('Narration');
		// else{
		// }
		parent::formatRow();
	}


}
