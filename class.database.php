<?php
class Database{

	public $sql;
	public $table;
	public $vals;//multi dim arr. k datatype [0] - v value order of array matters eg $dbse->params[s][]=$myid; 
	public $order_by='';
	public $limit='';
	public $ids=0;
	public $column='';
	public $backtrace=0;
	
	private $stmt;

	public function __construct(){
		$this->db=$db;
		$this->html=$html;
		$this->pdo=$pdo;
	}
	public function __destruct(){
	}
	public function query($sql){
		//https://www.php.net/manual/en/pdo.query.php
		//simply runs a given query without any parameters
		$ret_arr=array();
		try {
			$stmt = $this->pdo->query($sql);
			$ret_arr['count'] = $stmt->rowCount();
			$stmt = null;
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	public function fetchassoc($sql,$vals=array()){
		$ret_arr=array();
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($vals);
			while ($row = $stmt->fetch()) {
				$ret_arr[]=$row;
			}
			$stmt = null;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	public function fetchrow($sql,$vals=array()){
		/* (use)
		$sql="select * from lesson_schedule where scheduleid=?";
		$vals=array($id);
		$row=$dbse->fetchrow($sql,$vals);
		*/
		$ret_arr=array();
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($vals);
			$ret_arr= $stmt->fetch();
			$ret_arr['count'] = $stmt->rowCount();
			$stmt = null;
			return $ret_arr;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
			
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	public function fetch(){
		//returns a simple array for single result, and a multi for multiple
		//if ids is empty it selects the entire table
		
		$ret_arr=array();
		$sql="SELECT * FROM ".$this->table; 
		
		if(!empty($this->ids)){
			$conditions_arr=array();
			foreach($this->ids as $k=>$v){
				$injection_attempt=strpos($k,"'");
				if($injection_attempt===false){
					$conditions_arr[]=$k.'=?';
				}else{
					$injection_attempt=1;
				}
			}
			$sql.=' WHERE '.implode(' AND ',$conditions_arr);
		}
		
		if(!empty($this->order_by)){
			$injection_attempt=strpos($this->order_by,"'");
			if($injection_attempt===false){
				$sql.=' ORDER BY '.$this->order_by;
			}else{
				$injection_attempt=1;
			}
		}

		if(!empty($this->limit)){
			$injection_attempt=strpos($this->limit,"'");
			if($injection_attempt===false){
				$sql.=' LIMIT '.$this->limit;
			}else{
				$injection_attempt=1;
			}
		}

		if(!empty($injection_attempt)){
				$ret_arr['error_message'] ="I wonder where I'll float next?";
				$ret_arr['error']=1;
				return $ret_arr;
		}
		
		try {
			$stmt = $this->pdo->prepare($sql);
			$values=array();
			//add ids to value params
			if(!empty($this->ids)){
				foreach($this->ids as $v){
					$values[]=$v;
				}
			}
			$stmt->execute($values);//this has to be executed even if $values is empty!!
			while ($row = $stmt->fetch()) {
				$ret_arr[]=$row;
			}
			$ret_arr['count'] = $stmt->rowCount();
			if($ret_arr['count'] ===1){
				//flatten and return just the row
				$ret_arr=$ret_arr[0];	
				$ret_arr['count']=1;
			}
			
			$stmt = null;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';

			
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}

	
	public function update(){
		//$vals is an array.  col name (key)=val pairs
		/*eg
		$vals['thiscol']='new value';			
		$vals['thatcol']='other new value';			
		$dbse->table='coupons';
		$dbse->vals=$vals;
		$dbse->ids=array('couponid'=>$this_coupon);
		$ret_arr=$dbse->update();
		*/
		$ret_arr=array();
		$column_arr = array_keys($this->vals);
		
		$injection_attempt=strpos($this->table,"'");
		
		$conditions_arr=array();
		foreach($this->vals as $k=>$v){
			$injection_attempt=strpos($k,"'");
			if($injection_attempt===false){
				$conditions_arr[]=$k.'=?';
			}else{
				$injection_attempt=1;	
			}
		}
		$conditions=implode(', ',$conditions_arr);
		
		$idconditions_arr=array();
		
		foreach($this->ids as $k=>$v){
			$injection_attempt=strpos($k,"'");
			if($injection_attempt===false){
				$idconditions_arr[]=$k.'=?';
			}else{
				$injection_attempt=1;
			}
		}
		$idconditions=implode(' AND ',$idconditions_arr);
		
		if(!empty($injection_attempt)){
				$ret_arr['error_message'] ="Little Bobby doesn't live here anymore.";
				$ret_arr['error']=1;
				return $ret_arr;
		}

		$sql="UPDATE ".$this->table." SET ".$conditions." WHERE ".$idconditions;
		$values=array_values($this->vals);
		//add ids to value params
		foreach($this->ids as $v){
			$values[]=$v;
		}
		
		try {
			$stmt = $this->pdo->prepare($sql);
			
			$stmt->execute($values);
			$ret_arr['count'] = $stmt->rowCount();
			$stmt = null;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	public function delete(){
		$ret_arr=array();
		$values=array_values($this->ids);
		$column_arr = array_keys($this->ids);
		$injection_attempt=strpos($this->table,"'");
		
		foreach($this->ids as $k=>$v){
			$injection_attempt=strpos($k,"'");
			if($injection_attempt===false){
				$conditions_arr[]=$k.'=?';
			}else{
				$ret_arr['error_message'] ="Say Hi to Little Bobby from me.";
				$ret_arr['error']=1;
				return $ret_arr;
			}
		}
		$conditions=implode(' AND ',$conditions_arr);
		
		$sql="DELETE FROM ".$this->table." WHERE ".$conditions;

		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($values);
			$ret_arr['count'] = $stmt->rowCount();
			$stmt = null;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;	
	}	
	public function insert(){
		//$vals is an array.  col name (key)=val pairs
		$ret_arr=array();
		$values=array_values($this->vals);
		$injection_attempt=strpos($this->table,"'");
		
		// build the SQL stmt
		$column_arr = array_keys($this->vals);
		foreach($this->vals as $k){
			$qms[]='?';
		}
		$question_marks = implode(',',$qms);
		$columns = implode(',',$column_arr);
		$sql = "INSERT INTO ".$this->table." ($columns) VALUES ($question_marks)";

		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($values);
			$ret_arr['insertid'] = $this->pdo->lastInsertId();

			if(empty($ret_arr['insertid'])){
				$ret_arr['error']=1;
				$ret_arr['error_message']='An unknown insert error occurred.';
			}
			$ret_arr['count'] = $stmt->rowCount();
			$stmt = null;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	
	function multi_insert($arr){
		//$dbse->table='purchased_lessons';
		//$arr[unique_keyA][col_name1]=$value1
		//$arr[unique_keyA][col_name2]=$value2
		//$arr[unique_keyB][col_name1]=$value1
		//$arr[unique_keyB][col_name2]=$value2
		/* eg
		foreach($_SESSION['cart']['scheduleids'] as $scheduleid=>$num){
			for($c=0;$c<$num;$c++){
				$rows_to_insert[$i.$c]['transactionid']=$transactionid;
				$rows_to_insert[$i.$c]['scheduleid']=$scheduleid;
				$rows_to_insert[$i.$c]['first_name']=($i==1?$first_name[0]:'unknown');
				$rows_to_insert[$i.$c]['email']=($i==1?$_POST['email']:'');
				$rows_to_insert[$i.$c]['phone']=($i==1?$_POST['phone']:'');
			}
			$i++;	
		}		
		*/
		
		//SQL bits.
		$rowsSQL = array();
		//values to bind.
		$toBind = array();
	 
		foreach($arr as $k => $row){
			$columnNames = array_keys($arr[$k]);
			$params = array();
			foreach($row as $columnName => $columnValue){
				$param = ":" . $columnName . $k;
				$params[] = $param;
				$toBind[$param] = $columnValue; 
			}
			$rowsSQL[] = "(" . implode(", ", $params) . ")";
		}

		$sql = "INSERT INTO `$this->table` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

		try {
			$stmt = $this->pdo->prepare($sql);
			foreach($toBind as $param => $val){
				$stmt->bindValue($param, $val);
			}
			$stmt->execute();		
			$stmt = null;
			$ret_arr['success']=1;
			
			$this->table='';
			$this->vals=array();
			$this->ids=array();
			$this->limit='';
			$this->order_by='';
		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;
	}
	
	private function escape_backticks($field) {
		return str_replace("`","``",$field);
	}
	private function escape_quotes($field) {
		return str_replace("'","''",$field);
	}
	
	public function injection_protection($vals){
		foreach($vals as $k=>$v){
			 $k=$this->escape_backticks($k);
			 $k=$this->escape_quotes($k);
			 $v=$this->escape_backticks($v);
			 $v=$this->escape_quotes($v);
			 $ret_arr[$k]=$v;
		}
		return $ret_arr;
	}
	
	public function multi_update(){
		//ID should be first value in $vals array
		//updates the same column(s) but multiple rows
		foreach($this->vals as $v){
			$vals[]=$this->injection_protection($v);
			$cols=array_keys($v);
		}
		
		$id=$cols[0];

		$sql='INSERT INTO '.$this->table;
		
		$sql.=' (`'.implode('`,`',$cols).'`) VALUES ' ;
		
	
		foreach($vals as $v){
				$i=0;
			foreach ($cols as $col){
				$temp_vals_arr[$i]="'".$v[$col]."'";
				$i++;
				
				$dup_key_bit[$i]=$col.'=VALUES('.$col.')';
			}	
			
			$vals_arr[]=' ('.implode(',',$temp_vals_arr).')' ;
		}
		$sql.=' '.implode(',',$vals_arr).'  ' ;
		$sql.=' ON DUPLICATE KEY UPDATE ';
		$sql.=' '.implode(',',$dup_key_bit).'  ' ;
		
		$ret_arr=array();
		try {
			$stmt = $this->pdo->prepare($sql);
			$ret_arr = $stmt->execute();	
			$this->table='';
			$this->vals=array();

		}
		catch (PDOException $e) {
			$ret_arr['error_message'] =$this->error_message($e->getMessage());
			$ret_arr['error']=1;
		}
		return $ret_arr;	
/*			

INSERT INTO mytable (id, a, b, c)
VALUES (1, 'a1', 'b1', 'c1'),
(2, 'a2', 'b2', 'c2'),
(3, 'a3', 'b3', 'c3'),
(4, 'a4', 'b4', 'c4'),
(5, 'a5', 'b5', 'c5'),
(6, 'a6', 'b6', 'c6')
ON DUPLICATE KEY UPDATE id=VALUES(id),
a=VALUES(a),
b=VALUES(b),
c=VALUES(c);
		}
*/
		//$sql.=' WHERE '.implode(' AND ',$conditions_arr);
	}

	public function error_message($error_message=''){
		//replaces system generated messages with a generic message for non admin users.
		if(empty($_SESSION['admin']['loggedin'])){
			$error_message='Sorry, something went wrong, please try again. If you continue to see this message please contact support.';
		}
		return $error_message;
	}

}