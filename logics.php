<?php


	class MHTD_connectionMaintain{
	
		public $iniSet = "base settings.ini";
		public $prefix = "";
		public $base = "";
		public $userLink = "";
		
	
		public function parseSettingsFile(){
			
			if(!file_exists($this->iniSet)): return false; else: $setts = parse_ini_file($this->iniSet); endif;
			if(isset($setts["host"])): $r_host = $setts["host"]; else: $r_host = $setts[0]; endif;
			
			if(!empty($this->base)):
				$r_base = $this->base; 
			else:
				if(isset($setts["base"])): $r_base = $setts["base"]; else: $r_base = $setts[1]; endif;
			endif;
			
			if(isset($setts["password"])): $r_password = $setts["password"]; else: $r_password = $setts[2]; endif;
			
			if(isset($setts["user"]) || isset($setts[3])):
				if(isset($setts["user"])): $r_user = $setts["user"]; else: $r_user = $setts[3]; endif;
			else:
				$r_user = "root";
			endif;
			
			defined("HOST") or define(strtoupper($this->prefix)."HOST" , $r_host);
			defined("BASE") or define(strtoupper($this->prefix)."BASE" , $r_base);
			defined("PASSWORD") or define(strtoupper($this->prefix)."PASSWORD" , $r_password);
			defined("USER") or define(strtoupper($this->prefix)."USER" , $r_user);
			
			return true;
			
		}

		public function updateUserLink($params,$values = false){
			if(!is_array($params)) $params = explode(",",$params);
			if(!empty($values) && !is_array($values)):
				$values = explode(",",$values);
			elseif(empty($values)):
				$values = array_values($params);
				$params = array_keys($params);
			endif;


			$sign = MHTD_basicFunctions::elseifCompare($this->userLink,"e","?","&");
			$userLink = $this->userLink . $sign;

			$param = "";

			foreach($params as $key=>$par):
				$glue = MHTD_basicFunctions::elseifCompare($param,"e","","&");
				$param .= $glue . $par ."=". $values[$key];
			endforeach;

			$userLink = $userLink . $param;
			$this->userLink = $userLink;
			return $userLink;

		}

		public function checkMsqDefine(){
		
			if(!defined("HOST") || !defined("BASE") || !defined("USER") ):
				$this->parseSettingsFile();
				return true;
			else:
				return false;
			endif;
			
		}
		
		public function checkConnection(){
			is_object($this->mysqli) or $this->connectToBase();
		}
		
		public $mysqli = "";
		
		private function connectToBase(){
			defined("HOST") or $this->parseSettingsFile();
			
			$mysqli = $this->mysqliConnect($this->constant("HOST") ,
			$this->constant("USER") ,
			$this->constant("PASSWORD") ,
			$this->constant("BASE"));
			
			if(is_object($mysqli)): $this->mysqli = $mysqli; else: return $mysqli; endif;
		}
		
		public function constant($name,$prefix = true){
			if($prefix == true) $prefix = $this->prefix;
			return constant(strtoupper($prefix.$name));
		}
		
		public function const_name($name,$prefix = true){
			if($prefix == true) $prefix = $this->prefix;
			return strtoupper($prefix.$name);
		}
		
		protected function mysqliConnect($host,$user,$password,$base){
			$mysqli = new mysqli($host ,
			$user ,
			$password ,
			$base);
			
			if(!$mysqli->connect_error): return $mysqli; else: return $mysqli->connect_error; endif;
		}
		
		protected function userSessId($user){
			return md5($user.time());
		}
		
		protected function createSession($user){
			session_start();
			$_SESSION["id"] = $this->userSessId($user);
			$_SESSION["user"] = $user;
			
			$this->userLink = "user=" . $_SESSION["user"] . "&id=" . $_SESSION["id"];
		}
		
		protected function maintainSession($user){
				session_start();
			if(!isset($_SESSION["id"]) || !isset($_SESSION["user"])):
				return false;
			else:
				
				if($_GET["user"] == $_SESSION["user"] && $_GET["id"] == $_SESSION["id"]):
					
					if(empty($this->userLink)) $this->userLink = "user=" . $_SESSION["user"] . "&id=" . $_SESSION["id"];
					return true;

				elseif($_GET["user"] != $_SESSION["user"] || $_GET["id"] != $_SESSION["id"]):
					if($_GET["user"] != $_SESSION["user"] && $_GET["id"] != $_SESSION["id"]):
						return -1;
					elseif($_GET["id"] != $_SESSION["id"]):
						return -3;
					else:
						return -2;
					endif;
				else:
				return false;
				
				endif;
			
			endif;
		}
		
		public function getInput($inpName,$method = "req"){
			
			if(is_array($inpName)):
				$count = count($inpName) -1;
				$returnArray = true;
			else:
				$inpName = array($inpName);
				$returnArray = false;
				$count = 0;
			endif;
			$result = array();
			
			for($i=0; $i<=$count; $i++):
			
				switch($method){
					case "get":
						if(!isset($_GET[$inpName[$i]])) return false;
						$result[] = $_GET[$inpName[$i]];
					break;
						
					case "post":
						if(!isset($_POST[$inpName[$i]])) return false;
						$result[] = $_POST[$inpName[$i]];
					break;
						
					case "session":
						if(!isset($_SESSION[$inpName[$i]])) return false;
						$result[] = $_SESSION[$inpName[$i]];
					break;
						
					case "cookie":
						if(!isset($_COOKIE[$inpName[$i]])) return false;
						$result[] = $_COOKIE[$inpName[$i]];
					break;
					
					default:
						if(!isset($_REQUEST[$inpName[$i]])) return false;
						$result[] = $_REQUEST[$inpName[$i]];
				}
			
			
			endfor;
			
			if($returnArray):
				return $result;
			else:
				return $result[0];
			endif;
		}
		
		public function redirect($address,$quick = true,$seconds = "10"){
			switch($quick){
				case(true):
					header("Location: " . $address);
				break;
				
				case(false):
					return "<meta http-equiv=\"refresh\" content=\"$seconds;$address\" />";
				break;
			}
		}
		
	
	
	}
	
	class MHTD_createQuery extends MHTD_connectionMaintain{
		
		private function cProp($parent = "table",$properties = array()){
		
		if(!is_array($properties)) return false;
		
		$commulate = array("name"=>"","varchar"=>"","int"=>"","nnull"=>"","autoinc"=>"","def"=>"","primk"=>"","defchset"=>"","col"=>"","eng"=>"","varbinary"=>"","text"=>"");
		
		foreach($properties as $key=>$val):
			
			if($key === 0 || $key == "name"): $commulate["name"] = $val;
			elseif($key === 1 || $key == "varchar" && !empty($val) ): $commulate["varchar"] = " varchar($val)";
			elseif($key === 2 || $key == "int" && !empty($val) ): $commulate["int"] = " int($val) ";
			elseif($key === 3 || $key == "nnull" ): $commulate["nnull"] = " NOT NULL ";
			elseif($key === 4 || $key == "autoinc" ): $commulate["autoinc"] = " auto_increment ";
			elseif($key === 5 || $key == "def" ): $commulate["def"] = " default '$val' ";
			elseif($key === 6 || $key == "primk" && !empty($val) ): $commulate["primk"] = " PRIMARY KEY ($val) ";
			elseif($key === 7 || $key == "defchset" && !empty($val) ): $commulate["defchset"] = " DEFAULT CHARSET $val ";
			elseif($key === 8 || $key == "col" && !empty($val) ): $commulate["col"] = " COLLATE $val ";
			elseif($key === 9 || $key == "eng" && !empty($val) ): $commulate["eng"] = " ENGINE=$val ";
			elseif($key === 10 || $key == "varbinary" && !empty($val) ): $commulate["varbinary"] = " varbinary($val)";
			elseif($key === 11 || $key == "text" ): $commulate["text"] = " text ";
			endif;
			
		
			
		endforeach;
		
		 return $commulate["name"] . $commulate["varchar"] . $commulate["int"] . $commulate["varbinary"] . $commulate["nnull"] . $commulate["autoinc"] . $commulate["def"] . $commulate["primk"]  . $commulate["defchset"] . $commulate["col"] . $commulate["eng"] .$commulate["text"] ;

			}
		
		public function mysqlPrepareInput($name,$method ="request"){
			return $this->mysqlPrepareString($this->getInput($name,$method));
		}
		
		public function mysqlPrepareString($string){
			$this->checkConnection();
			if(is_object($this->mysqli)):$mysqli = $this->mysqli; else: $mysqli = new mysqli; endif;
			return $mysqli->real_escape_string($string);
		}
		
		public function processProps($type ="table",$properties = array(array())){
			$proc = "";
			foreach($properties as $k=>$tbP):
				$k <= 0 or $proc .= ", ";
				$proc .= $this->cProp($type,$tbP);
			endforeach;
			
			return $proc;
		} 
		
		public function cBase($existCheck = false){
			
			$this->checkConnection();
			if($existCheck) $existCheck = " IF NOT EXISTS ";
			
			if(empty($this->base)) return false;
			return "CREATE DATABASE $existCheck" . $this->base;
		}
		
		
		
		public function cTable($tableName = "test",$tableProps = array(array()),$engine="MyIsam",$defChSet = "", $collation = ""){
			
			if(is_array($engine) && empty($defChSet) && empty($collation)):
				foreach($engine as $key=>$e):
					
						if( $key === 0 or $key == "eng") :
							$eng = $e;
						
						elseif($key === 1 or $key == "defchset"):
							$defChSet = $e;
						
						elseif( $key === 2 or $key == "col"):
							$collation = $e;
						endif;
						
						
				endforeach;
				$engine = $eng;
			endif;
			
			$fields = $this->processProps("table",$tableProps);
			$engines = $this->cProp("table",array("eng"=>$engine,"defchset"=>$defChSet,"col"=>$collation));
			
			return sprintf($this->sqlPatterns("crtable"),$tableName,$fields,$engines);
			
		}
		
		protected function passwordCrypt($password,$salt = "zyl zelen ohlyuv"){
			$this->checkConnection();
			$length = round(strlen($password),PHP_ROUND_HALF_UP);
			$passwordTmp = "";
			for($i=0; $length >= $i; $i++):
				$passwordTmp .= crypt($password,$salt);
				$password = substr($password,8,-1);
			endfor;
			$password = $passwordTmp;

			$password = $this->QRequest(sprintf($this->sqlPatterns("password"),$password),"assoc");
			return $password["password"]; 
		}
		
		public function cUser($userName,$password){
			$this->checkConnection();
			$password = $this->passwordCrypt($password);
			$user = sprintf($this->sqlPatterns("cruser"),$userName,$password);
			return $user;
		}
		
		public function qGrant($user,$base,$password,$table="*",$privileges="select,insert,update"){
		
			$this->checkConnection();
			$conn = $this->mysqliConnect(constant($this->prefix."HOST"),constant($this->prefix."USER"),constant($this->prefix."PASSWORD"),"mysql");
			$userAcp = $this->QRequest($this->cSelect("user","user","user='$user'"),"assoc",false,$conn);
			if(empty($userAcp["user"])) return false; 
			$reqHandler = $this->QRequest(sprintf($this->sqlPatterns("grant"),$privileges,$base,$table,$user,$this->passwordCrypt($password)),"none",false,$conn);
			if($conn->error): return $conn->error; elseif($reqHandler == false): return false; else: return true; endif;
			
			
		}
		
		public function qUser($userName,$password){
			$this->checkConnection();
			$quer = $this->cUser($userName,$password);
			$quer = $this->QRequest($quer,"none");
			return $quer;
		}
		
		public function cSelect($fields,$from,$where = "",$addConditions = ""){
		
			$query = sprintf($this->sqlPatterns("select"),$fields,$from,$where . " " . $addConditions);
			return $query;
		
		}
		
		public function QRequest($query,$type = "row",$addValue = false,$mysqlConn = false,$msqlUseRes = false){
			
			$mysqli = "";
			if($mysqlConn != true && !is_object($mysqlConn)):
				$this->checkConnection();
				$mysqli = $this->mysqli;
			elseif(is_object($mysqlConn)):
				$mysqli = $mysqlConn;
			else:
				return false;
			endif;
			
			$msqlUseRes == true ? $msqlUseRes = "MYSQL_USE_RESULT": $msqlUseRes = "MYSQL_STORE_RESULT";
			
			
			switch($type){
				case "row":
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_row();
				break;
				
				case "dseek":
					$query = $mysqli->data_seek($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_row();
				break;
				
				case "array":
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_array();
				break;
				
				case "all": 
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_all();
				break;
				
				case "assoc":
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_assoc();	
				break;
				
				case "fielddirect": 
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_field_direct($addValue);
				break;
				
				case "field":
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_field();
				break;
				
				case "fields": 
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_fields();
				break; 
				
				case "object": 
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->fetch_object();
				break;
				
				case "fieldseek":
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!is_object($query)) return $mysqli->error;
					return $query->field_seek($addValue);
				break;
				
				case "free":
					$query = $mysqli->free_result();
					if(!is_object($query)) return $mysqli->error;
					return $query;
				break;
				
				 default:
					$query = $mysqli->query($query,(int)$msqlUseRes);
					if(!$mysqli->error): return true; else: return $mysqli->error; endif;
				
			}
		}
		
		public function slashEscape($text){
		
			$value = array();
			if(is_array($text)):
				foreach($text as $val):
					$val = str_replace("\\\"","\"",$val);
					$val = stripslashes($val);
					$value[] = $val;
				endforeach;
				$text = $value;
			else:
				$text = stripslashes(str_replace("\\\"","\"",$text));
			endif;
			
			return $text;
			
		}
		
		public function cInsert($table,$fields,$values,$mysqlEscape = true){
		
			if(is_array($fields)) $fields = $this->slashEscape();
			if(is_array($values)):
				if($mysqlEscape == true): foreach($values as $k=>$v): $values[$k] = $this->mysqlPrepareString($v); endforeach; endif;
				$values = "\"" . implode("\",\"",$values) . "\"";	
			endif;
			
			return sprintf(substr($this->sqlPatterns("insert"),0,-1),$table,$fields,$values);
		
		}
		
		public function cDelete($table,$where = "true"){
			if(is_array($table)) $table = implode(",",$table);
			return sprintf($this->sqlPatterns("delete"),$table,$where);
		}
		
		public function cUpdate($table,$fields,$where = "true"){
			if(is_array($fields)):
				$fieldStr = "";
				foreach($fields as $key=>$field):
					$fieldStr .= $key . "=\"" . $this->slashEscape($field) . "\"";
				endforeach;
				$fields = $fieldStr;
			endif;
			
			return sprintf($this->sqlPatterns("update"),$table,$fields,$where);
		}
		
		
		
		public function cInsertUpd($table,$fields,$values,$updates){
			
			$properties = "";
			
			$properties = $this->slashEscape($updates);
			
			if(is_array($updates)):
				$this->slashEscape($updates);
				foreach($updates as $key=>$val):
					$properties .= $key . "=\"" . $val . "\""; 
				endforeach;
			endif;
			$query = sprintf($this->sqlPatterns("insUpdate"),$this->cInsert($table,$fields,$values),$updates);
			return $query;
			
		}
		
		public function sqlPatterns($getPattern = "crUser"){
			$getPattern = strtolower($getPattern);
			$pattern = "";
			switch($getPattern){
			
				case "cruser":
					$pattern = " CREATE USER %s@".constant(strtoupper($this->prefix)."HOST")." IDENTIFIED BY  '%s' ;";
				break;
				
				case "grant":
					$pattern = " GRANT %s ON %s.%s TO %s@".constant($this->prefix."HOST")." IDENTIFIED BY  '%s' ;";
				break;
				
				case "crtable":
					$pattern = "CREATE TABLE %s (%s) %s ;";
				break;
				
				case "select":
					$pattern = "SELECT %s FROM %s WHERE %s ;";
				break;
				
				case "insert":
					$pattern = "INSERT INTO %s (%s) VALUES (%s) ;";
				break;
				
				case "update":
					$pattern = "UPDATE %s SET %s WHERE %s;";
				break;
				
				case "insupdate":
					$pattern = "%s ON DUPLICATE KEY UPDATE %s ;";
				break;
				
				case "password":
					$pattern = "SELECT 	password('%s') AS password ;";
				break;
				
				case "delete":
					$pattern = "DELETE FROM %s WHERE %s ;";
				break;
			
			}
			
			return $pattern;
			
		}
	}
	
	class MHTD_basicFunctions {
		
		
		
		public static function compare(&$value,$condition,$orReturn,$strict = false){
			
			$condition = (string)$condition;
			$bool = false;
			switch($condition):
				
				case "true":
				case "1":
					$bool = (($strict == false && $value == true) || ($strict == false && $value === true));
				break;
				
				case "ntrue":
				case "-1":
					$bool = (($strict == false && $value != true) || ($strict == true && $value !== true));		
				break;
				
				case "false":
				case "0":
					$bool = (($strict == false && $value == false) || ($strict == true && $value === false));		
				break;
				
				case "nfalse":
				case "nf":
					$bool = (($strict == false && $value != false ) || ($strict == true && $value !== false));	
				break;
				
				case "empty":
				case "e":
				case "2":	
					$bool = empty($value);					
				break;
				
				case "nempty":
				case "ne":
				case "-2":
					$bool = !empty($value);
				break;
				
				case "array":
				case "a":
				case "3":
					$bool = is_array($value);
				break;
				
				case "narray":
				case "na":
				case "-3":
					$bool = !is_array($value);
				break;
				
				case "isset":
				case "is":
				case "4":
					$bool = isset($value);
				break;
				
				case "nisset":
				case "nis":
				case "-4":
					$bool = !isset($value);
				break;
				
			endswitch;
			
			if($bool == true):
				
				$value = $orReturn;
				return true;
				
			else:
				
				return false;
				
			endif;
		}
		
		public static function elseifCompare($conditionVar,$condition,$ifTrue,$ifFalse,$evalTrue=false,$evalFalse=false){
			
			$evalFalse = ($evalFalse == true || $evalTrue == 2 || $evalTrue == "both");
			if($evalTrue == true):
				eval('$ifTrue'." = $ifTrue;");
			endif;
			if($evalFalse == true):
				eval('$ifFalse'." = $ifFalse;");
			endif;
			
			$var = self::compare($conditionVar,$condition,false);
			
			if($var === false):
				return $ifFalse;
			else:
				return $ifTrue;
			endif;
			
		
		}
		
		public static function is_assoc($array){
				if(!is_array($array)) trigger_error("Function is_assoc: given attribute is not array",E_USER_WARNING);
			
				foreach($array as $k=>$v):
					if(!is_int($k)) return 1;
				endforeach;
				return 0;
		}
		
		public static function attrStringToArray($attrString,$pattern=array(",","=")){
			
			if(!is_array($pattern) && substr_count($pattern,"\,")):
				$pattern = explode("\,",$pattern);
			elseif(!is_array($pattern)):
				$pattern = array($pattern);
			endif;
			//escape string from undesired occurencies
			$tempPattern = explode("\\\,","\\".implode("\\\,\\",$pattern));
			$attrString = str_replace($tempPattern,array("\\\\1\\\\","\\\\2\\\\","\\\\3\\\\"),$attrString);
			$in = count($pattern);
			foreach($pattern as $p):
				if(!substr_count($attrString,$p))$in--;
			endforeach;
			if(!$in):
				$attrString = str_replace(array("\\\\1\\\\","\\\\2\\\\","\\\\3\\\\"),$pattern,$attrString);
				return $attrString;
			endif;
				
			
			$firstArr = explode($pattern[0],$attrString);
			$depth = count($pattern);
			$result  = array();
			
			if($depth == 1):
				$result = $firstArr;
			endif;
			
			if($depth == 2 || $depth == 3):
				foreach($firstArr as $k=>$v):
					$r = explode($pattern[1],$v);
					$result[$r[0]] = $r[1];
					unset($r);
				endforeach;
				if($depth == 2) $result = str_replace(array("\\\\1\\\\","\\\\2\\\\","\\\\3\\\\"),$pattern,$result);
			endif;
			
			if($depth == 3):
				$res = array();
				$resInc = 0;
				foreach($result as $k=>$v):
					$resInc++;
					$kr = explode($pattern[2],$k);
					$r = explode($pattern[2],$v);
					for($i=0; $i<count($kr); $i++):
						isset($r[$i])?$res[$resInc][$kr[$i]] = str_replace(array("\\\\1\\\\","\\\\2\\\\","\\\\3\\\\"),$pattern,$r[$i]):$res[$resInc][$kr[$i]] = str_replace(array("\\\\1\\\\","\\\\2\\\\","\\\\3\\\\"),$pattern,$r[max(array_keys($r))]);
					endfor;
					unset($kr,$r);
				endforeach;
				$resInc > 1?$result = $res:$result = $res[1];
			endif;

			return $result;
		}
		
		public static function callerFunction($complete = false){
			$caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3);
			
			if(!$complete):
				return $caller[2]['function'];
			else:
				return $caller[2];
			endif;
		}
		
		public static function recursiveExplode($string,$pattern = array(",")){
			if(!is_array($pattern) && substr_count($pattern,"\,")):
				$pattern = explode("\,",$pattern);
			elseif(!is_array($pattern)):
				$pattern = array($pattern);
			endif;
			
			is_array($string)?$result = $string:$result = '';
			$incr = 0;
			$tempPattern = $pattern;
			foreach($pattern as $key=>$val):
				if(!is_array($result)):
					$result = explode($val,$string);
				elseif(is_array($result)):
					foreach($result as $rk=>$rv):
						if(!is_array($rv)):
							$result[$rk] = explode($val,$rv);
						else:
							$result[$rk] = self::recursiveExplode($rv,$tempPattern,true);
						endif;
					
					endforeach;
				endif;
				array_shift($tempPattern);
				$incr++;
			endforeach;
			
			return $result;
			
		}
		
		public static function checkSession($action = "check"){
			
			if (session_status() == PHP_SESSION_NONE):
				if($action == 0 || $action == "check"):
				
					return false;

				else:
					session_start();
					return 2;
				
				endif;
			elseif($action === 3 || $action == "stop"):
				
					session_destroy();
			
			else:
				return true;
			endif;
			
		}
		
		public static function imagesFromDir($directory,$supported=array("gif","jpg","jpeg","png"),$sort = 0){
		
		switch($sort):
			case "0":
			case false:
				$sort = "SCANDIR_SORT_NONE";
			break;
			case "1":
				$sort = "SCANDIR_SORT_ASCENDING";
			break;
			case "2":
				$sort = "SCANDIR_SORT_DESCENDING";
			break;
		endswitch;
		
		$dir = scandir($directory,constant($sort));
		$images = array();
		
		foreach($dir as $val):
			$ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
			if(in_array($ext,$supported)):
				$images[] = $val;
			endif;
		endforeach;
		
		return $images;
		
		}
	
		public static function escapeLink($link){
		
			return str_replace("=","\=",$link);
		
		}

	}
	
