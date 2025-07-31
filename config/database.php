<?php
/* 
session_start();
if(!checkUserSession($_SESSION["user"]))
{
        echo "<script>window.top.location.href='login.php'</script>";
        //header("Location:login.php");
}
*/
function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        return $protocol . $host . $uri;
}

function write_log($message, $logfile = 'app.log') {
        $logPath = __DIR__ . '/' . $logfile;
        $timeStamp = date('Y-m-d H:i:s');
        $logMessage = "[$timeStamp] $message" . PHP_EOL;
        file_put_contents($logPath, $logMessage, FILE_APPEND);
}
function js_redirect($url, $seconds=1) 
            {  
                echo "<script language=\"JavaScript\">\n";  
                echo "<!-- hide from old browser\n\n";       
                echo "function redirect() {\n";  
                echo "window.location = \"" . $url . "\";\n";  
                echo "}\n\n";  
                echo "timer = setTimeout('redirect()', '" . ($seconds*1000) . "');\n\n";  
                echo "-->\n";  
                echo "</script>\n";  
                return true;  
            }

// Load environment variables from .env using vlucas/phpdotenv
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

function getOrangeConnection()
{
    $con = mysqli_connect(
        $_ENV['DB_HOST'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASSWORD'],
        $_ENV['DB_ORANGEHRM'],
        $_ENV['DB_PORT']
    );
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    return $con;
}

function getOrangePDOConnection()
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_ORANGEHRM']
    );
    $con = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    return $con;
}

function getConnection()
{
    try {
        $con = mysqli_connect(
            $_ENV['DB_HOST'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            $_ENV['DB_NAME'],
            $_ENV['DB_PORT']
        );
        
        if (mysqli_connect_errno()) {
            $error = "Failed to connect to MySQL: " . mysqli_connect_error();
            write_log("Database Connection Error: " . $error, 'app.log');
            logError("Database connection failed", 'DB_ERROR', [
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'port' => $_ENV['DB_PORT'],
                'error' => mysqli_connect_error()
            ]);
            return false;
        }
        
        logInfo("Database connection successful", [
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME']
        ]);
        
        return $con;
    } catch (Exception $e) {
        logError("Database connection exception: " . $e->getMessage(), 'DB_EXCEPTION', [
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME']
        ]);
        return false;
    }
}

function getPDOConnection()
{
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );
        
        $con = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        logInfo("PDO Database connection successful", [
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME']
        ]);
        
        return $con;
    } catch (PDOException $e) {
        logError("PDO Database connection failed: " . $e->getMessage(), 'DB_PDO_ERROR', [
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_NAME'],
            'error_code' => $e->getCode()
        ]);
        return false;
    }
}

function debug($str)
{
    if ($_ENV['DEBUGGER'] == "yes") {
        echo "<h2>There was a technical error. </h2>";
        die($str);
    } else {
        die("<h2>There was a technical error. <br><br>Please contact the Administrator.</h2>");
    }
}

function closeConnection($con)
{
	mysqli_close($con);
}

function checkPermission($check)
{
	$con=getConnection();
	$sql="
	 select view_perm,create_perm,
        edit_perm,pay_perm,auth_perm,del_perm
        from expense_perm_matrix
        left join  hr_mysql_live.ohrm_user on emp_id = emp_number
        where emp_id = 0 or user_name ='".$_SESSION['user']."'" ;
	
	
	$result = mysqli_query($con,$sql) or debug($sql."   failed  <br/><br/>".mysqli_error($con));

	while($row = mysqli_fetch_array($result))
  	{
		if($check == "CREATE" and $row['create_perm'] == 1 )
			return true;
		
		if($check == "EDIT" and $row['edit_perm'] == 1 )
			return true;
		
		if($check == "AUTH" and $row['auth_perm'] == 1 )
			return true;
		
		if($check == "DELETE" and $row['del_perm'] == 1 )
			return true;
		
		if($check == "PAY" and $row['pay_perm'] == 1 )
			return true;
		
		if($check == "VIEW" and $row['view_perm'] == 1 )
			return true;
  	}
  	closeConnection($con);
	return false;
}

