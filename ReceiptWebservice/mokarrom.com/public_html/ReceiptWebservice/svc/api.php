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

  protected function transactions()
  {
    $resp["method"] = $this->method;
    $resp["request"] = $this->request;
    $resp["putfile"] = $this->file;
    $resp["verb"] = $this->verb;
    $resp["args"] = $this->args;

    //Check that a valid token is being used.
    if(!TokenActive($resp["request"]))
    {
      echo "Unauthorized access.";
      return;
    }

    //echo TokenActive($resp["request"]);
    if($resp["method"] == "POST")
    {
      //return IsThisWorking($resp["request"]);
      return AddNewReceipt($resp["request"]);
    }
    // else if($resp["method"] == "GET")
    //https://en.ryte.com/wiki/GET_Parameter this is how get parameters are made
    //   echo json_encode($resp);
    else
    {
      echo json_encode("nothing here");
    }
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

function IsThisWorking($request)
{
  echo json_encode($request);//["message"]);
}

function TokenActive($request)
{
  //need the mysql_connection item
  global $mysql_connection;

  //Clean the input
  $token = $mysql_connection->real_escape_string($request['token']);
  
  //return value
  $active = false;  //Assume the token does not exist

  //Query to find the expiration time of the token given in the request
  $findExpTimeFromTokenQuery = "select expireTime from tokens where token = '" .$token."';";

  //check if any data was returned
  $tokenExists = mysqlQuery($findExpTimeFromTokenQuery);

  if($tokenExists->num_rows == 1)
  {
    //if data was returned then check if the current time has passed.
    //Fetch the data and convert the result to time.
    $row = $tokenExists->fetch_assoc();
    $expireTime = strtotime($row["expireTime"]);
    $currentTime = time();
    
    //If the difference between the expiry time and the current time is 
    //positive then the expiry time is in the future
    if(($expireTime - $currentTime) > 0)
    {
      $active  = true;
    }
  }
  //return true/false
  return $active ;
}


//Incoming Request should have the token, category name,
//store name, city, state/province, country, date and price.
function AddNewReceipt($request)
{
  //global mysql object
  global $mysql_connection;

  //result to be returned;
  $result = array();
  //Assume the transaction will not be successfully added.
  $result["successfullyAdded"] = false;
  $result["status"] = "This receipt could not be added.";

  //should the inputs be cleaned before being sent to the functions?
  //or should the functions clean the inputs before they do anyting with them?
  //clean inputs
  $token = $mysql_connection->real_escape_string($request['token']);
  $categoryName = $mysql_connection->real_escape_string($request['category']);
  $storeName = $mysql_connection->real_escape_string($request['store']);
  $city = $mysql_connection->real_escape_string($request['city']);
  $province = $mysql_connection->real_escape_string($request['province']);
  $country = $mysql_connection->real_escape_string($request['country']);
  //convert the date string received.
  $date = date("Y-m-d",strtotime($mysql_connection->real_escape_string($request['date'])));
  $price = $mysql_connection->real_escape_string($request['price']);

  //id variables
  $categoryID = array();
  $storeID = array();
  $locationID = array();
  $userID = array();

  //check if the category exists and insert it if it does not
  if(!CategoryExists($categoryName))
  {
    AddCategory($categoryName);
  }

  //check if the store exists and insert it if it does not
  if(!StoreExists($storeName))
  {
    AddStore($storeName);
  }

  //check if the location exists and insert it if it does not
  if(!LocationExists($city, $province, $country))
  {
    AddLocation($city, $province, $country);
  }

  //find the ids of the category, store, location, and user
  $categoryID = FindCategoryID($categoryName);
  $userID = FindUserIDFromToken($token);
  $storeID = FindStoreID($storeName);
  $locationID = FindLocationID($city, $province, $country);

  //If any of the ID's don't exist then we need to leave the function
  //and stop further processing.
  if(!$categoryID['found'] || !$userID['found'] || !$storeID['found'] || !$locationID['found'])
  {
    return $result;
  }

  //insert into the transaction table 
  $query = "insert into transactions(total_cost, date, userID, storeID, categoryID, locationID) ";
  $query .= "values ($price, '$date', ".$userID['id'].", ".$storeID['id'].", ".$categoryID['id'].", ".$locationID['id'].");";

  //If exactly 1 row was inserted then it was successful.
  $rowsAdded = mysqlNonQuery($query);
  if($rowsAdded == 1)
  {
    $result["successfullyAdded"] = true;
    $result["status"] = "Added $rowsAdded receipt.";
  }

  //return status of inserting the transaction
  return $result;
}

//Find the userID from the given token
function FindUserIDFromToken($token)
{
  //Assume that a userID with this token does not exist
  $result = array();
  $result["id"] = "";
  $result["found"] = false;

  //global mysql object
  global $mysql_connection;
  
  //clean the input
  $token = $mysql_connection->real_escape_string($token);

  //Find the ID using this query
  $query = "select t.userID from tokens t where t.token = '$token';";

  $IDExists = mysqlQuery($query);
  //If only 1 match is found then we can return this result.
  if($IDExists->num_rows == 1)
  {
    $row = $IDExists->fetch_assoc();
    $result["id"] = $row["userID"];
    $result["found"] = true;
  }

  return $result;
}

//Return true if a category with the provided category 
//name exists within the Category table 
function CategoryExists($categoryName)
{
  //global mysql object
  global $mysql_connection;
  
  //clean the input
  $categoryName = $mysql_connection->real_escape_string($categoryName);

  //Assume the category does not exist.
  $exists = false;

  //Look for the category with the category name
  $findCategoryQuery = "select * from category c where c.name = '" .$categoryName."';";
  $categoryFound = mysqlQuery($findCategoryQuery);

  //Exactly 1 category matching the name was found
  //therefore this category exists.
  if($categoryFound->num_rows == 1)
  {
    $exists = true;
  }

  return $exists;
}

//Insert the category into the category table
function AddCategory($categoryName)
{
  //global mysql object
  global $mysql_connection;

  //clean the input
  $categoryName = $mysql_connection->real_escape_string($categoryName);

  $insertQuery = "insert into category(name) values ('".$categoryName."');";

  $result = array();
  $result["rowsAdded"] = mysqlNonQuery($insertQuery);
  $result["status"] = "Added " .$result["rowsAdded"]." rows.";

  return $result;
}

//Find the categoryID from the category name
function FindCategoryID($categoryName)
{
  //Assume that a category with this name does not exist
  $result = array();
  $result["id"] = "";
  $result["found"] = false;

  //global mysql object
  global $mysql_connection;
  
  //clean the input
  $categoryName = $mysql_connection->real_escape_string($categoryName);

  //Find the ID using this query
  $query = "select c.categoryID from category c where c.name = '$categoryName';";

  $IDExists = mysqlQuery($query);
  //If only 1 match is found then we can return this result.
  if($IDExists->num_rows == 1)
  {
    $row = $IDExists->fetch_assoc();
    $result["id"] = $row["categoryID"];
    $result["found"] = true;
  }

  return $result;
}
//Return true if a store with the provided store 
//name exists within the Stores table 
function StoreExists($storeName)
{
  //global mysql object
  global $mysql_connection;

  //clean the input
  $storeName = $mysql_connection->real_escape_string($storeName);

  //Assume the store does not exist.
  $exists = false;

  //Look for the store with the store name
  $findStoreQuery = "select * from stores s where s.name = '" .$storeName."';";
  $storeFound = mysqlQuery($findStoreQuery);

  //Exactly 1 store matching the name was found
  //therefore this store exists.
  if($storeFound->num_rows == 1)
  {
    $exists = true;
  }

  return $exists;
}

//Insert the store into the stores table
function AddStore($storeName)
{
  //global mysql object
  global $mysql_connection;

  //clean the input
  $storeName = $mysql_connection->real_escape_string($storeName);

  $insertQuery = "insert into stores(name) values ('".$storeName."');";

  $result = array();
  $result["rowsAdded"] = mysqlNonQuery($insertQuery);
  $result["status"] = "Added " .$result["rowsAdded"]." rows.";

  return $result;
}

//Find the storeID from the store name
function FindStoreID($storeName)
{
  //Assume that a store with this name does not exist
  $result = array();
  $result["id"] = "";
  $result["found"] = false;

  //global mysql object
  global $mysql_connection;
  
  //clean the input
  $storeName = $mysql_connection->real_escape_string($storeName);

  //Find the ID using this query
  $query = "select s.storeID from stores s where s.name = '$storeName';";

  $IDExists = mysqlQuery($query);
  //If only 1 match is found then we can return this result.
  if($IDExists->num_rows == 1)
  {
    $row = $IDExists->fetch_assoc();
    $result["id"] = $row["storeID"];
    $result["found"] = true;
  }

  return $result;
}

//Return true if a location with the provided city, 
//state_province, and country exists within the Locations table 
function LocationExists($city, $province, $country)
{
  //global mysql object
  global $mysql_connection;

  //clean the inputs
  $city = $mysql_connection->real_escape_string($city);
  $province = $mysql_connection->real_escape_string($province);
  $country = $mysql_connection->real_escape_string($country);

  //Assume the location does not exist.
  $exists = false;

  //Look for the location with the city, state_province, and country
  $findLocationQuery = "select * from location l where l.city = '" .$city."' and ";
  $findLocationQuery .= "l.state_province = '" .$province."' and ";
  $findLocationQuery .= "l.country = '" .$country."';";

  $locationFound = mysqlQuery($findLocationQuery);

  //Exactly 1 location matching the city, province,
  //and country were found therefore this location exists.
  if($locationFound->num_rows == 1)
  {
    $exists = true;
  }

  return $exists;
}

//Insert the location into the location table
function AddLocation($city, $province, $country)
{
  //global mysql object
  global $mysql_connection;

  //clean the inputs
  $city = $mysql_connection->real_escape_string($city);
  $province = $mysql_connection->real_escape_string($province);
  $country = $mysql_connection->real_escape_string($country);

  $insertQuery = "insert into location(city, state_province, country) values ('$city', '$province', '$country');";

  $result = array();
  $result["rowsAdded"] = mysqlNonQuery($insertQuery);
  $result["status"] = "Added " .$result["rowsAdded"]." rows.";

  return $result;
}

//Find the locationID from the city province and country provided
function FindLocationID($city, $province, $country)
{
  //Assume that a location with this city, province,
  //and country does not exist
  $result = array();
  $result["id"] = "";
  $result["found"] = false;

  //global mysql object
  global $mysql_connection;
  
  //clean the inputs
  $city = $mysql_connection->real_escape_string($city);
  $province = $mysql_connection->real_escape_string($province);
  $country = $mysql_connection->real_escape_string($country);

  //Find the ID using this query
  $query = "select l.locationID from location l ";
  $query .= "where l.city = '$city' and ";
  $query .= "l.state_province = '$province' and ";
  $query .= "l.country = '$country';";

  $IDExists = mysqlQuery($query);
  //If only 1 match is found then we can return this result.
  if($IDExists->num_rows == 1)
  {
    $row = $IDExists->fetch_assoc();
    $result["id"] = $row["locationID"];
    $result["found"] = true;
  }

  return $result;
}
//Test code for the ____Exists() functions.
//echo json_encode(CategoryExists('Groceries'));
//echo json_encode(StoreExists('Real Canadian Superstore'));
//echo json_encode(LocationExists('Calgary', 'Alberta', 'Canada'));

//Test code for the Add___() functions.
//echo json_encode(AddCategory('Furniture'));
//echo json_encode(AddStore('Amazon'));
//echo json_encode(AddLocation('Banff', 'Alberta', 'Canada'));

//Testing code for the Find___ID() functions
//echo json_encode(FindCategoryID('food'));
//echo json_encode(FindStoreID('memory express'));
//echo json_encode(FindLocationID('adfadf','Alberta','Canada'));
//echo json_encode(FindUserIDFromToken('583cbc112c4a741908aed1d6cf5731cc'));


//Testing code for adding transactions
// $test = array();
// $test["token"] = '583cbc112c4a741908aed1d6cf5731cc';
// $test["category"] = 'Groceries';
// $test['store'] = "Walmart";
// $test["city"] = "Edmonton";
// $test["province"] = "Alberta";
// $test["country"] = "Canada";
// $test["date"] = strtotime('2020-06-03');
// $test["price"] = "4.14";
// echo json_encode($test);
// echo json_encode(AddNewReceipt($test));

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

// function testPostHandler( $args ){
//     return PHPInfo();
// }

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