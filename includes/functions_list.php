<?php

/*********** 
  string hashPassword (string $pPassword, string $pSalt1, string $pSalt2) 
    This will create a SHA1 hash of the password 
    using 2 salts that the user specifies. 
************/ 
function get_ip(){
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}	
return $ip;	
}
 
 
function hashPassword($pPassword) { 
  return hash('sha256', "24134&$%@e".$pPassword."dsas^#");
} 


function add_slashes($string){
    if(get_magic_quotes_gpc() == 1){
        return $string;
    } else {
        return addslashes(($string));
    }
}

function redirect_to($new_url){
    header("Location: " . $new_url);
    exit;

}


function shuffle_assoc($list) {
    if (!is_array($list)) return $list;

    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key) {
        $random[$key] = $list[$key];
    }
    return $random;
}

function validateSubmit($pId, $pKey) { 
   global $dbc;
   $sql = "SELECT question_id  FROM nctf_questions   WHERE question_id='" . mysqli_real_escape_string($dbc,$pId) . "'  AND answer='" . mysqli_real_escape_string($dbc,$pKey) . "'   LIMIT 1;"; 
   $query = mysqli_query($dbc,$sql) or trigger_error("Query Failed: " . mysql_error()); 
  
    // If one row was returned, the user was logged in! 
    if (mysqli_num_rows($query) == 1) {   
    //$sql = "insert into nctf_rank (user_id,question_id,time) values(".$_SESSION['user_id'].",".$pId.",now());"; 
	//防止重复插入
	$sql = "
	INSERT INTO nctf_rank (user_id, question_id) SELECT
	*
FROM
	(
		SELECT
			".$_SESSION['user_id'].",
			".$pId."
	) AS tmp
WHERE
	NOT EXISTS (
		SELECT
			submit_id
		FROM
			nctf_rank
		WHERE
			user_id = ".$_SESSION['user_id']."
		AND question_id = ".$pId."
	);

UPDATE nctf_accounts
SET time = now()
WHERE
	user_id = ".$_SESSION['user_id'].";
	UPDATE nctf_rank a
		JOIN nctf_questions b ON a.question_id = b.question_id
		SET a.mark = b.mark;
 	UPDATE nctf_accounts p,
		(
		SELECT
			user_id,
			sum(mark) AS mysum
		FROM
			nctf_rank
		GROUP BY
			user_id
		) AS s
		SET p.score = s.mysum
		WHERE
			p.user_id = s.user_id "; 
	//echo $sql;
    $query = $dbc->multi_query($sql) or trigger_error("Query Failed: " . mysql_error()); 
    return true; 
	
  } 
  
  return false; 
} 


 function createAccount($pUsername, $pPassword, $pMail) { 
 global $dbc;
  // First check we have data passed in. 
  if (!empty($pUsername) && !empty($pPassword) && !empty($pPassword) && !empty($pMail)) { 
    $uLen = strlen($pUsername); 
    $pLen = strlen($pPassword); 
     
    // escape the $pUsername to avoid SQL Injections 
    $eUsername = mysqli_real_escape_string($dbc,$pUsername); 
    $sql = "SELECT username FROM nctf_accounts WHERE username = '" . $eUsername . "' LIMIT 1"; 
 
    // Note the use of trigger_error instead of or die. 
    $query = mysqli_query($dbc,$sql) or trigger_error("Query Failed: " . mysql_error()); 
    
	$ip = get_ip();
    // Error checks (Should be explained with the error) 
    if ($uLen <= 4 || $uLen >= 16) { 
      $_SESSION['error'] = "Username must be between 5 and 11 characters."; 
    }elseif ($pLen < 6) { 
      $_SESSION['error'] = "Password must be longer then 6 characters."; 
    }elseif (!filter_var($pMail, FILTER_VALIDATE_EMAIL)){
      $_SESSION['error'] = "Invaild Email address."; 
    }elseif (mysqli_num_rows($query) == 1) { 
      $_SESSION['error'] = "Username already exists."; 
    }else { 
	  $sql = "INSERT INTO nctf_accounts (`username`, `password`, `mail`,`register_time`,`register_ip`) VALUES ('" . $eUsername . "', '" . hashPassword($pPassword). "','" . $pMail . "',now(),'" . $ip . "');";  
	  //echo $sql;
      $query = mysqli_query($dbc,$sql) or trigger_error("Query Failed: " . mysql_error()); 
      if ($query) { 
        return true; 
      }   
    } 
  } 
   
  return false; 
} 
 

/*********** 
  bool loggedIn 
    verifies that session data is in tack 
    and the user is valid for this session. 
************/ 
function loggedIn() { 
  // check both loggedin and username to verify user. 
  if (isset($_SESSION['loggedin'])){
  if ($_SESSION['loggedin'] && isset($_SESSION['username'])) { 
    return true; 
  } 
  }
   
  return false; 
} 
 
/*********** 
  bool logoutUser  
    Log out a user by unsetting the session variable. 
************/ 
function logoutUser() { 
  // using unset will remove the variable 
  // and thus logging off the user. 
  unset($_SESSION['username']); 
  unset($_SESSION['loggedin']); 
  unset($_SESSION['user_id']);
   
  return true; 
} 
 
