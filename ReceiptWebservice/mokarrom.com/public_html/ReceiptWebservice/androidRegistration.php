<?php
    require_once('functions.php');

    //check to make sure a post was made
    
    $status = null;
    
    //if the user has submitted then take the neccessary fields
    if(isset($_POST["submit"]) && $_POST["submit"] == "Register")
    {
        $newUser = array();
        $newUser["firstName"] = $_POST["firstName"];
        $newUser["lastName"] = $_POST["lastName"];
        $newUser["email"] = $_POST["email"];
        $newUser["password1"]= $_POST["password1"];
        $newUser["password2"] = $_POST["password2"];

        $status = RegisterUser($newUser);

        echo json_encode($status);
        die();
    }

    echo("Unauthorized access.");
?>