//	class MHTD_createHtml {
class MHTD_createHtml {

	public $structure = array(array(),"open"=>true);
	public $htmlStats = array("charset"=>"utf-8","doctype"=>"html5");
	public $condensed = false;
	public $errors = array();

	public function triggerHtmlError($message,$type = "notice"){
		$caller = MHTD_basicFunctions::callerFunction(1);
		$caller["message"] = $message;
		$caller["error_type"] = $type;
		$this->errors[] = $caller;

	}

	private function htmlPatterns($type){

		$type = strtolower($type);

		switch($type){

			case("htmlcompletebrackets"):
			case("hcb"):
				empty($this->condensed) ? $endLine = PHP_EOL : $endLine = "";
				return "<%s/>$endLine";
				break;

			case ("htmlbrackets"):
			case("hb"):
				return "<%s>";
				break;

			case ("htmlclosebracket"):
			case ("hcl"):
				return "</%s>";
				break;

			case ("name"):
			case ("n"):
				return " %s ";
				break;

			case ("value"):
			case("v"):
				return "%s";
				break;

			case ("option"):
			case ("o"):
				return " %s=\"%s\"";
				break;

		}

	}


	public function htmlAttributeList($attribute,$values = false){
		if(!is_array($values)):
			$values = explode(",",$values);
		endif;

		$attrDefVals = array(
			"xmlns" => "http://www.w3.org/1999/xhtml",
			"lang" => "en-us",
			"xml:lang" => "en-us",
			"style" => "",
			"manifest" => "",
			"name" => "",
			"id" => "",
			"class" => "",
			"type"=>"",
			"accesskey"=>"",
			"contenteditable"=>"",
			"contextmenu"=>"",
			"data-"=>"",
			"dir"=>"",
			"draggable"=>"",
			"dropzone"=>"",
			"hidden"=>"",
			"spellcheck"=>"",
			"tabindex"=>"",
			"title"=>"",
			"translate"=>"",
			"onafterprint"=>"",
			"onbeforeprint"=>"",
			"onbeforeunload"=>"",
			"onerror"=>"",
			"onhashchange"=>"",
			"onload"=>"",
			"onmessage"=>"",
			"onoffline"=>"",
			"ononline"=>"",
			"onpagehide"=>"",
			"onpageshow"=>"",
			"onpopstate"=>"",
			"onresize"=>"",
			"onstorage"=>"",
			"onunload"=>"",
			"onblur"=>"",
			"onchange"=>"",
			"oncontextmenu"=>"",
			"onfocus"=>"",
			"oninput"=>"",
			"oninvalid"=>"",
			"onreset"=>"",
			"onsearch"=>"",
			"onselect"=>"",
			"onsubmit"=>"",
			"onkeydown"=>"",
			"onkeypress"=>"",
			"onkeyup"=>"",
			"onclick"=>"",
			"ondblclick"=>"",
			"ondrag"=>"",
			"ondragend"=>"",
			"ondragenter"=>"",
			"ondragleave"=>"",
			"ondragover"=>"",
			"ondragstart"=>"",
			"ondrop"=>"",
			"onmousedown"=>"",
			"onmousemove"=>"",
			"onmouseout"=>"",
			"onmouseover"=>"",
			"onmouseup"=>"",
			"onmousewheel"=>"",
			"onscroll"=>"",
			"onwheel"=>"",
			"oncopy"=>"",
			"oncut"=>"",
			"onpaste"=>"",
			"onabort"=>"",
			"oncanplay"=>"",
			"oncanplaythrough"=>"",
			"oncuechange"=>"",
			"ondurationchange"=>"",
			"onemptied"=>"",
			"onended"=>"",
			"onloadeddata"=>"",
			"onloadedmetadata"=>"",
			"onloadstart"=>"",
			"onpause"=>"",
			"onplay"=>"",
			"onplaying"=>"",
			"onprogress"=>"",
			"onratechange"=>"",
			"onseeked"=>"",
			"onseeking"=>"",
			"onstalled"=>"",
			"onsuspend"=>"",
			"ontimeupdate"=>"",
			"onvolumechange"=>"",
			"onwaiting"=>"",
			"onshow"=>"",
			"ontoggle"=>"",
			"download"=>"",
			"href"=>"",
			"hreflang"=>"",
			"media"=>"",
			"rel"=>"",
			"target"=>"",
			"charset"=>"",
			"coords"=>"",
			"rev"=>"",
			"shape"=>"",
			"code"=>"",
			"object"=>"",
			"align"=>"",
			"alt"=>"",
			"archive"=>"",
			"codebase"=>"",
			"height"=>"",
			"hspace"=>"",
			"vspace"=>"",
			"width"=>"",
			"nohref"=>"",
			"autoplay"=>"",
			"controls"=>"",
			"loop"=>"",
			"muted"=>"",
			"preload"=>"",
			"src"=>"",
			"color"=>"",
			"face"=>"",
			"size"=>"",
			"cite"=>"",
			"alink"=>"",
			"background"=>"",
			"bgcolor"=>"",
			"link"=>"",
			"text"=>"",
			"vlink"=>"",
			"autofocus"=>"",
			"disabled"=>"",
			"form"=>"",
			"formaction"=>"",
			"formenctype"=>"",
			"formmethod"=>"",
			"formnovalidate"=>"",
			"formtarget"=>"",
			"value"=>"",
			"span"=>"",
			"char"=>"",
			"charoff"=>"",
			"valign"=>"",
			"datetime"=>"",
			"open"=>"",
			"compact"=>"",
			"accept-charset"=>"",
			"action"=>"",
			"autocomplete"=>"",
			"enctype"=>"",
			"method"=>"",
			"novalidate"=>"",
			"accept"=>"",
			"frameborder"=>"",
			"longdesc"=>"",
			"marginheight"=>"",
			"marginwidth"=>"",
			"noresize"=>"",
			"scrolling"=>"",
			"cols"=>"",
			"rows"=>"",
			"profile"=>"",
			"noshade"=>"",
			"sandbox"=>"",
			"seamless"=>"",
			"srcdoc"=>"",
			"crossorigin"=>"",
			"ismap"=>"",
			"usemap"=>"",
			"border"=>"",
			"checked"=>"",
			"list"=>"",
			"max"=>"",
			"maxlength"=>"",
			"min"=>"",
			"multiple"=>"",
			"pattern"=>"",
			"placeholder"=>"",
			"readonly"=>"",
			"required"=>"",
			"step"=>"",
			"challenge"=>"",
			"keytype"=>"",
			"for"=>"",
			"sizes"=>"",
			"label"=>"",
			"command"=>"",
			"default"=>"",
			"icon"=>"",
			"radiogroup"=>"",
			"content"=>"",
			"http-equiv"=>"",
			"scheme"=>"",
			"high"=>"",
			"low"=>"",
			"optimum"=>"",
			"data"=>"",
			"classid"=>"",
			"codetype"=>"",
			"declare"=>"",
			"standby"=>"",
			"reversed"=>"",
			"start"=>"",
			"selected"=>"",
			"valuetype"=>"",
			"async"=>"",
			"defer"=>"",
			"xml:space"=>"",
			"scoped"=>"",
			"sortable"=>"",
			"cellpadding"=>"",
			"cellspacing"=>"",
			"frame"=>"",
			"rules"=>"",
			"summary"=>"",
			"colspan"=>"",
			"headers"=>"",
			"rowspan"=>"",
			"abbr"=>"",
			"axis"=>"",
			"nowrap"=>"",
			"scope"=>"",
			"wrap"=>"",
			"sorted"=>"",
			"kind"=>"",
			"srclang"=>"",
			"poster"=>""

		);

		if(substr_count($attribute,"data-")) $attrDefVals[$attribute] = "";
		if(!in_array($attribute,array_keys($attrDefVals))) return false;

		MHTD_basicFunctions::compare($values[0],0,$attrDefVals[$attribute]);
		return sprintf($this->htmlPatterns("o"),$attribute,$values[0]);

	}

