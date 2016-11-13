<?php

$_DELETE = array ();
$_PUT = array ();
$_method = array_key_exists('_m',$_REQUEST) ? $_REQUEST['_m'] : $_SERVER['REQUEST_METHOD'];

switch ( $_method ) {
    case !strcasecmp($_method,'DELETE'):
        parse_str( file_get_contents( 'php://input' ), $_DELETE );
        $_REQUEST = array_merge($_REQUEST,$_DELETE);
        break;

    case !strcasecmp($_method,'PUT'):
        parse_str( file_get_contents( 'php://input' ), $_PUT );
        $_REQUEST = array_merge($_REQUEST,$_PUT);
        break;
}

if(!IsDevelopment() && !($_SERVER['SERVER_NAME'] === 'share.numberstation.com'))
{
  require 'Security.inc.php';
}
else if($_SERVER['SERVER_NAME'] === 'share.numberstation.com')
{
  if($_SERVER['SCRIPT_NAME']!=='/v1/index.php')
  {
    NotFound();
  }
}

require_once 'UUID.inc.php';
use Aws\S3\Model\ClearBucket;

function IsDevelopment()
{
    return true;
  return $_SERVER['SERVER_NAME'] === 'local.numberstation.com' || $_SERVER['SERVER_NAME'] === 'nstest_ne.numberstation.com';
}

function IsProduction()
{
  $parts = explode('.',$_SERVER['SERVER_NAME']);

  $pos = strpos($parts[0], 'prod');
  if ($pos === false)
  {
    return false;
  }
  return true;
}

function Forbidden()
{
  header('HTTP/1.0 403 Forbidden');
  echo "<h1>403 Forbidden</h1>";
  echo "You do not have permission to access this resource.";

  exit;
}

function NotFound()
{
  date_default_timezone_set('America/Los_Angeles');
  header('HTTP/1.0 404 Not Found');
  echo "<h1>404 Not Found</h1>";
  echo "The page that you have requested could not be found. " . date('Y-m-d H:i:s') . ' ' .time();
  exit;
}

function StationNotFound()
{
  header('HTTP/1.0 404 Not Found');
  echo "<h1>Station Not Found</h1>";
  echo "The page that you have requested could not be found.";
  exit;
}

function Fatal($reason, $contentType = 'application/json')
{
  if(IsDevelopment() || true) // dump environment
  {
    $json['details'] = array('SERVER' => $_SERVER, 'POST' => $_POST, 'GET' => $_GET, 'FILES' => $_FILES, 'COOKIE', $_COOKIE, 'REQUEST' => $_REQUEST);
  }

  if(is_string($reason))
  {
    $json['errormessage'] = $reason;
    ReturnJSON($json,$contentType); 
  }
  if(is_object($reason) && is_a($reason,'Exception'))
  {
    $code = $reason->getCode();
    header("HTTP/1.0 {$code} ");
    $json['errormessage'] = $reason->getMessage();
    $json['code'] = $code;
    $json['trace'] = explode("\n",str_replace('\\','|',$reason->getTraceAsString())); 
    ReturnJSON($json,$contentType); 
  }
  if(is_array($reason))
  {
    $json = $reason;
    if(!array_key_exists('webpage', $json))
    {
      $json['webpage'] = '';
    }
    if(!array_key_exists('errormessage', $json))
    {
      $json['errormessage'] = 'Error';
    }
  }
  else
  {
    $json['reason'] = print_r($reason,true);
  }
  $json['details'] = array('SERVER' => $_SERVER, 'POST' => $_POST, 'GET' => $_GET, 'FILES' => $_FILES);
	ReturnJSON($json,$contentType);	
}

function ActionTag()
{
  $script = $_SERVER['REQUEST_URI'];
  $script = explode('?', $script)[0];
  $script = explode('index.php',$script)[0];
  $script = str_replace('/', '', $script);
  $tag = 'rewind.' . $script . '.' . $_SERVER['REQUEST_METHOD'];
  return $tag;
}

function ReturnJSON($json,$contentType = 'application/json')
{
  header('Cache-Control: no-cache, must-revalidate');
  header("Content-type: {$contentType}");
  echo json_encode($json);
  exit;
}

function Success($json, $contentType = 'application/json')
{
  ReturnJSON($json,$contentType);
  exit;
}