function checkGenericPermission($role)
{
//Generic permission check
	//echo basename($_SERVER["SCRIPT_FILENAME"]);
	$con=getConnection();
	$sql = "select * from crux_auth where asset = '".basename($_SERVER["SCRIPT_FILENAME"])."'";
	$result = mysqli_query($con,$sql) or debug($sql."   failed  <br/><br/>".mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
		if($row['role'] == $role)
		return true;
		
		if($row['role'] == "ALL")
		return true;
	
	}
	closeConnection($con);
	return false;
}
function checkProjectPermission($check)
{
        $con=getOrangeConnection();
        $sql="select custom4 from ohrm_user a
		left join  hs_hr_employee b on b.emp_number = a.emp_number
		where emp_status not in (4,6)
		and deleted = 0 and user_name = '".$_SESSION['user']."'";

        $result = mysqli_query($con,$sql) or debug($sql."   failed  <br/><br/>".mysqli_error($con));
		$row = mysqli_fetch_array($result);
		$emp=$row["custom4"];
		
		//If the generic permission says fine return true otherwise use legacy authorizer
		//over a period of time we must delete legacy authroizer code and just return generic authorizer
		if(checkGenericPermission($emp) == true)
			return true;
			
			
		if($emp == "All")
			return true;
                if($check == "INITIATE")
		{	
			if($emp == "Manager" )
                        	return true;
			else
				return false;
		}
		if($check == "MASTEREDIT")
                {
                        if($emp == "Authoriser" )
                                return true;
                        else
                                return false;
                }
		if($check == "INVEDIT")
                {
                        if($emp == "Reviewer" or $emp == "Manager" or $emp == "Authoriser")
                                return true;
                        else
                                return false;
                }


		if($check == "AUTHORISE")
                {
                        if($emp == "Authoriser" )
                                return true;
                        else
                                return false;
                }
		if($check == "APPROVE")
                {
                        if($emp == "Reviewer" )
                                return true;
                        else
                                return false;
                }
		if($check == "UPDATE")
                {
                       if($emp == "Reviewer" or $emp == "Manager" or $emp == "Authoriser")
                                return true;
                        else
                                return false;
                }

 		if($check == "INITCLOSE")
                {
                        if($emp == "Manager" )
                                return true;
                        else
                                return false;
                }
                if($check == "AUTHCLOSE")
                {
                        if($emp == "Authoriser" )
                                return true;
                        else
                                return false;
                }
                if($check == "CLOSE")
                {
                        if($emp == "Reviewer" )
                                return true;
                        else
                                return false;
                }

	        if($check == "DELETE")
                {
                        if($emp == "Authoriser" )
                                return true;
                        else
                                return false;
                }
                if($check == "DELIVER")
                {
                        if($emp == "Manager" or $emp == "Authoriser")
                                return true;
                        else
                                return false;
                }
                if($check == "EDIT")
                {
                        if($emp == "Manager" or $emp == "Authoriser")
                                return true;
                        else
                                return false;
                }
                if($check == "WARRANTY")
                {
                        if($emp == "Manager" or $emp == "Authoriser")
                                return true;
                        else
                                return false;
                }
                if($check == "VIEW")
                {
                        if($emp == "Manager" or $emp == "Authoriser" or $emp == "Reviewer")
                                return true;
                        else
                                return false;
                }
		if($check == "ACTIVATE")
                {
                        if($emp == "Authoriser")
                                return true;
                        else
                                return false;
                }

        closeConnection($con);

        return false;
}

function checkUserSession($loggedinuser)
{

	return !(!isset($loggedinuser) || trim($loggedinuser)=="");
}
/*
function checkUserSession($loggedinuser)
{

	$ret = !(!isset($loggedinuser) || trim($loggedinuser)=="");
	if($ret == false)
	{
		$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') 
            === FALSE ? 'http' : 'https';
		$host = $_SERVER['HTTP_HOST'];
		$script = $_SERVER['SCRIPT_NAME'];
		$params = $_SERVER['QUERY_STRING']; 
		$currentUrl = $protocol . '://' . $host . $script . '?' . $params;
		//header("Location:login.php?redir=".$currentUrl);
		header("Location:login.php");
		exit;
	}
	return $ret;
}*/
function getUser()
{
	$headers = apache_request_headers();
 
if (!isset($headers['Authorization'])){
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: NTLM');
    exit;
}
 
$auth = $headers['Authorization'];
 
if (substr($auth,0,5) == 'NTLM ') {
    $msg = base64_decode(substr($auth, 5));
    if (substr($msg, 0, 8) != "NTLMSSP\x00")
        die('error header not recognised');
 
    if ($msg[8] == "\x01") {
        $msg2 = "NTLMSSP\x00\x02\x00\x00\x00".
            "\x00\x00\x00\x00". // target name len/alloc
            "\x00\x00\x00\x00". // target name offset
            "\x01\x02\x81\x00". // flags
            "\x00\x00\x00\x00\x00\x00\x00\x00". // challenge
            "\x00\x00\x00\x00\x00\x00\x00\x00". // context
            "\x00\x00\x00\x00\x00\x00\x00\x00"; // target info len/alloc/offset
 
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: NTLM '.trim(base64_encode($msg2)));
        exit;
    }
    else if ($msg[8] == "\x03") {
        function get_msg_str($msg, $start, $unicode = true) {
            $len = (ord($msg[$start+1]) * 256) + ord($msg[$start]);
            $off = (ord($msg[$start+5]) * 256) + ord($msg[$start+4]);
            if ($unicode)
                return str_replace("\0", '', substr($msg, $off, $len));
            else
                return substr($msg, $off, $len);
        }
        $user = get_msg_str($msg, 36);
        $domain = get_msg_str($msg, 28);
        $workstation = get_msg_str($msg, 44);
 
        return "You are $user from $domain/$workstation";
    }
}
}

?>