	private function htmlClosedTag($type,$extendable = false){

		$open = $this->htmlPatterns("hb");
		$open = sprintf($open,$type.$extendable);
		$close = $this->htmlPatterns("hcl");
		$close = sprintf($close,$type);
		empty($this->condensed)? $endLine = PHP_EOL : $endLine = "";

		return $open . "$endLine %s  $endLine" . $close;
	}

	public function htmlAddChild($parent,$child,$cExtend = true,$newChild = false){

		if(!empty($this->condensed)) return sprintf($parent,$child);
		$result = "";
		MHTD_basicFunctions::compare($parent,"e",PHP_EOL . " %s  " . PHP_EOL);


		preg_match("/(\\t\/i){1}(\/)?[0-9]+/",$parent,$tabIncreaser);

		if(empty($tabIncreaser)):
			$tabIncreaser = 1;
		else:
			$tabIncreaser = preg_replace("/(\\t\/i){1}(\/)/","",$tabIncreaser[0]);
		endif;

		$tab = "\t";
		for($i = 1; $i <= $tabIncreaser; $i++):
			$tab .= "\t";
		endfor;

		if($tabIncreaser == 1):
			$parent = str_replace("%s",$tab. " %s" . $tab ,$parent);
		endif;

		if(!substr_count($child,"%s")):
			$child = str_replace(array(PHP_EOL,"\r\n","\n\r")," \r\r\&\n ",$child);
			$child = str_replace("\n","\n ".$tab ,$child);
			$child = str_replace(" \r\r\&\n ",PHP_EOL,$child);
		endif;

		$result = preg_replace("/(\\t\/i){1}(\/)?[0-9]+/",$tab,$parent);
		if($cExtend == false):
			$result = preg_replace("/&rep&!/","",$result,1);
		endif;


		$result = sprintf($result,$child);
		$tabIncreaser = $tabIncreaser + 1;
		$tabReplacer = "\t/i/".$tabIncreaser;
		$result = str_replace(PHP_EOL . " %s  ". PHP_EOL, PHP_EOL . $tabReplacer." %s  " . PHP_EOL . $tabReplacer  ,$result);

		return $result .PHP_EOL;
	}

