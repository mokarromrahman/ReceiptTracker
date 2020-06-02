<?php
//This connects to the databse for us.
require_once('dbAccess.php');
require_once('functions.php');


 if(isset($_POST["submit"]) && $_POST["submit"] == "Login")
     {
        $user = array();
        $user["email"] = $_POST["email"];
        $user["password"]= $_POST["password"];
        //echo json_encode(($user));
        //check to make sure that the user has been verified
        //if not verified tell them to check their email for the link
        //Once the user is logged in then their credentials can be seen.
        $status = ValidateUser($user);
        echo json_encode($status);

        //send the info to the api page to check if their jwt is within the tokens table
        //and send them to the adding data/data viewing page.
        die();
    }

    echo("Unauthorized access.");



?>