/*********** 
  bool validateUser 
    Attempt to verify that a email / password 
    combination are valid. If they are it will set 
    cookies and session data then return true.  
    If they are not valid it simply returns false.  
************/ 
function validateUser($pEmail, $pPassword) { 
   global $dbc;
  // See if the email and password are valid. 
  //$sql = "SELECT username FROM nctf_accounts  WHERE mail = '" . mysqli_real_escape_string($dbc,$pEmail) . "' AND password = '" . hashPassword($pPassword, SALT1, SALT2) . "' LIMIT 1"; 
  $sql = "SELECT username,user_id FROM nctf_accounts  WHERE mail = '" . mysqli_real_escape_string($dbc,$pEmail) . "' AND password = '" . hashPassword($pPassword) . "' LIMIT 1"; 
  $query = mysqli_query($dbc,$sql) or trigger_error("Query Failed: " . mysql_error()); 
  
  // If one row was returned, the user was logged in! 
  if (mysqli_num_rows($query) == 1) { 
    $row = mysqli_fetch_assoc($query); 
    $_SESSION['username'] = $row['username']; 
	$_SESSION['user_id'] = $row['user_id']; 
    $_SESSION['loggedin'] = true;       
    return true; 
  } 
  
  return false; 
} 


function validateUser_Name($pName, $pPassword) { 
   global $dbc;
  // See if the email and password are valid. 
  //$sql = "SELECT username FROM nctf_accounts  WHERE mail = '" . mysqli_real_escape_string($dbc,$pEmail) . "' AND password = '" . hashPassword($pPassword, SALT1, SALT2) . "' LIMIT 1"; 
  $sql = "SELECT username,user_id FROM nctf_accounts  WHERE username = '" . mysqli_real_escape_string($dbc,$pName) . "' AND password = '" . hashPassword($pPassword) . "' LIMIT 1"; 
  $query = mysqli_query($dbc,$sql) or trigger_error("Query Failed: " . mysql_error()); 
  
  // If one row was returned, the user was logged in! 
  if (mysqli_num_rows($query) == 1) { 
    $row = mysqli_fetch_assoc($query); 
    $_SESSION['username'] = $row['username']; 
	$_SESSION['user_id'] = $row['user_id']; 
    $_SESSION['loggedin'] = true;       
    return true; 
  } 
  
  return false; 
} 


function cast_query_results($rs) {
    $fields = mysqli_fetch_fields($rs);
    $data = array();
    $types = array();
    foreach($fields as $field) {
        switch($field->type) {
            case 3:
                $types[$field->name] = 'int';
                break;
            case 4:
                $types[$field->name] = 'float';
                break;
            default:
                $types[$field->name] = 'string';
                break;
        }
    }
    while($row=mysqli_fetch_assoc($rs)) array_push($data,$row);
    for($i=0;$i<count($data);$i++) {
        foreach($types as $name => $type) {
            settype($data[$i][$name], $type);
        }
    }
    return $data;
}

function userProfile($pUsername){
    //display user profile
	global $dbc;
	$query = "select user_id,username,tel,mail,xuehao,comment from nctf_accounts where username= '" .$pUsername. "';";

	//Run the query
	$query_result = $dbc->query($query);

	$userArray = array();
	while ($row = $query_result->fetch_assoc()){
		$userArray[] = $row;
	}
	//if (isset($userArray)){ exit;}
	$userArray=$userArray[0];
	//var_dump($userArray );

	$oostring = '
	      <tr>
			 <td>User Name</td>
			 <td>'.$userArray['username'].'</td>
		  </tr>
		  <tr>
			 <td>Emai</td>
			 <td>'.$userArray['mail'].'</td>
		  </tr>
		  <tr>
			 <td>Phone Number</td>
			 <td>'.$userArray['tel'].'</td>
		  </tr>
		  <tr>
			 <td>Comment</td>
			 <td>'.$userArray['comment'].'</td>
		  </tr>
		  ';
	   
	echo $oostring ;
	}

function get_questionamout() {
	global $dbc;
	$query1 = 'select count(*) as num from nctf_questions';
    $num = mysqli_fetch_assoc($dbc->query($query1));
    return  $num['num'];
}


function get_answerarray($user_id){
    global $dbc;
    $totalquestion = get_questionamout();	
    $query = "select question_id from nctf_rank where user_id='".$user_id."'";	
        $query_result = $dbc->query($query);
		$answerArray = cast_query_results($query_result);		
		$answerArrayfuck=array();
		foreach ($answerArray as $key=>$value){
			foreach ($value as $kkk=>$vvv){
			$answerArrayfuck[]=$vvv;
			}
		}
    return $answerArrayfuck;
}


function show_spanarray1($user_id){
    global $dbc;
	$oostring = '';
    $totalquestion = get_questionamout();	
    $answer_array= array();
    $answer_array = get_answerarray($user_id);
        //var_dump($answerArrayfuck);
		foreach ( range(0,$totalquestion) as $qid ) {
		$qid = $qid+1;
        if(in_array($qid,$answer_array)){			    
                $oostring .= '<span class="label label-success label-as-badge">'.$qid.'</span>';
                }else{
                $oostring .= '<span class="label label-default label-as-badge">'.$qid.'</span>';
        }
		
		
	    }
	return $oostring ;
}