	public function htmlConstruct($input,$open = true,$newChild = false){

		#$input = input string
		#$open = set new child as extendible (default - extendible)
		#$newChild = set current element as sub child (default - false)

		if($newChild == true):
			$input = array($input,"open"=>$open);
		endif;
		$activeChild = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
		if(empty($activeChild)):
			$this->htmlStructEditPath($this->htmlCurrentChild(),array("","open"=>true));
			$activeChild = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
		endif;
		$max = MHTD_basicFunctions::elseifCompare($activeChild,'a',max(array_keys($activeChild)),0);



		if($open == true):
			if(empty($activeChild[$max])):
				$activeChild[$max] = $input;
			else:
				$activeChild[$max + 1] = $input;
			endif;
			$activeChild["open"] =1;
		else:
			if(empty($activeChild[$max])):
				$activeChild[$max] = $input;
			else:
				$activeChild[$max + 1] = $input;
			endif;
			$activeChild["open"] =0;
		endif;

		$this->htmlStructEditPath($this->htmlCurrentChild(),$activeChild);


	}

	//next functions are for testing purpose
	public function veryTestful($taste){
		if($taste !=1 ):
			echo $taste . "\n\r <br/>";
			$taste--;
			$this->veryTestful($taste);
		else:
			return "End!";
		endif;

	}

	public function htmlConstruct1($input,$open = true,$newChild = false){

		#accessing session main variable
		$subStructure = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);

		#declaring max array key value for first substructure
		if(count($subStructure) > 0):
			$max = max(array_keys($subStructure));
		else:
			$max = 0;
			$first = true;
		endif;

		#set array initialization if not set yet
		if(!isset($subStructure[$max]) || !is_array($subStructure[$max])):
			$this->htmlStructEditPath($this->htmlCurrentChild(),array("","open"=>true));
			$subStructure = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
		endif;

		#looping around first substructure until open child is found
		for($i=0; $i<=$max; $i++):
			if(is_array($subStructure[$i]) && isset($subStructure[$i]["open"]) && $subStructure[$i]["open"] == true):
				$currentChild = $i;
				break;
			endif;
		endfor;

		#forces new child set if true give n in attributes
		if($newChild == true):
			if(!isset($first)):
				$currentChild = $max + 1;
				$this->htmlStructEditPath($this->htmlCurrentChild(),array("","open"=>true));
			endif;
		endif;

		#sets value in subarray and sets sub array if not set
		$currentChild = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
		if(empty($currentChild) && !is_array($currentChild)):
			$currently = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
			$currently[0] = $input;
			$this->htmlStructEditPath($this->htmlCurrentChild(),$currently);
		else:
			$currently = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
			$currently[] = $input;
			$this->htmlStructEditPath($this->htmlCurrentChild(),$currently);
		endif;

