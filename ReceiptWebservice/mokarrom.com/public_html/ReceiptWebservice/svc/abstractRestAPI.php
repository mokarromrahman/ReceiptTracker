<?php

// The following class will handle the parsing of a submitted URI and  
// returning the response to the request.  This class may be reused as 
// the core of any future RESTful API you may create.

abstract class AbstractAPI
{
    // Note:    private, protected, and public mean the same thing as in C# 
                
    
    protected $method = '';     // The type of HTTP request used. 
                                // (GET, PUT, POST, DELETE,)
    
    protected $endpoint = '';   // The model requested in the URI.  
                                // ie.  /titles if the request was sent to
                                // https://thor.net.nait.ca/~keletest/Demos/Demo05/Rest/files
                                // Remember the .htaccess will intercept.
    
    protected $verb = '';       // An optional descriptor about the endpoint that
                                // is not encompassed by the basic request types.
                                // ie.  /files/process
    
    protected $args = array();  // Any additional URI components after the endpoint
                                // and verb have been removed.
                                // ie.  /files/process/<arg0>/<arg1> 
                                // ie.  /files/<arg0>/<arg1>
    
    protected $file = Null;     // Stores the input of a PUT request.
    
    // In PHP contructors are always declared in this fashion, including the 
    // leading double underscore.  Note that we are accepting the $request 
    // extracted by the .htaccess RewriteRule. 
    public function __construct($request) 
    {
        
        // The following two lines will enable CORS - Cross-Origin Resource Sharing
        // which means clients from domains other than the one hosting the API may
        // connect to send and receive data.
//      header("Access-Control-Allow-Origin: *");   // Allow requests from all origins
//      header("Access-Control-Allow-Methods: *");  // Allow all types of HTTP method
        
        // Tell the browser receiving our response that we are sending json data
        header("Content-Type: application/json");
        
        
        // Next we'll split the string by slashes ( / ), and strip any trailing 
        // slashes on the URI typed in by the user.
        $this->args = explode('/', rtrim($request, '/'));
        
        // array_shift() pulls the first element from the front of the array 
        // unlike array_pop() which pulls the last element 
        $this->endpoint = array_shift($this->args);
        
        // If the next piece of the URI is non-numeric, it is our verb.  So save 
        // to our optional extra verb argument
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) 
        { 
            $this->verb = array_shift($this->args);
        }
        
        // Extract the HTTP method used to submit the request from $_SERVER
        $this->method = $_SERVER['REQUEST_METHOD'];
        switch ($this->method) 
        {
            case 'POST':
              $this->request = $this->_cleanInputs($_POST);
              break;
            case 'DELETE':
            case 'GET':
              $this->request = $this->_cleanInputs($_GET);
              break;
            case 'PUT':
              $this->request = $this->_cleanInputs($_GET); 
              $this->file = file_get_contents("php://input");
              break;
            default:
              echo $this->_response('Invalid Method', 405);
              die(); // actually want things to stop here.
              break;
        }
    }
    
    // Utility method to sanitize inputs, remove all tags, and trim white space out
    //  - recurse into embedded arrays
    private function _cleanInputs($data) 
    {
        $clean_input = Array();
        if (is_array($data)) 
        {
            foreach ($data as $k => $v) 
            {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } 
        else 
        {
          $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }
    
    // Dynamic "polymorphic" method. Uses the endpoint as the method name.
    // Verify the method and invoke it if it exists.
    public function processAPI() 
    {
        // if endpoint as method name exists, invoke with arguments, return with
        //  processed response
        if (method_exists($this, $this->endpoint)) 
        {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("No Endpoint: $this->endpoint", 404);
    }
    
    // construct response header, append processed json encoded response
    public function _response($data, $status = 200) 
    { 
        header($_SERVER["SERVER_PROTOCOL"] . " " . $status . " " 
                                           . $this->_requestStatus($status));
        return json_encode($data);
    }
    
    // Utility method to populate header response codes.
    // There are actually way more codes in the HTTP spec, but these will suffice.
    private function _requestStatus($code) {
        $status = array(
            200 => 'OK',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
}