// Found on the internet at 
function EmailIsValid($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

function SendEmailNotification($sid,$mid,$pid,$rid)
{
  global $aws;
  $subject = 'Report Abuse';
  $html = '';
  $text = '';

  // send the user a link to a page to update their password
  $ses = $aws->get('v1.ses');
  $sender = "Do Not Reply <info@numberstation.com>";
  $destination = array('ToAddresses' => 'anthony@numberstation.com');

  $linkinemail = "http://data.numberstation.com/?sid=".$sid."&mid=".$mid;

  $html = '<div>Station ID - '.$sid.'</div>';
  $html .= '<div>Message ID - '.$mid.'</div>';
  $html .= '<div>Message Creator Unique ID - '.$pid.'</div>';
  $html .= '<div>Report Unique ID - '.$rid.'</div>';
  $html .= '<div style="text-align:center;margin-top:30px;font-size:22px;"><a href="'.$linkinemail.'">View Message</a></div>';
  $result = $ses->SendEmail($sender,
      array("ToAddresses"=> $destination),
            array("Subject"=>array("Data"=>$subject),
              "Body"=>array(
          "Text"=>array("Data"=>$text),
          "Html"=>array("Data"=>$html)
      )
    )
  );

  if($result->body->Error)
  {
  throw new Exception((string)$result->body->Error->Message);
  }
  return $result;
}
function Now()
{
  return intval(gmdate("U"));
}

function Server()
{
  return $_SERVER['SERVER_NAME'];
}

function HTTPS()
{
  return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
}

function IsPost()
{
  return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
}

function IsGet()
{
  return strtolower($_SERVER['REQUEST_METHOD']) === 'get';  
}

function IsDelete()
{
  return strtolower($_SERVER['REQUEST_METHOD']) === 'delete';    
}

function IsPut()
{
  return strtolower($_SERVER['REQUEST_METHOD']) === 'put';    
}

function GetFileNameWithoutExtension($filename)
{
    return substr($filename,0,strlen($filename)-strlen(pathinfo($filename,PATHINFO_EXTENSION))-1);
}

function ChangeFileExtension($filename,$ext)
{
    return GetFileNameWithoutExtension($filename) . '.' . $ext;
}

function FileExtension($path)
{
  return strtolower(pathinfo($path,PATHINFO_EXTENSION));
  // return pathinfo($path,PATHINFO_EXTENSION);
}

function FileName($path)
{
    return pathinfo($path,PATHINFO_FILENAME) . '.' . pathinfo($path,PATHINFO_EXTENSION);
}

function FixImageOrientation(&$image)
{
    $orientation = $image->getImageOrientation();

    switch($orientation)
    {
        case 0: // no idea
        case 1: // already correct
            break;
        case 2: // horizontal flip
            $image->flipImage();
            $image->setImageOrientation(1);
            break;
        case 3: // 180 rotate left (why not right?)

            $image->rotateImage(new ImagickPixel(), 180);
            $image->setImageOrientation(1);
            break;
        case 4: // vertical flip

            $image->flopImage();
            $image->setImageOrientation(1);
            break;
        case 5: // vertical flip + 90 rotate right

            $image->flopImage();
            $image->rotateImage(new ImagickPixel(),90);
            $image->setImageOrientation(1);
            break;
        case 6: // 90 rotate right

            $image->rotateImage(new ImagickPixel(), 90);
            $image->setImageOrientation(1);
            break;
        case 7: // horizontal flip + 90 rotate right

            $image->flipImage();
            $image->rotateImage(new ImagickPixel(), 90);
            $image->setImageOrientation(1);
            break;
        case 8: // 90 rotate left

            $image->rotateImage(new ImagickPixel(), -90);
            $image->setImageOrientation(1);
            break;

        default: // should never happen
            break;

    }
    return $orientation;
}

function safe_unlink($path)
{
    if(!empty($path) && file_exists($path))
    {
        unlink($path);
    }
}

function CleanUpDate($s)
{
    if(strpos($s, '.') > -1)
    {
        $parts = explode('.', $s);
        if(count($parts) != 2)
        {
            return $s;
        }
        $tail = $parts[1];
        $len = 1;
        while($len < strlen($tail) && is_numeric(substr($tail, 0, $len))) 
        { 
            $len += 1; 
        }
        $len -= 1;
        return $parts[0] . substr($tail,$len,strlen($tail)-$len+1);
    }
}

function ValueOrDie($key,$array,$description = null)
{
  if(is_null($description))
  {
    $description = $key;
  }
  if(array_key_exists($key,$array))
  {
    $value = $array[$key];
    if(strlen(trim($value)) > 0)
    {
      return $value;
    }
  }
  Fatal("No {$description} ({$key})");
}

function ValueOr($key,$array,$default=null)
{
  if(array_key_exists($key,$array))
  {
    $v = $array[$key];
    if(strlen(trim($v)) > 0)
    {
      return $v;
    }
    // if($v === '0' || $v === 0)
    // {
    //   return $v;
    // }
    // return empty($v) ? $default : $v;
  }
  return $default;
}

function BuildTestForm($params)
{
  $args = array();
  parse_str($params,$args);
  $html = '<form method="POST" action="."><table style="width: 100%;">';

  foreach($args as $k => $v)
  {
    $html = $html . "<tr><td>{$k}</td><td><input name='{$k}' value='{$v}' style='width: 100%'></td></tr>";
  }
  $html = $html . '</table><input type="submit"></form>';
  return $html;
}

function PostAsync($url, $params)
{
    $post_string = http_build_query($params);
    $parts=parse_url($url);

    $fp = fsockopen($parts['host'],
        isset($parts['port'])?$parts['port']:80,
        $errno, $errstr, 30);

  if(!$fp)
  {
    //Perform whatever logging you want to have happen b/c this call failed!  
  }
    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out.= "Host: ".$parts['host']."\r\n";
    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out.= "Content-Length: ".strlen($post_string)."\r\n";
    $out.= "Connection: Close\r\n\r\n";
    if (isset($post_string)) $out.= $post_string;

    fwrite($fp, $out);
    fclose($fp);
}

function map_json($a,$opts = null)
{
	if(!is_array($a))
	{
		$a = array($a);
	}
	if(is_null($opts))
	{
		$opts = array();
	}
	$result = array();
	foreach($a as $idx => $v)
	{
		$result[] = json_decode($v->to_json($opts));
	}
	return $result;
}