		#sets current child's open/close state 
		if($open == false):
			$currently = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
			$currently["open"] = 0;
			$this->htmlStructEditPath($this->htmlCurrentChild(),$currently);
		else:
			$currently = $this->htmlStructExtractPath($this->htmlCurrentChild(),0);
			$currently["open"] = 1;
			$this->htmlStructEditPath($this->htmlCurrentChild(),$currently);
		endif;
	}

	//>>end testing purpose functions

	public function htmlCheckChild($structure){
		$max = max(array_keys($structure));
		for($i=$max; $i>=0; $i--):
			if(is_array($structure[$i]) && isset($structure[$i]["open"]) && $structure[$i]["open"] == true):
				$currentChild = $i;
				break;
			endif;
		endfor;
		if(!isset($currentChild)) return false;
		return  (int)$currentChild;
	}

	public	function htmlCurrentChild(){
		$p = &$this->structure;
		$cc = $this->htmlCheckChild($p);
		$path = array();


		if($cc === false):
			$pMax = count($p) - 2;
			$path[] = $pMax;
		else:
			$cch = $cc;
			$path[] = $cc;
			$breakOut =0;
			while($cch !== false):
				if(!isset($path) or $path[0] === false):

					$path[] = $cc;
					$cch = $this->htmlCheckChild($p[$path[0]]);

				else:
					$outPath = "['" . implode("']['",$path) ."']";
					$evaluated = "";
					eval('$evaluated = $p'."$outPath;");
					if(is_array($evaluated)):
						$cch = $this->htmlCheckChild($evaluated);
						if($cch === false) break;
						$path[] = $cch;
					else:
						$cch = false;
					endif;

				endif;
			endwhile;
		endif;
		return $path;
	}

	public function htmlStructExtractPath($path,$returnString = true ){
		$variable = $this->structure;
		$result = "";
		if($returnString === -1): $internal = true; else: $internal = false; endif;
		$outPath = "['" . implode("']['",$path) ."']";
		eval('$result = $variable'."$outPath;");
		if($returnString == true):
			if(is_array($result)):
				return $result[0];
			else:
				return $result;
			endif;
		endif;
		return $result;
	}

	public function htmlStructEditPath($path,$input){
		$variable = &$this->structure;
		$result = "";
		$outPath = "['" . implode("']['",$path) ."']";
		$arrayCheck = false;

		if($arrayCheck):
			if(is_array($input)):
				eval('$variable'."$outPath".'[0]'.' = $input;');
			else:
				eval('$variable'."$outPath".'[0]'." = \"$input\";");
			endif;
		else:
			if(is_array($input)):
				eval('$variable'."$outPath".' = $input;');
			else:
				eval('$variable'."$outPath = \"$input\";");
			endif;
		endif;
	}

	public function htmlArrayKeysDecode(&$result,$returnValue = 0){
		$outPath = "['" . implode("']['",$result) ."']";
		if($returnValue == true):
			eval('$result = $result'."$outPath;");
		else:
			$result = '$result'.$outPath;
		endif;
	}

	public function parseStructure($structure = false,$strict = false){
		if($structure == false)$structure = $this->structure;

		if(is_array($structure)):

			$sprintable = array();
			$nonSprintable = array();
			$parseAlone = array();
			foreach($structure as $key=>$struct):
				if($key === "open"): continue; endif;
				if(is_array($struct)):
					$current = $this->parseStructure($struct,1);
				else:
					$current = $struct;
				endif;
				$match = preg_match_all("/%(?:\d+\$)?[+-]?(?:[ 0]|'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/",$current);

				if($match != 0):
					$sprintable[$key] = $current;
				else:
					$nonSprintable[$key] = $current;
				endif;
			endforeach;


			if(count($sprintable)):

				$sprintable = array_reverse($sprintable,true);
				$nsprtString = "";
				$nsprtStringTmp = "";
				$sprtString = "";

				foreach($sprintable as $key=>$sprt):
					foreach($nonSprintable as $k=>$nsprt):
						if($key < $k):
							if(count($nonSprintable) > 1 && empty($this->condensed)): $newLine = PHP_EOL; else: $newLine = false; endif;
							$nsprtStringTmp .= $nsprt.$newLine;
							unset($nonSprintable[$k],$newLine);
						endif;
					endforeach;
					if($nsprtString == ""):
						$nsprtString = $this->htmlAddChild($sprt,$nsprtStringTmp);
					else:
						$nsprtString = $this->htmlAddChild($sprt,$nsprtString.$nsprtStringTmp);
					endif;
					$nsprtStringTmp = "";
				endforeach;
				if(count($nonSprintable)):
					$nsprtString = implode("",$nonSprintable) . $nsprtString;
				endif;

			else:
				$nsprtString = implode("",$nonSprintable);
			endif;

			return $nsprtString;

		endif;
	}

	public function htmlStructuredOutput(){
		$output = "";
		foreach($this->structure[0] as $key=>$struct):
			$output .= $struct[0];
		endforeach;

		return $output;
	}

	public function is_xhtml(){
		if(!substr_count($this->htmlStats["doctype"],"x")) return false;
		return true;
	}

	public function is_html5($other = false){
		if($other == true): $type = $other; else: $type = 5; endif;
		if(!substr_count($this->htmlStats["doctype"],$type)) return false;
		return true;
	}

	public function xnl2br($input){
		if(!$this->is_xhtml() == true):
			$result = nl2br($input,false);
		else:
			$result = nl2br($input);
		endif;
		empty($this->condensed) or $result = str_replace(array("\n",PHP_EOL),"",$result);
		return $result;
	}



	public function htmlTagSupportedAttribute($tag,$attributeList = false){

		is_array($tag) or $tag = MHTD_basicFunctions::attrStringToArray($tag,",\,=");
		if(is_array($tag)):

			$assoc = MHTD_basicFunctions::is_assoc($tag);
			if(!$assoc):
				$tag = array_values($tag);
				$attributeList = array_values($attributeList);
				$retag = array();
				$i = 0;
				foreach($tag as $t):

					if(isset($attributeList[$i])):
						$retag[$attributeList[$i]] = $t;
						$i++;
					endif;

					if(!isset($attributeList[$i])):
						unset($i);
						break;
					endif;

				endforeach;
				$tag = $retag;
				unset($retag);
			else:
				$count = 0;
				foreach(array_keys($tag) as $val):
					if(!in_array($val,$attributeList)):
						if(substr_count($val,"data-")) continue;
						$count++;
						unset($tag[$val]);
					endif;
				endforeach;
				if($count) $this->triggerHtmlError("$count unsupported attributes provided! All unsupported attributes will be omitted.","doctype|warning");
			endif;

			$result = "";

			foreach($tag as $key=>$ta):
				$result .= $this->htmlAttributeList($key,$ta);
			endforeach;

			return $result;
		else:
			if(in_array($tag,$attributeList)):
				return $this->htmlAttributeList($tag);
			else:
				$this->triggerHtmlError("Unsupported attribute provided! The unsupported attribute will be omitted.","doctype|warning");
				return false;
			endif;
		endif;
	}



	public function htmlTags($tag,$closed = true){

		if(!is_array($tag)):
			$tag = strtolower($tag);
		else:
			$tagAttributes = $tag;
			if(isset($tag[0])):
				$tag = strtolower($tag[0]);
			elseif(isset($tag["tag"])):
				$tag = strtolower($tag["tag"]);
			endif;
		endif;
		empty($this->condensed) ? $endLine = PHP_EOL : $endLine = "";


		$skip = array("html","title");

		if(!in_array($tag,$skip) && !substr_count($tag,"doctype") && !substr_count($tag,"charset")):
			$attrList = $this->htmlTagAttributeList($tag);
			if($attrList == false):
				$this->triggerHtmlError("Unsupported tag! The unsupported tags will be omitted.","doctype|warning");
				return false;
			elseif($attrList == -1):
				$attrList = $this->htmlTagAttributeList(1);
				$this->triggerHtmlError("Unsupported tag, but commonly used out of specification! The unsupported tags will be present but error will be thrown and html validation won't be possible.","doctype|warning");
			endif;
			if(isset($tagAttributes)):
				$result = $this->htmlTagSupportedAttribute($tagAttributes,$attrList);
			else:
				$result = false;
			endif;
			if($closed == true):
				return $this->htmlClosedTag($tag,$result);
			else:
				if($this->is_xhtml()):
					return sprintf($this->htmlPatterns("hb"),$tag.$result." /");
				else:
					return sprintf($this->htmlPatterns("hb"),$tag.$result);
				endif;
			endif;
		endif;


		switch ($tag):

			case "html":

				$attrList = array("xmlns","lang","xml:lang");
				unset($tagAttributes[array_search($tag,$tagAttributes)]);
				if(!$this->is_xhtml())unset($attrList[0],$attrList[2]);
				if($this->is_html5()):unset($attrList[1]); $attrList[] = "manifest";
				elseif($this->is_html5(3)):unset($attrList[1]); endif;
				$result = $this->htmlTagSupportedAttribute($tagAttributes,$attrList);

				return $this->htmlClosedTag($tag,$result);

				break;


			case "title":
				$return = $this->htmlClosedTag("title");
				if(isset($tagAttributes)):
					if(isset($tagAttributes[0])):
						$tagAttributes["name"] = $tagAttributes[1];
					endif;
					empty($this->condensed) ? $tab = "\t" : $tab = "";
					$return = sprintf($return,$tab.$tagAttributes["name"]);
				endif;
				return $return . $endLine;
				break;


			default :

				if(substr_count($tag,"doctype")):
					$tag = str_replace("doctype","",$tag);
					$value = "";
					switch($tag):

						case "strict":
							$value = '!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"';
							break;

						case "transit":
							$value = '!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"';
							break;

						case "frameset":
							$value = '!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"';
							break;

						case "xstrict":
							$value = '!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"';
							break;

						case "x11strict":
							$value = '!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"';
							break;

						case "xtransit":
							$value = '!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
							break;

						case "xframeset":
							$value = '!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd"';
							break;

						case "html3":
							$value = '!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN"';
							break;

						case "html2":
							$value = '!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN"';
							break;

						case "html5":
						default:
							$value = '!DOCTYPE html';
							$tag = "html5";
							break;

					endswitch;

					$this->htmlStats["doctype"] == $tag or $this->htmlStats["doctype"] = $tag;
					return sprintf($this->htmlPatterns("hb"),$value).$endLine;

				elseif(substr_count($tag,"charset")):

					$tag = str_replace("charset","",$tag);
					if(empty(str_replace(" ","",$tag))) $tag = $this->htmlStats["charset"];
					$value = "";

					if($this->htmlStats["doctype"] == "html5"):

						$value = 'meta charset="'.$tag.'"';
						return sprintf($this->htmlPatterns("hb"),$value);

					elseif(substr_count($this->htmlStats["doctype"],"x")):

						$value = 'meta http-equiv="Content-Type" content="text/html; charset='.$tag.'"';
						return sprintf($this->htmlPatterns("hcb"),$value);

					else:

						$value = 'meta http-equiv="Content-Type" content="text/html; charset='.$tag.'"';
						return sprintf($this->htmlPatterns("hb"),$value);

					endif;

				endif;

		endswitch;

		return false;

	}

	private function switchDataType($input,$dataType = 0,$closed = true){
		if($dataType == 0):
			return $this->htmlTags($input,$closed);
		elseif($dataType == 1):
			return $this->attrToTag($input,$closed);
		endif;

		return $input;
	}

	public function closeActiveChild($level = -1){

		$currentChild = $this->htmlCurrentChild();

		if((count($currentChild) == 1 && $currentChild[0] == 0) ||$currentChild == 0):
			return false;
		endif;

		if($level > 0):
			$level = count($currentChild) - $level;
			$level = -$level;
		endif;

		for($i=0; $i>$level; $i--):
			$temp = $this->htmlCurrentChild();
			//print_r($temp);
			$temp[max(array_keys($temp))+1] = "open";
			$this->htmlStructEditPath($temp,0);
			unset($temp);
		endfor;
	}

	public function attrToTag($attributes,$closed = true){
		if(substr_count($attributes,"|")):
			$attrs = MHTD_basicFunctions::attrStringToArray($attributes,",\,=\,|");
		else:
			$attrs = MHTD_basicFunctions::attrStringToArray($attributes);
		endif;
		return $this->htmlTags($attrs,$closed);
	}

	public function newChild($input,$dataType = 0,$level = -1,$closed = true){
		#$input = input string
		#$level = parent child's level, negative - levels above current level, if positive how many levels below top level

		$input = $this->switchDataType($input,$dataType,$closed);

		$this->closeActiveChild($level);

		$this->htmlConstruct($input,1,1);
		return true;

	}

	public function newSubchild($input,$dataType = 0){

		$input = $this->switchDataType($input,$dataType);

		$this->htmlConstruct($input,1,1);

	}

	public function newElement($input,$dataType = 2,$level = -1){

		$input = $this->switchDataType($input,$dataType,0);
		$this->closeActiveChild($level);
		$this->htmlConstruct($input,1);
		return true;

	}

	public function newSubelement($input,$dataType = 2){

		$input = $this->switchDataType($input,$dataType,0);

		$this->htmlConstruct($input,1);

	}

	public function finishStructure($structure){

		foreach($structure as $key=>$struct):
			if(is_array($struct)):
				if(isset($struct['open'])) $struct["open"] = 0;
				$struct = $this->finishStructure($struct);
				$structure[$key] = $struct;
			endif;
		endforeach;

		return $structure;
	}

	public function htmlRepetition($code = "",$values = "",$elementType = "Subchild,Subchild",$nested = false){

		if(!is_array($code)):
			if(!substr_count($code,"=")): $code = str_replace(",","=%s,",$code) . "=%s"; endif;
			$code = MHTD_basicFunctions::attrStringToArray($code,array(",","=","|"));
		endif;

		if(!is_array($elementType)):
			if(empty($elementType)): $elementType = "subc,subc"; endif;
			$elementType =  MHTD_basicFunctions::attrStringToArray($elementType,array(","));
			if(!isset($elementType)): $elementType = array($elementType); endif;
		endif;

		if(!is_array($values)):
			$values = MHTD_basicFunctions::recursiveExplode($values,array(":::",",","|"));
		endif;

		$innerStructure = new MHTD_createHtml;
		$innerStructure->htmlStats = $this->htmlStats;


		foreach($values as $valKey=>$val):
			$tCode = $code;
			$tElementType = $elementType;
			foreach($val as $realKey=>$realVal):
				if(count($tCode)>=1):
					$codeValue = array_shift($tCode);
				else:
					$codeValue = $tCode[min(array_keys($tCode))];
				endif;
				$inc = 0;
				foreach($codeValue as $realCodeKey=>$realCodeVal):
					if($realCodeVal == false || $realCodeVal == "%s"):
						$codeValue[$realCodeKey] = $realVal[$inc];
						$inc++;
					endif;

				endforeach;


				if(substr_count($tElementType[0],1)):
					$inpType = 1;
				elseif(substr_count($tElementType[0],2)):
					$inpType = 2;
				else:
					$inpType = 0;
				endif;

				if(substr_count(strtolower($tElementType[0]),"subc")):
					$innerStructure->newSubchild($codeValue);
				elseif(substr_count(strtolower($tElementType[0]),"sube")):
					$innerStructure->newSubelement($codeValue,$inpType);
				elseif(substr_count(strtolower($tElementType[0]),"el")):
					$innerStructure->newElement($codeValue,$inpType);
				elseif(substr_count(strtolower($tElementType[0]),"ch")):
					$innerStructure->newChild($codeValue);
				endif;
				if(count($tElementType) > 1): array_shift($tElementType); endif;
			endforeach;
			if($nested == false): $innerStructure->structure = $this->finishStructure($innerStructure->structure); endif;
		endforeach;


		return $innerStructure->structure;



	}

	public function childSwitch($input,$case = 0,$type = 1,$level = -1){
		if(!is_int($case)):
			if(substr_count(strtolower($case),"subc")):
				$case = 1;
			elseif(substr_count(strtolower($case),"sube")):
				$case = 3;
			elseif(substr_count(strtolower($case),"el")):
				$case = 2;
			elseif(substr_count(strtolower($case),"ch")):
				$case = 0;
			endif;
		endif;

		switch($case):

			case "1":
				return $this->newSubchild($input,$type);
				break;

			case 2:
				return $this->newElement($input,$type,$level);
				break;

			case 3:
				return $this->newSubelement($input,$type);
				break;

			default:
				return $this->newChild($input,$type,$level);
		endswitch;
	}

	public function newLine($count = 1,$format = 0,$level = -1){
		$newLine = "";
		for($i=1; $i<=$count; $i++):
			$newLine .= "\n";
		endfor;
		$newLine = $this->xnl2br($newLine);

		switch($format):
			case 1:
				return $this->newElement($newLine,2,$level);
				break;

			default:
				return $this->newSubelement($newLine,2);
		endswitch;
	}


	public function htmlTagAttributeList($tag){

		$doctype = $this->htmlStats["doctype"];

		switch($doctype):
			case "html5":
				$globalAttributes = "accesskey,class,contenteditable,contextmenu,data-,dir,draggable,dropzone,hidden,id,lang,spellcheck,style,tabindex,title,translate";
				$eventAttributes = "onafterprint,onbeforeprint,onbeforeunload,onerror,onhashchange,onload,onmessage,onoffline,ononline,onpagehide,onpageshow,onpopstate,onresize,onstorage,onunload,onblur,onchange,oncontextmenu,onfocus,oninput,oninvalid,onreset,onsearch,onselect,onsubmit,onkeydown,onkeypress,onkeyup,onclick,ondblclick,ondrag,ondragend,ondragenter,ondragleave,ondragover,ondragstart,ondrop,onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onmousewheel,onscroll,onwheel,oncopy,oncut,onpaste,onabort,oncanplay,oncanplaythrough,oncuechange,ondurationchange,onemptied,onended,onerror,onloadeddata,onloadedmetadata,onloadstart,onpause,onplay,onplaying,onprogress,onratechange,onseeked,onseeking,onstalled,onsuspend,ontimeupdate,onvolumechange,onwaiting,onerror,onshow,ontoggle";
				break;

			default:
				$globalAttributes = "accesskey,class,dir,id,lang,style,tabindex,title";
				$eventAttributes = "onload,onunload,onblur,onchange,onfocus,onsearch,onselect,onsubmit,onkeydown,onkeypress,onkeyup,onclick,ondblclick,onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onmousewheel,oncopy,oncut,onpaste,onabort";
		endswitch;
		$glob = false;
		$event = false;
		//fullSupportList may contain html type declarations keys (html5,xhtml,html4) with values:
		//0 (no tag specific attributes), 
		//-1 (not supported in present html type),
		//-2 (not officially supported in present html type, although its usage is common in there (i.e. will raise validation error) )
		//and keys (global,event) which define whether it supports global or event html attributes.
		$fullSupportList = array();

		if($tag === 1):
			$tag = "globals";
		elseif($tag === 2):
			$tag = "global";
		elseif($tag === 3):
			$tag = "event";
		endif;

		switch($tag){

			case "global":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => false
				);
				break;

			case "event":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => true
				);
				break;

			case "globals":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "a":
				$fullSupportList = array(
					"html5" => "download,href,hreflang,media,rel,target,type",
					"xhtml" => "charset,coords,href,hreflang,name,rel,rev,shape,target,type",
					"html4" => "charset,coords,href,hreflang,name,rel,rev,shape,target,type",
					"global" => true,
					"event" => true
				);
				break;

			case "abbr":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "acronym":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "address":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "applet":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "code,object,align,alt,archive,codebase,height,hspace,name,vspace,width",
					"html4" => "code,object,align,alt,archive,codebase,height,hspace,name,vspace,width",
					"global" => false,
					"event" => false
				);
				break;

			case "area":
				$fullSupportList = array(
					"html5" => "alt,coords,download,href,hreflang,media,nohref,rel,shape,target,type",
					"xhtml" => "alt,coords,href,nohref,shape,target",
					"html4" => "alt,coords,href,nohref,shape,target",
					"global" => true,
					"event" => true
				);
				break;

			case "article":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "aside":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "audio":
				$fullSupportList = array(
					"html5" => "autoplay,controls,loop,muted,preload,src",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "b":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "base":
				$fullSupportList = array(
					"html5" => "href,target",
					"xhtml" => "href,target",
					"html4" => "href,target",
					"global" => true,
					"event" => false
				);
				break;

			case "basefont":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "color,face,size",
					"html4" => "color,face,size",
					"global" => false,
					"event" => false
				);
				break;

			case "bdi":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "bdo":
				$fullSupportList = array(
					"html5" => "dir",
					"xhtml" => "dir",
					"html4" => "dir",
					"global" => true,
					"event" => true
				);
				break;

			case "big":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "blockquote":
				$fullSupportList = array(
					"html5" => "cite",
					"xhtml" => "cite",
					"html4" => "cite",
					"global" => true,
					"event" => true
				);
				break;

			case "body":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "alink,background,bgcolor,link,text,vlink",
					"html4" => "alink,background,bgcolor,link,text,vlink",
					"global" => true,
					"event" => true
				);
				break;

			case "br":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "button":
				$fullSupportList = array(
					"html5" => "autofocus,disabled,form,formaction,formenctype,formmethod,formnovalidate,formtarget,name,type,value",
					"xhtml" => "disabled,name,type,value",
					"html4" => "disabled,name,type,value",
					"global" => true,
					"event" => true
				);
				break;

			case "canavas":
				$fullSupportList = array(
					"html5" => "height,width",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "caption":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align",
					"html4" => "align",
					"global" => true,
					"event" => true
				);
				break;

			case "center":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "cite":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "code":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "col":
				$fullSupportList = array(
					"html5" => "span",
					"xhtml" => "align,char,charoff,span,valign,width",
					"html4" => "align,char,charoff,span,valign,width",
					"global" => true,
					"event" => true
				);
				break;

			case "colgroup":
				$fullSupportList = array(
					"html5" => "span",
					"xhtml" => "",
					"html4" => "",
					"global" => true,
					"event" => true
				);
				break;

			case "datalist":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "dd":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "del":
				$fullSupportList = array(
					"html5" => "cite,datetime",
					"xhtml" => "cite,datetime",
					"html4" => "cite,datetime",
					"global" => true,
					"event" => true
				);
				break;

			case "details":
				$fullSupportList = array(
					"html5" => "open",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "dfn":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "dialog":
				$fullSupportList = array(
					"html5" => "open",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "dir":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "compact",
					"html4" => "compact",
					"global" => false,
					"event" => false
				);
				break;

			case "div":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align",
					"html4" => "align",
					"global" => true,
					"event" => true
				);
				break;

			case "dl":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "dt":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "em":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "embed":
				$fullSupportList = array(
					"html5" => "height,src,type,width",
					"xhtml" => -2,
					"html4" => -2,
					"global" => true,
					"event" => true
				);
				break;

			case "fieldset":
				$fullSupportList = array(
					"html5" => "disabled,form,name",
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "figcaption":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "figure":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "font":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "color,face,size",
					"html4" => "color,face,size",
					"global" => false,
					"event" => false
				);
				break;

			case "footer":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "form":
				$fullSupportList = array(
					"html5" => "accept-charset,action,autocomplete,enctype,method,name,novalidate,target",
					"xhtml" => "accept,accept-charset,action,enctype,method,target",
					"html4" => "accept,accept-charset,action,enctype,method,name,target",
					"global" => true,
					"event" => true
				);
				break;

			case "frame":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "frameborder,longdesc,marginheight,marginwidth,name,noresize,scrolling,src",
					"html4" => "frameborder,longdesc,marginheight,marginwidth,name,noresize,scrolling,src",
					"global" => false,
					"event" => false
				);
				break;

			case "frameset":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "cols,rows",
					"html4" => "cols,rows",
					"global" => false,
					"event" => false
				);
				break;

			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "h":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align",
					"html4" => "align",
					"global" => true,
					"event" => true
				);
				break;

			case "head":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "profile",
					"html4" => "profile",
					"global" => true,
					"event" => false
				);
				break;

			case "header":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "hr":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align,noshade,size,width",
					"html4" => "align,noshade,size,width",
					"global" => true,
					"event" => true
				);
				break;

			case "html":
				$fullSupportList = array(
					"html5" => "manifest,xmlns",
					"xhtml" => "xmlns,xml:lang",
					"html4" => "xmlns",
					"global" => true,
					"event" => false
				);
				break;

			case "i":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "iframe":
				$fullSupportList = array(
					"html5" => "height,name,sandbox,seamless,src,srcdoc,width",
					"xhtml" => "align,frameborder,height,longdesc,marginheight,marginwidth,name,scrolling,src,width",
					"html4" => "align,frameborder,height,longdesc,marginheight,marginwidth,name,scrolling,src,width",
					"global" => true,
					"event" => true
				);
				break;

			case "img":
				$fullSupportList = array(
					"html5" => "alt,crossorigin,height,ismap,src,usemap,width",
					"xhtml" => "align,alt,border,height,hspace,ismap,longdesc,src,usemap,vspace,width",
					"html4" => "align,alt,border,height,hspace,ismap,longdesc,src,usemap,vspace,width",
					"global" => true,
					"event" => true
				);
				break;

			case "input":
				$fullSupportList = array(
					"html5" => "accept,alt,autocomplete,autofocus,checked,disabled,form,formaction,formenctype,formmethod,formnovalidate,formtarget,height,list,max,maxlength,min,multiple,name,pattern,placeholder,readonly,required,size,src,step,type,value,width",
					"xhtml" => "accept,align,alt,checked,disabled,maxlength,name,readonly,size,src,type,value",
					"html4" => "accept,align,alt,checked,disabled,maxlength,name,readonly,size,src,type,value",
					"global" => true,
					"event" => true
				);
				break;

			case "ins":
				$fullSupportList = array(
					"html5" => "cite,datetime",
					"xhtml" => "cite,datetime",
					"html4" => "cite,datetime",
					"global" => true,
					"event" => true
				);
				break;

			case "kbd":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "keygen":
				$fullSupportList = array(
					"html5" => "autofocus,challenge,disabled,form,keytype,name",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "label":
				$fullSupportList = array(
					"html5" => "for,form",
					"xhtml" => "for",
					"html4" => "for",
					"global" => true,
					"event" => true
				);
				break;

			case "legend":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => "align",
					"html4" => "align",
					"global" => true,
					"event" => true
				);
				break;

			case "li":
				$fullSupportList = array(
					"html5" => "value",
					"xhtml" => "value,type",
					"html4" => "value,type",
					"global" => true,
					"event" => true
				);
				break;

			case "link":
				$fullSupportList = array(
					"html5" => "crossorigin,href,hreflang,media,rel,sizes,type",
					"xhtml" => "charset,href,hreflang,media,rel,rev,target,type",
					"html4" => "charset,href,hreflang,media,rel,rev,target,type",
					"global" =>true,
					"event" => true
				);
				break;

			case "main":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "map":
				$fullSupportList = array(
					"html5" => "name",
					"xhtml" => "name",
					"html4" => "name",
					"global" => true,
					"event" => true
				);
				break;

			case "mark":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "menu":
				$fullSupportList = array(
					"html5" => "label,type",
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "menuitem":
				$fullSupportList = array(
					"html5" => "checked,command,default,disabled,icon,label,radiogroup,type",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "meta":
				$fullSupportList = array(
					"html5" => "charset,content,http-equiv,name",
					"xhtml" => "content,http-equiv,name,scheme",
					"html4" => "content,http-equiv,name,scheme",
					"global" => true,
					"event" => false
				);
				break;

			case "meter":
				$fullSupportList = array(
					"html5" => "form,high,low,max,min,optimum,value",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "nav":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "noframes":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "noscript":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => false
				);
				break;

			case "object":
				$fullSupportList = array(
					"html5" => "data,form,height,name,type,usemap,width",
					"xhtml" => "align,archive,border,classid,codebase,codetype,data,declare,height,hspace,name,standby,type,usemap,vspace,width",
					"html4" => "align,archive,border,classid,codebase,codetype,data,declare,height,hspace,name,standby,type,usemap,vspace,width",
					"global" => true,
					"event" => true
				);
				break;

			case "ol":
				$fullSupportList = array(
					"html5" => "reversed,start,type",
					"xhtml" => "compact,start,type",
					"html4" => "compact,start,type",
					"global" => true,
					"event" => true
				);
				break;

			case "optgroup":
				$fullSupportList = array(
					"html5" => "disabled,label",
					"xhtml" => "disabled,label",
					"html4" => "disabled,label",
					"global" => true,
					"event" => true
				);
				break;

			case "option":
				$fullSupportList = array(
					"html5" => "disabled,label,selected,value",
					"xhtml" => "disabled,label,selected,value",
					"html4" => "disabled,label,selected,value",
					"global" => true,
					"event" => true
				);
				break;

			case "output":
				$fullSupportList = array(
					"html5" => "for,form,name",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "p":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align",
					"html4" => "align",
					"global" => true,
					"event" => true
				);
				break;

			case "param":
				$fullSupportList = array(
					"html5" => "name,value",
					"xhtml" => "name,type,value,valuetype",
					"html4" => "name,type,value,valuetype",
					"global" => true,
					"event" => true
				);
				break;

			case "pre":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "width",
					"html4" => "width",
					"global" => true,
					"event" => true
				);
				break;

			case "progress":
				$fullSupportList = array(
					"html5" => "max,value",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "q":
				$fullSupportList = array(
					"html5" => "cite",
					"xhtml" => "cite",
					"html4" => "cite",
					"global" => true,
					"event" => true
				);
				break;

			case "rp":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "rt":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "ruby":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "s":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "samp":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "script":
				$fullSupportList = array(
					"html5" => "async,charset,defer,src,type",
					"xhtml" => "charset,defer,src,type,xml:space",
					"html4" => "charset,defer,src,type,xml:space",
					"global" => true,
					"event" => false
				);
				break;

			case "section":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "select":
				$fullSupportList = array(
					"html5" => "autofocus,disabled,form,multiple,name,required,size",
					"xhtml" => "disabled,multiple,name,size",
					"html4" => "disabled,multiple,name,size",
					"global" => true,
					"event" => true
				);
				break;

			case "small":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "source":
				$fullSupportList = array(
					"html5" => "media,src,type",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "span":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "strike":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "strong":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "style":
				$fullSupportList = array(
					"html5" => "media,scoped,type",
					"xhtml" => "media,type",
					"html4" => "media,type",
					"global" => true,
					"event" => true
				);
				break;

			case "sub":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "summary":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "sup":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "table":
				$fullSupportList = array(
					"html5" => "sortable",
					"xhtml" => "align,bgcolor,border,cellpadding,cellspacing,frame,rules,sortable,summary,width",
					"html4" => "align,bgcolor,border,cellpadding,cellspacing,frame,rules,sortable,summary,width",
					"global" => true,
					"event" => true
				);
				break;

			case "tbody":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align,char,charoff,valign",
					"html4" => "align,char,charoff,valign",
					"global" => true,
					"event" => true
				);
				break;

			case "td":
				$fullSupportList = array(
					"html5" => "colspan,headers,rowspan",
					"xhtml" => "abbr,align,axis,bgcolor,char,charoff,colspan,headers,height,nowrap,rowspan,scope,valign,width",
					"html4" => "abbr,align,axis,bgcolor,char,charoff,colspan,headers,height,nowrap,rowspan,scope,valign,width",
					"global" => true,
					"event" => true
				);
				break;

			case "textarea":
				$fullSupportList = array(
					"html5" => "autofocus,cols,disabled,form,maxlength,name,placeholder,readonly,required,rows,wrap",
					"xhtml" => "cols,disabled,name,readonly,rows",
					"html4" => "cols,disabled,name,readonly,rows",
					"global" => true,
					"event" => true
				);
				break;

			case "tfoot":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align,char,charoff,valign",
					"html4" => "align,char,charoff,valign",
					"global" => true,
					"event" => true
				);
				break;

			case "th":
				$fullSupportList = array(
					"html5" => "abbr,colspan,headers,rowspan,scope,sorted",
					"xhtml" => "abbr,align,axis,bgcolor,char,charoff,colspan,headers,height,nowrap,rowspan,scope,sorted,valign,width",
					"html4" => "abbr,align,axis,bgcolor,char,charoff,colspan,headers,height,nowrap,rowspan,scope,sorted,valign,width",
					"global" => true,
					"event" => true
				);
				break;

			case "thead":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align,char,charoff,valign",
					"html4" => "align,char,charoff,valign",
					"global" => true,
					"event" => true
				);
				break;

			case "time":
				$fullSupportList = array(
					"html5" => "datetime",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "title":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => false
				);
				break;

			case "tr":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "align,bgcolor,char,charoff,valign",
					"html4" => "align,bgcolor,char,charoff,valign",
					"global" => true,
					"event" => true
				);
				break;

			case "track":
				$fullSupportList = array(
					"html5" => "default,kind,label,src,srclang",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "tt":
				$fullSupportList = array(
					"html5" => -1,
					"xhtml" => 0,
					"html4" => 0,
					"global" => false,
					"event" => false
				);
				break;

			case "u":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "ul":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => "compact,type",
					"html4" => "compact,type",
					"global" => true,
					"event" => true
				);
				break;

			case "var":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => 0,
					"html4" => 0,
					"global" => true,
					"event" => true
				);
				break;

			case "video":
				$fullSupportList = array(
					"html5" => "autoplay,controls,height,loop,muted,poster,preload,src,width",
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			case "wbr":
				$fullSupportList = array(
					"html5" => 0,
					"xhtml" => -1,
					"html4" => -1,
					"global" => true,
					"event" => true
				);
				break;

			default:
				$fullSupportList = array();

		}

		if(empty($fullSupportList)) return -1;

		if($this->is_xhtml()):
			$supportList = $fullSupportList["xhtml"];
		elseif($this->is_html5()):
			$supportList = $fullSupportList["html5"];
		else:
			$supportList = $fullSupportList["html4"];
		endif;

		if($supportList == -1):
			return false;
		elseif($supportList == -2):
			return -1;
		elseif($supportList === 0):
			$supportList = '';
		endif;

		if(!empty($fullSupportList["global"]))$glob = $globalAttributes;
		if(!empty($fullSupportList["event"]))$event = $eventAttributes;

		$endArray = explode("|",$glob ."|". $event ."|".$supportList);
		$endString = "";
		foreach($endArray as $end):
			if(!empty($end) && !empty($endString)):
				$endString .= "," . $end;
			elseif(!empty($end) && empty($endString)):
				$endString .= $end;
			endif;
		endforeach;

		$result = explode(',',$endString);

		return $result;



		//function end
	}
	//class	end
}
	
