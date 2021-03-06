<?php

/**
 * 
 * Datagrid Class easier datagrids in wordpress
 * @author Abuzz
 *
 */
class IDGL_DataGrid{
	private $tableName;
	private $rows;
	private $pageNum=10;
	/**
	 * 
	 * Constructor for datagrid class
	 * @param string $tableName
	 * @param string $rows
	 * @param string $query
	 */
	public function IDGL_DataGrid($tableName="",$rows="",$query=""){
		$this->tableName=$tableName;
		$this->rows=$rows;
		$this->query=$query;
	}
	
	/**
	 * 
	 * Final rendering of datagrid ...
	 * @return string
	 */
	public function render(){
		$outStr="";
		
		$outStr.=$this->getPager();
		$outStr.=$this->getFilter();
		$outStr.='<div class="clear"></div>
    			  <table cellspacing="0" class="widefat page fixed">';
		
		$outStr.=$this->getBody();
		$outStr.='</table>';
		$outStr.=$this->getPager();
		return $outStr;
	}
	
	/**
	 * 
	 * Outputs only header ...
	 * @param object from sql query $object
	 * @return string
	 */
	private function getHeader($object){
		$outStr='<thead><tr>';
	    foreach($object as $rowName=>$niceName){
			$outStr.='<th style="" class="manage-column" scope="col">'.$rowName.'</th>';
	    }
	    $outStr.='</tr></thead>';
	    return $outStr;
	}
	
	/**
	 * 
	 * Outputs only body ...
	 * @return string
	 */
	private function getBody(){
		$outStr="";
		global $wpdb;
		
		if($this->query!=""){
			
			$pg=new Paginator($this->query,$_GET["pageid"],$this->pageNum);
		}else{
			// TODO
		}
		if($this->query!=""){
			$objects = $wpdb->get_results($this->query." ".$pg->getLimit(), ARRAY_A);
		}else{
			$objects = $wpdb->get_results("SELECT ".implode(",",array_keys($this->rows))." FROM $this->tableName ".$pg->getLimit(), ARRAY_A);
		}
		
		$outStr.=$this->getHeader($objects[0]);
		foreach($objects as $object){
			$outStr.="<tr>";
			foreach($object as $rowName=>$niceName){
				 $outStr.="<td>".$object[$rowName]."</td>";	
			}
			$outStr.="</tr>";
		}
		return $outStr;
	}
	
	
	/**
	 * 
	 * Output Footer ...
	 */
	
	private function getFooter(){
		
	}
	/**
	 * 
	 * Output Pagination for datagrid ...
	 * @return string
	 */
	private function getPager(){
	if($this->query!=""){
			$pg=new Paginator($this->query,$_GET["pageid"],$this->pageNum);
		}else{
			// TODO
		}
		
	 	$paging=$pg->getPages(Util::curPageURL()."&pageid={i}");
	 	//$limit=$pg->getLimit();
		
		$outStr='
			<div class="tablenav">
        		<div class="tablenav-pages">
            		<span class="displaying-num">Displaying '.$pg->getStartRecords().' - '.$pg->getEndRecords().' step of '.$pg->getRecordsCount().'</span>
            	'.$paging.'
            		</div>
        		<br class="clear"/>
    		</div>
		';
		return $outStr;
	}
	
	
	/**
	 * 
	 * Outputs Filter options ...
	 * @return string
	 */
	
	private function getFilter(){
		$outStr='
			<ul class="subsubsub">
		        <li>
		            <a class="current" href="">All <span class="count">(x)</span></a>
		            |
		        </li>
		        <li>
		            <a href="admin.php?page=AzEventCalendar/AzEventCalendar.php&filter=pending">Pending <span class="count">()</span></a>
					|
		        </li>
				 <li>
		            <a href="admin.php?page=AzEventCalendar/AzEventCalendar.php&filter=approved">Approved <span class="count">()</span></a>
		        	|
		        </li>
		        <li>
		            <a href="admin.php?page=AzEventCalendar/AzEventCalendar.php&filter=upcoming">Upcoming <span class="count">()</span></a>
		        	|
		        </li>
		         <li>
		            <a href="admin.php?page=AzEventCalendar/AzEventCalendar.php&filter=past">Past <span class="count">()</span></a>
		        </li>
		    </ul>
		';
		return $outStr;
	}
}


