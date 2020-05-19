<?php
// Put your other needed requires here
require_once 'abstractRestAPI.php';  // include our base abstract class
require_once '../functions.php';

class ConcreteAPI extends AbstractAPI {

  // Since we don't allow CORS (Cross Origin Researouce Error/security error), we don't need to check Key Tokens
  // We will ensure that the user has logged in using our SESSION authentication
  // Constructor - use to verify our authentication, uses _response
  public function __construct($request, $origin) {
    parent::__construct($request);

    // Uncomment for authentication verification with your session
//    if (!isset($_SESSION["userID"]))
//      return $this->_response("Get Lost", 403);
  }

  /**
   * Example of an Endpoint/MethodName 
   * - ie tags, messages, whatever sub-service we want
   */
  protected function test() {
    // TEST BLOCK - comment out once validation to here is verified
    $resp["method"] = $this->method;
    $resp["request"] = $this->request;
    $resp["putfile"] = $this->file;
    $resp["verb"] = $this->verb;
    $resp["args"] = $this->args;
    //return $resp;
    // END TEST BLOCK
    if ($this->method == 'GET') {
      //return $this->verb;       // For testing if-else ladder
      return testGetHandler( $this->args );  // Invoke your handler here
    }
    elseif ($this->method == 'POST') {
      return testPostHandler( $this->args );// Invoke your handler here
    }
    elseif ($this->method == 'DELETE' && count($this->args) == 1 ) {
      return $this->args[0]; // ID of delete request
    }
    else {
      return $resp; // DEBUG usage, help determine why failure occurred
      return "Invalid requests";
    }
  }

  protected function messages() {
    // TEST BLOCK - comment out once validation to here is verified
    $resp["method"] = $this->method;
    $resp["request"] = $this->request;
    $resp["putfile"] = $this->file;
    $resp["verb"] = $this->verb;
    $resp["args"] = $this->args;
    //return $resp;
    // END TEST BLOCK

    if (!isset($_SESSION["userID"]))
      return $this->_response("Get Lost! Stop trying to hack me nerd!", 403);
    if ($this->method == 'GET') {
      return GetMessages($this->verb);       // For testing if-else ladder
      return testGetHandler( $this->args );  // Invoke your handler here
    }
    elseif ($this->method == 'POST') {
      return PostMessages($this->request);
      return testPostHandler( $this->args );// Invoke your handler here
    }
    elseif ($this->method == 'DELETE' && count($this->args) == 1 ) {
      return DeleteMessage( $this->args[0] ); // ID of delete request
      return $this->args[0]; // ID of delete request
    }
    else {
      return $resp; // DEBUG usage, help determine why failure occurred
      return "Invalid requests";
    }
  }
}

// The actual functionality block here
try 
{
    // Construct instance of our derived handler here
    $API = new ConcreteAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    // invoke our dynamic method, should find the endpoint requested.
    echo $API->processAPI();
} 
catch (Exception $e) 
{   // OOPs - we have a problem
    echo json_encode(Array('error' => $e->getMessage()));
}

function GetMessages($verb)
{
  //do we want inner joins?
  $query = "select pm.msgID, pu.userID, pu.username, pm.msg, pm.stamp from prj_messages pm ";
  $query .= "join prj_users pu on pu.userID = pm.userID ";
  $query .= "where pu.username like '%$verb%' or pm.msg like '%$verb%'";

  $data = array();
  $data['data'] = array();
  $data['status'] = "Failed to retrieve data.";

  if($result = mysqlQuery($query))
  {
      $rowsReturned = 0;

      // $data = array();
      // $data['data'] = array();
      // $data['status'] = "Failed to retrieve data.";
      
      while($row = $result->fetch_assoc())
      {
          $tempDataArray = array();
          $tempDataArray['msgID'] = $row['msgID'];
          $tempDataArray['userID'] = $row['userID'];
          $tempDataArray['username'] = $row['username'];
          $tempDataArray['msg'] = $row['msg'];
          $tempDataArray['timestamp'] = $row['stamp'];

          array_push($data['data'],$tempDataArray);

          $rowsReturned++;
      }

      $data['status'] = "Retrieved: $rowsReturned message records.";

  }

  return $data;
}

function testGetHandler( $args )
{
  $statment = "Hello from the GET handler for test";// - $verb: ";
  foreach($args as $key=>$value)
  {
    $statment .= "{key: $key; value: $value}; ";
  }

    //return "Hello from the GET handler for test";

    return $statment;
}

function testPostHandler( $args ){
    return PHPInfo();
}

//phpinfo();

function PostMessages($request)
{
  global $mysql_connection;

  $message = $mysql_connection->real_escape_string($request['message']);
  $userID = "";
  $query = "";
  if(isset($_SESSION['userID']))
  {
    $userID = $_SESSION['userID'];
    $query = "insert into prj_messages(msg,userID) VALUES(";
    $query .= "'$message', $userID)";
  }

  /*$query = "insert into prj_messages(msg,userID) VALUES(";
  $query .= "'$message', $userID)";*/

  if(strlen($query) < 1)
  {
    return "Stop trying to hack me nerd!";
  }
  $rowsAdded = mysqlNonQuery($query);

  $responseData = array();
  $responseData['rowsAdd'] = $rowsAdded;
  $responseData['status'] = "$rowsAdded rows added.";

  return $responseData;
}

function DeleteMessage( $keyID)
{
  $macUserID = 11;

  $findUserQuery = "select pm.userID from prj_messages pm where pm.msgID = $keyID";

  $msgUserID = array();
  if($foundUserID = mysqlQuery($findUserQuery))
  {
    $row = $foundUserID->fetch_assoc();
    $msgUserID['userID'] = $row['userID'];
  }

  error_log($msgUserID['userID']);
  error_log("Im working!!!!!!");

  $rowsDeleted = 0;
  $responseData = array();
  $responseData['rowsDeleted'] = $rowsDeleted;
  $responseData['status'] = "";
  if(isset($_SESSION['userID']) && ($_SESSION['userID'] == $msgUserID['userID'] || $_SESSION['userID'] == $macUserID))
  {
    $deleteQuery = "delete from prj_messages where prj_messages.msgID = $keyID";

    $rowsDeleted = mysqlNonQuery($deleteQuery);
    $responseData['status'] = "$rowsDeleted rows deleted. ";
  }
  else
  {
    $responseData['status'] = "You cannot delete that message. ";

  }

  return $responseData;
}
?>