class MHTD_htmlPreset extends MHTD_createHtml{
	
	public $htmlAttributes = "tag=html";
	public $title = "";
	public $favicon = "";
	public $javaScript = "";
	public $jsFile = "";
	public $css = "";
	public $cssFile = "";
	private $supported = array("favicon","js","jsfile","css","cssfile");
	
	
	function __construct(){
		
		$arguments = func_get_args(); 
		if(!empty($arguments[0])) $this->htmlAttributes = $arguments[0];
		
		if(is_array($this->htmlAttributes)):
			$keys = "";
			$vals = "";
			foreach($this->htmlAttributes as $key=>$val):
				$keys .= "|".$key;
				$vals .= "|".$val;
			endforeach;
			$this->htmlAttributes = "tag".$keys."=html".$vals;
		endif;
		if(!empty($arguments[1]["title"])): $this->title = $arguments[1]["title"]; endif;
		if(!empty($arguments[1]["favicon"])): $this->favicon = $arguments[1]["favicon"]; endif;
		if(!empty($arguments[1]["js"])): $this->javaScript = $arguments[1]["js"]; endif;
		if(!empty($arguments[1]["css"])): $this->css = $arguments[1]["css"]; endif;
		if(!empty($arguments[1]["cssfile"])): $this->cssFile = $arguments[1]["cssfile"]; endif;
		if(!empty($arguments[1]["jsfile"])): $this->jsFile = $arguments[1]["jsfile"]; endif;
		$this->supportedFunctions($arguments[1]);
		
	}
	