/**
 * 
 * Pagination Class for Datagrid ...
 * @author Abuzz
 *
 */
class Paginator{
	private $query;
	private $where;
	private $order;
	private $recordsPerPage;
	private $currentPage;
	private $record_count;
	
	/**
	 * 
	 * Constructor for Paginator ...
	 * @param string $query
	 * @param integer $currentPage
	 * @param integer $recordsPerPage
	 * @param string $where
	 * @param string $order
	 */
	public function Paginator($query,$currentPage=0,$recordsPerPage=10,$where="",$order=""){
		$this->query=$query;
		$this->currentPage=$currentPage;
		if($where!=""){
			$this->where=" WHERE ".$where;
		}
		if($order!=""){
			$this->order=" ORDER BY ".$order;
		}
		$this->recordsPerPage=$recordsPerPage;
	}
	
	/**
	 * 
	 * Outputs Pages for specified query ...
	 * @param string $url
	 */
	public function getPages($url){
		global $wpdb;
		$q=" {$this->query} {$this->where} {$this->order}";
		
		$this->record_count=$record_count = $wpdb->get_var($wpdb->prepare($q));

		$outStr="<div class='pageNav'>";
		$iterNo=ceil($record_count/$this->recordsPerPage);
		if($this->currentPage!=0){
			
			$first=str_replace("{i}",0,$url);
			$prev=max($this->currentPage-1,0);
			$prev=str_replace("{i}",$prev,$url);
			
			$outStr.= "<a href='$first'>&laquo; First</a>";
			$outStr.= "<a href='$prev'>&laquo;</a>";
		}
		$bottom=max($this->currentPage-4,0);
		$top=min($bottom+8,$iterNo);
		for($i=$bottom;$i<$top;$i++){
			if($this->currentPage==$i){
				$outStr.= "<span class='on'>".($i+1)."</span>";
			}else{
				$newurl=str_replace("{i}",$i,$url);
				$outStr.= "<a href='$newurl'>".($i+1)."</a>";
			}
		}
		if($this->currentPage!=$iterNo-1){
			$last=str_replace("{i}",$iterNo,$url);
			$next=min($this->currentPage+1,$iterNo);
			$next=str_replace("{i}",$next,$url);
			//$outStr.= "<span class='extend'>&nbsp;...&nbsp;</span>";
			$outStr.= "<a href='$next'>&raquo;</a>";
			$outStr.= "<a href='$last'>Last &raquo;</a>";
		}
		$outStr.= "</div>";
		return $outStr;
	}
	
	
	/**
	 * 
	 * Returns Limit query string ...
	 * @return string query
	 */
	public function getLimit(){
		return " LIMIT ".$this->currentPage*$this->recordsPerPage.",".$this->recordsPerPage;
	}

	/**
	 * 
	 * Returns current page ...
	 * @return integer
	 */
	public function getCurrentPageNum(){
		return $this->currentPage+1;
	}
	/**
	 * 
	 * Returns how many records per page ...
	 * @return integer
	 */
	public function getRecordsperPage(){
		return $this->recordsPerPage;
	}
	/**
	 * 
	 * Return start records
	 */
	public function getStartRecords(){
		return $this->getCurrentPageNum()*$this->recordsPerPage;
	}
	/**
	 * 
	 * Return end records ...
	 */
	public function getEndRecords(){
		return $this->getCurrentPageNum()*($this->recordsPerPage)+$this->recordsPerPage;
	}
	/**
	 * 
	 * Return total records ...
	 */
	public function getRecordsCount(){
		return $this->record_count;
	}
}
