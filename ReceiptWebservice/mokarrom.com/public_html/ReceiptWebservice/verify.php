<?php
    require_once('dbAccess.php');

    //User the verification key found to activate the user.
    if(isset($_GET['vkey']))
    {
        //echo "Your account has been activated.";  
        echo ActivateUser($_GET['vkey']);
    }
    else
    {
        echo "Something went wrong.";
        die();
    }

    //Activate the user using the verification key.
    function ActivateUser($vkey)
    {
        $status = "Waiting to verify user.";

        $vKeyFound = FindUserFromVKey($vkey);
        if(!($vKeyFound["found"]))
        {
            $status = "This is not the activation link that was sent to you. Please check your email for the activation link.";
        }

        //Account already verified
        if($vKeyFound["verified"] == 1)
        {
            $status = "This account has already been verified.";
        }
        //Account not verified
        else
        {
            //Try to update the user to activate their account.
            $updateVerified = "update users_info set verified = 1 where userID = " .$vKeyFound['userID'].";";
            if(mysqlNonQuery($updateVerified) == 1)
            {
                $status = "Welcome " .$vKeyFound["firstName"]. " " .$vKeyFound["lastName"]. ". Your account has been activated.";
            }
            else
            {
                $status = "Something went wrong.";
            }
        }
        

        return $status;
    }

    //Find the assoc
    function FindUserFromVKey($vkey)
    {
        //make sure there is access to the db
        global $mysql_connection, $mysql_response, $mysql_status;

        $vkey = $mysql_connection->real_escape_string($vkey);

        $findUserQuery = "select u.first_name, u.last_name, u.userID, u.verified from users_info u where u.vkey = '$vkey';";

        $userExists = mysqlQuery($findUserQuery);

        $result = array();
        $result["userID"] = null;
        $result["found"] = false; 

        if($userExists->num_rows == 1)
        {
            $row = $userExists->fetch_assoc();
            $result["firstName"] = $row['first_name'];
            $result["lastName"] = $row['last_name'];
            $result["userID"] = $row['userID'];
            $result["verified"] = $row['verified'];
            $result["found"] = true; 
        }

        return $result;
    }
?>

<!-- <html>
        <h1>Hello</h1>
</html>  -->