	private function supportedFunctions($input){
		if(!function_exists("runkit7_method_add")) return false;
		foreach($this->supported as $key=>$val):
			runkit7_method_add("htmlPreset",$val,"$input[$val]","$this->newScript($input,".$val.");");
		endforeach;
		return true;
	}
	
	public function newScript($input,$type){
		
		$conv = array(
		"js"=>"javaScript",
		"jsfile"=>"jsFile",
		"cssfile"=>"cssFile",
		"jsf"=>"jsFile",
		"cssf"=>"cssFile",
		"fav"=>"favicon"
		);
		if(in_array($type,array_keys($conv))): $string = $conv[$type]; else: $string = $type; endif;
		
		if(!isset($this->$string)) return false;
		
		if(!is_array($this->$string)): $this->$string = array($input); else: $tmp = $this->$string; $tmp[] = $input; $this->$string = $tmp; unset($tmp); endif;
		
		return true;
		
	}
	
	
	
	
	public function htmlBasePreset(){
		
		$htm = new MHTD_createHtml;
		$jsFile = $this->jsFile;
		$cssFile = $this->cssFile;
		$css = $this->css;
		$javaScript = $this->javaScript;
		$favicon = $this->favicon;
		
		if(!empty($jsFile) || !is_array($jsFile)): $jsFile = array($jsFile);  endif;
		if(!empty($cssFile) || !is_array($cssFile)): $cssFile = array($cssFile);  endif;
		if(!empty($css) || !is_array($css)): $css = array($css);  endif;
		if(!empty($javaScript) || !is_array($javaScript)): $javaScript = array($javaScript);  endif;
		if(!empty($favicon) || !is_array($favicon)): $favicon = array($this->favicon); endif;
		$includes = array($cssFile,$favicon,$jsFile,$css,$javaScript);
		$text = "";

		
		$htm->newElement("doctype".$this->htmlStats["doctype"],0);
		$htm->newChild($this->htmlAttributes,1);
		$htm->newSubchild("head");
		$htm->newSubElement("charset".$this->htmlStats["charset"],1);
		$htm->newSubchild("title");
		$htm->newSubelement($this->title);
		
		foreach($includes as $key=>$val):
			foreach($val as $k=>$v):
				if(empty($v)) break;
				if(!is_array($v))$v = array($v);
				foreach($v as $strKey=>$strVal):
					switch($key):
						case 0:
							$text .= $this->attrToTag("tag|rel|type|href=link|stylesheet|text/css|".$strVal,0) ."\n\r";
						break;
						case "1":
							$text .= $this->attrToTag("tag|rel|href=link|shortcut icon|".$strVal,0) . "\n\r";
						break;
						case "2":
							$text .= sprintf($this->attrToTag("tag|src=script|".$strVal,1),"") ."\n\r";
						break;
						
						case "3":
						case "4":
							$t = $this->attrToTag("tag=script",1) ."\n\r";
							$text .= sprintf($t,$strVal);
							unset($t);

						break;

					endswitch;
				endforeach;
			endforeach;		
		endforeach;
		
		$htm->newElement($text,3);
		$htm->closeActiveChild();
		return $htm->structure;
		
	}
	
