<?php
    //this file will contain the ways to interact with the 
    //mySQL database
    $mysql_connection = null;
    $mysql_response = array();
    $mysql_status = "";

    mysqlConnect(); //call the function to connect to DB

    function mysqlConnect()
    {
        global $mysql_connection, $mysql_response;
        //server, user, password, databasename
        $mysql_connection = new mysqli("localhost", "mokarrom",
            "mysqlMokarrom1!", "receipts");

        //check if there was an error
        if ($mysql_connection->connect_error)
        {
            $mysql_response[] = 'Connect error (' .
                $mysql_connection->connect_errno .
                ') ' . $mysql_connection->connect_error;

            echo json_encode($mysql_response);
            die();
        }
        //echo 'Success';
    }

    //executing a query on the database
    //assumes that we already have a db connection
    function mysqlQuery($query)
    {
        global $mysql_connection, $mysql_response, $mysql_status;

        $results = false;

        //check if we have a valid mySQL connection
        if ($mysql_connection == null)
        {
            echo "No connection";
            $mysql_status = "No active database connection";
            return $results;
        }

        //execute the SQL statement and check the results
        if (!($results = $mysql_connection->query($query)))
        {
            //getting here indicates an error occurred
            $mysql_response[] = "Query error {$mysql_connection->errno} :" .
                "{$mysql_connection->error}";
            echo json_encode($mysql_response);
            die();
        }
        //if there was no error then return the results
        return $results;
    }
    
    function mysqlNonQuery ( $query )
    {
        global $mysql_connection, $mysql_response;
      
        // I adjusted this part to just spit out an error and die so we know it happens 
        // at the database level.
        if ( $mysql_connection == null )
        {
            $mysql_response[] = "No active database connection!";
            echo json_encode($mysql_response);
            die();
        }
    
        // query will only return true or false when no result set is to be returned
        if (!($mysql_connection->query( $query )))
        {
            $mysql_response[] = "Query Error {$mysql_connection->errno} : " .
                                    "{$mysql_connection->error}";
            echo json_encode($mysql_response);
            die();
        }
    
        return $mysql_connection->affected_rows;
    }
?>