	function __destruct(){
		
		$htm = new MHTD_createHtml;
		$htm->structure = $this->htmlBasePreset();
		$htm->htmlConstruct($this->structure);
		echo $htm->parseStructure();
		
	}
	
	
	
}

class MHTD_table{

	public $values = array();
	public $columns = 4;
	public $header = array();
	public $attributes = array("table"=>"","thead"=>"","tbody"=>"","tfoot"=>"","row"=>"","cell"=>"");
	public $classPrefix = "myprefix_";
	public $outputCss = true;
	//example attributes array("table"=>"class=class,style=crigobass")
	public $footer = 0;
	private $html = "";

	function __construct(){

		$this->html = new MHTD_createHtml;


	}

	public function doctype($doctype){

		//doctype might be: transit (HTML 4 transitional), strict (HTML 4 strict), xtransit (XHTML transitional), xstrict (XHTML strict), default - none ("" - HTML 5)
		$this->html->htmlStats["doctype"] = $doctype;
		return true;

	}

	private function HFState($header,$footer = ""){

		if(!empty($header) && $header != $footer):
			if($this->heading == true && empty($footer))$footer = $header;
		endif;

		return array("header"=>$header,"footer"=>$footer);

	}

	private function prepare($outputTypeHtml = true){

		$heading = true;
		if(empty($this->header))$heading = false;
		!$heading or $this->heading = true;
		$table = $thead = $tfoot = $tbody = $tr = $td = array();
		if(isset($this->attributes["table"])):
			$table = MHTD_basicFunctions::attrStringToArray($this->attributes["table"]);
			if($outputTypeHtml == true):
				$table["tag"] = "table";
			else:
				$table["tag"] = "div";
			endif;
			isset($table["class"]) or $table["class"] = "";
			$table["class"] .=" ".$this->classPrefix."table";
			$this->table = $table;
		endif;
		if($heading && isset($this->attributes["thead"])):
			$thead = MHTD_basicFunctions::attrStringToArray($this->attributes["thead"]);
			if($outputTypeHtml == true):
				$thead["tag"] = "thead";
			else:
				$thead["tag"] = "div";
			endif;
			isset($thead["class"]) or $thead["class"] = "";
			$thead["class"] .= " ".$this->classPrefix."thead";
			$this->thead = $thead;
		endif;
		if($heading && isset($this->attributes["tfoot"])):
			$tfoot = MHTD_basicFunctions::attrStringToArray($this->attributes["tfoot"]);
			if($outputTypeHtml == true):
				$tfoot["tag"] = "tfoot";
			else:
				$tfoot["tag"] = "div";
			endif;
			isset($tfoot["class"]) or $tfoot["class"] = "";
			$tfoot["class"] .= " ".$this->classPrefix."tfoot";
			$this->tfoot = $tfoot;
		endif;
		if($heading && isset($this->attributes["tbody"])):
			$tbody = MHTD_basicFunctions::attrStringToArray($this->attributes["tbody"]);
			if($outputTypeHtml == true):
				$tbody["tag"] = "tbody";
			else:
				$tbody["tag"] = "div";
			endif;
			isset($tbody["class"]) or $tbody["class"] = "";
			$tbody["class"] .= " ".$this->classPrefix."tbody";
			$this->tbody = $tbody;
		endif;
		if(isset($this->attributes["row"])):
			$tr = MHTD_basicFunctions::attrStringToArray($this->attributes["row"]);
			if($outputTypeHtml == true):
				$tr["tag"] = "tr";
			else:
				$tr["tag"] = "div";
			endif;
			isset($tr["class"]) or $tr["class"] = "";
			$tr["class"] .= " ".$this->classPrefix."row";
			$this->row = $tr;
		endif;
		if(isset($this->attributes["cell"])):
			$td = MHTD_basicFunctions::attrStringToArray($this->attributes["cell"]);
			if($outputTypeHtml == true):
				$td["tag"] = "td";
			else:
				$td["tag"] = "div";
			endif;
			isset($td["class"]) or $td["class"] = "";
			$td["class"] .= " ".$this->classPrefix."cell";
			$this->cell = $td;
		endif;

	}

	public function cssDefault(){

		$css = "
.".$this->classPrefix."table{
	display:table;
	}
.".$this->classPrefix."row{
	display:table-row;
	}
.".$this->classPrefix."cell{
	display:table-cell;
	}
.".$this->classPrefix."thead{
	display:table-header-group;
	}
.".$this->classPrefix."tfoot{
	display:table-footer-group;
	}
.".$this->classPrefix."tbody{
	display:table-row-group;
	}
.".$this->classPrefix."th {
    display: table-cell;
    vertical-align: inherit;
    font-weight: bold;
    text-align: center;
	} ";

		return $css;

	}

	private function innerOutput($values,$columns,$header = false){

		$cell = $this->cell;
		if($header == true && empty($this->outputCss)):
			$cell["tag"] = "th";
		elseif($header == true && !empty($this->outputCss)):
			$cell["class"] .= " ".$this->classPrefix . "th";
		endif;
		$html = $this->html;
		$html->newSubChild($this->row);
		$counter = 0;
		foreach($values as $k=>$v):
			if($counter == $columns):
				$html->newChild($this->row);
				$counter = 0;
			endif;
			$html->newSubChild($cell);
			$html->newSubElement($v,3);
			$html->closeActiveChild();
			$counter++;
		endforeach;
		$html->closeActiveChild();

	}


	public function output(){

		if(!is_array($this->values))return false;
		switch($this->outputCss):
			case true:
				$this->prepare(false);
				break;
			case false:
				$this->prepare();
		endswitch;
		$html = $this->html;
		$html->newChild($this->table);
		if(isset($this->heading)):
			$HF = $this->HFState($this->header);
			if(isset($this->thead)):
				$html->newSubChild($this->thead);
				$this->innerOutput($HF["header"],count($HF["header"]),true);
				$html->closeActiveChild();
			endif;

			if(isset($this->tfoot)):
				$html->newSubChild($this->tfoot);
				$this->innerOutput($HF["footer"],count($HF["footer"]),true);
				$html->closeActiveChild();
			endif;

			if(isset($this->tbody)):
				$html->newSubChild($this->tbody);
				$this->innerOutput($this->values,$this->columns);
				$html->closeActiveChild();
			endif;

		else:

			$this->innerOutput($this->values,$this->columns);

		endif;

		return $html->parseStructure();
	}

	public static function output_table($values,$columns = false,$mode=1,$headAndFoot = array("header"=>array(),"footer"=>array()),$attributes = array(),$doctype = "",$classPrefix = ""){

		$table = new MHTD_table;
		if(!is_array($values))$values = explode(",",$values);
		if(empty($columns) && (!empty($headAndFoot["header"])  || !empty($headAndFoot["footer"]))):
			if(count($headAndFoot["header"]) != count($headAndFoot["footer"])):
				if(count($headAndFoot["header"]) > count($headAndFoot["footer"])):
					$columns = count($headAndFoot["header"]);
				else:
					$columns = count($headAndFoot["footer"]);
				endif;
			else:
				$columns = count($headAndFoot["header"]);
			endif;
		endif;
		$table->columns = $columns;
		$table->values = $values;
		$table->outputCss = $mode;
		if(!empty($attributes)):
			$keysThisAttrs = array_keys($table->attributes);
			foreach($attributes as $k=>$v):
				if(in_array($k,$keysThisAttrs)):
					$table->attributes[$k] = $v;
				endif;
			endforeach;
		endif;
		if(!empty($headAndFoot["header"]))$table->header = $headAndFoot["header"];
		if(!empty($headAndFoot["footer"]))$table->footer = $headAndFoot["header"];
		$table->doctype($doctype);
		if(!empty($classPrefix))$table->classPrefix = $classPrefix;
		return $table->output();

	}

}
	

?>
