<?php
    //use the php mailer class
    use PHPMailer\PHPMailer\PHPMailer;

    //This connects to the databse for us.
    require_once('dbAccess.php');

    //Include these to be able to send an email
    require_once('PHPMailer/PHPMailer.php');
    require_once('PHPMailer/SMTP.php');
    require_once('PHPMailer/Exception.php');

    //Function used to validate the username and password 
    //entered by the user an create a temporary token they can
    //use to acceess the REST API.
    function ValidateUser($user)
    {
        global $mysql_connection, $mysql_response, $mysql_status;

        //Clean the inputs
        $user['email'] = $mysql_connection->real_escape_string($user['email']);
        $user['password'] = $mysql_connection->real_escape_string($user['password']);

        //find the userID and if the user is verified or not with the provided email.
        $userExists = mysqlQuery("select u.userID, u.first_name, u.last_name,  u.hash_pass, u.verified, u.createdate from users_info u where u.email = '" .$user['email']. "';"); 

        $response = array();

        //user was not found with this email
        if($userExists->num_rows != 1)
        {
            $response['response'] = "Failed to find " . $user['email'] . ".";
            $response['found'] = false;
            return $response;
        }

        //if we're down here then the user was found.
        $row = $userExists->fetch_assoc();

        //verify the password
        if(password_verify($user['password'], $row['hash_pass']))
        {
            //check to see if the account has been verified
            if($row["verified"] == 0)
            {
                $date = strtotime(($row["createdate"]));
                $date = date('M d, Y', $date);
                $response['response'] = "This account has not been verified yet. A verification email was sent to " .$user["email"]. " on " .$date. ".";
                $response["found"] = false;
                return $response;
            }

            //tell the user they have been logged in
            $response["userID"] = $row["userID"];
            $response["email"] = $user['email'];
            $response['response'] = $row['first_name'] . " " .$row['last_name'] . ", you have been logged in.";
            //create token
            $response['token'] = CreateToken($row);
            $response['found'] = true;
            $response['correctPass'] = true;
        }
        //Incorrect password.
        else
        {
            $response['response'] = "The password associated with this email is incorrect. Please try again.";
            $response['found'] = true;
            $response['correctPass'] = false;
        }

        return $response;//json_encode($response);
    }

    
    //Create and insert a unique token into the tokens table in the database
    //and return the token if it does not already exist within the table.
    function CreateToken($user)
    {
        //Used this link to learn how to create tokens.
        //https://stackoverflow.com/questions/1846202/php-how-to-generate-a-random-unique-alphanumeric-string

        //Used this link to find the proper formatting of the expireTime to insert into the table.
        //https://stackoverflow.com/questions/37616760/how-to-insert-timestamp-into-my-mysql-table#:~:text=In%20addition%20to%20checking%20your,H%3Ai%3As%22)%3B
        
        //Check if the user already has a token and if they do delete it
        //so that a new token can be created.
        if(UserHasToken($user))
        {
            DeleteUserToken($user);
        }

        //create the token
        $token = md5(uniqid($user["first_name"].$user["last_name"],true));
        
        //Check to ensure that the token does not already exists within the table.
        //If the token exists, then make new tokens until there is a unique token found
        while(TokenExists($token))
        {
            $token = md5(uniqid($user["first_name"].$user["last_name"],true));
        }

        //insert the token into the table
        $expireTime = date("Y-m-d H:i:s", time() + 300);//5 minutes until this token expires
        $insertTokenQuery = "insert into tokens (token, userID, expireTime) ";
        $insertTokenQuery .= "values ('$token', ".$user['userID']. ", '" .$expireTime ."')";
        /*June 1, 2020 
        There were errors in properly inserting the 
        token into the table because the userID was not specified
        and the entered date did not have ' ' around its value.
        Fixed by adding the ' ' around the expireDate value and adding
        a proper userID which already existed in the users_info table.
        */
        //echo $insertTokenQuery;

        //Execute the query and insert into the table.
        $tokenAdded = mysqlNonQuery($insertTokenQuery);

        //If there was not exactly 1 row inserted, then something went wrong.
        if($tokenAdded != 1)
        {
            return;
        }

        //By this point, the token was inserted and it can be returned.
        return $token;
    }
    
    /*June 1, 2020 
    Used this to test the CreateToken() function.
    There were errors in properly inserting the 
    token into the table because the userID was not specified
    and the entered date did not have ' ' around its value.
    Fixed by adding the ' ' around the expireDate value and adding
    a proper userID which already existed in the users_info table.
    */
    // $me = array();
    // $me["first_name"] = "mokarrom";
    // $me["last_name"] = "rahman";
    // $me["userID"] = 26;
    // CreateToken($me);

    //Delete a token from the tokens tables using the userID.
    function DeleteUserToken($user)
    {
        $deleteTokenQuery = "delete from tokens where userID = ".$user['userID'].";";
        mysqlNonQuery($deleteTokenQuery);
    }
    //Uses the userID to search for an existing token for this user.
    //Returns true if found and false if this user does not have a token
    //currently.
    function UserHasToken($user)
    {
        //Database objects needed
        global $mysql_connection, $mysql_response, $mysql_status;

        //Assume a token does not exist for this user.
        $result = false;

        //Look for the token using the userID
        $tokenExist = mysqlQuery("select * from `tokens` t where t.userID = " .$user["userID"] .";");

        //A token already exists
        if($tokenExist->num_rows != 0)
        {
            $result = true;
        }

        return $result;
    }
    //Look for a token within the token table and return true
    //if it exists otherwise return false.
    function TokenExists($token)
    {
        //Database objects needed
        global $mysql_connection, $mysql_response, $mysql_status;

        //Assume the token does not exist.
        $result = false;
        
        //Clean the token just in case
        $token = $mysql_connection->real_escape_string($token);

        //look for the token
        $tokenExist = mysqlQuery("select * from `tokens` t where t.token like '" .$token ."';");

        //Token already exists if the number of rows is not 0.
        if($tokenExist->num_rows != 0)
        {
            $result = true;
        }

        return $result;
    }

    //Register a new user within the database.
    function RegisterUser($newUser)
    {
        //make sure there is access to the db
        global $mysql_connection, $mysql_response, $mysql_response;

        //Response array
        $response = array();
        $response["status"] = "";
        $response["userExists"] = false;
        $response["otherIssues"] = false;
        $response["userCreatedSuccess"] = false;

        //check to make sure the passwords are the same
        if($newUser['password1'] != $newUser['password2'])
        {
            $response["status"] = "Make sure your passwords are matching.";
            return $response;
        }

        if(UserExist($newUser))
        {
            $response["status"] = "This email has already been registered.";
            $response["userExists"] = UserExist($newUser);
            return $response;
        }

        //Sanitize entries and hash passwords
        $firstName = $mysql_connection->real_escape_string($newUser['firstName']);
        $lastName = $mysql_connection->real_escape_string($newUser['lastName']);
        $email = $mysql_connection->real_escape_string($newUser['email']);
        $password = $mysql_connection->real_escape_string($newUser['password1']);
        //Don't need to escape string password2 because it is not used.
        //$password2= $mysql_connection->real_escape_string($newUser['password2']);
        
        //generate the vkey and hash the password
        $vkey = md5(time().$newUser['firstName'].$newUser['lastName']);
        $password = password_hash($password, PASSWORD_DEFAULT);

        //create insert statement
        $insert = "Insert into users_info(first_name, last_name, email, ";
        $insert .= "hash_pass, vkey) ";
        $insert .= "Values ('" .$firstName ."', ";
        $insert .= "'" .$lastName ."', ";
        $insert .= "'" .$email ."', ";
        $insert .= "'" .$password ."', ";
        $insert .= "'" .$vkey ."');";

        //insert into db
        $rowsAdded = mysqlNonQuery($insert);

        //if exactly 1 row isn't returned them something went wrong.
        if($rowsAdded != 1)
        {
            $response["otherIssues"] = true;
            $response["status"] = "Something went wrong.";
            return $response;
        }

        //Send the activation email.
        $response["status"] = SendActivationEmail($firstName, $lastName, $email, $vkey);
        $response["userCreatedSuccess"] = true;

        return $response;
    }

    //Send activation email to the user with the provided verification key. 
    //Used in the RegisterUser function.
    function SendActivationEmail($firstName, $lastName, $email, $vkey)
    {
        $mail = new PHPMailer(true);

        //SMTP settings
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "receiptTrackerMR@gmail.com";
        $mail->Password = "rTracker0094!?";
        $mail->Port = 465; //587,465
        $mail->SMTPSecure = "ssl"; //tls,ssl

        //Email settings
        $mail->setFrom("receiptTrackerMR@gmail.com","Mokarrom Rahman Receipt Tracker");
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $firstName. " " .$lastName. " Verification Email";
        $mail->Body = "<a href='http://www.mokarrom.com/ReceiptWebservice/verify.php?vkey=" .$vkey. "'>Please click this link to activate your account.</a>";

        $status = "";

        if($mail->send())
        {
            //$status = "email sent";
            $status = $firstName. " " .$lastName. " thank you for registering. An activation link has been sent to ". $email. " to activate your account.";
        }
        else
        {
            //$status = "email not sent";
            $status = "Something went wrong:" .$mail->ErrorInfo;
        }

        return $status;
    }

    //Check the database to see if this user
    //is already registered by checking the email.
    function UserExist($newUser)
    {
        //make sure there is access to the db
        global $mysql_connection, $mysql_response, $mysql_status;

        $newUser['email'] = $mysql_connection->real_escape_string($newUser['email']);

        $userExists = mysqlQuery("select * from `users_info` u where u.email like '" .$newUser['email'] ."';");

        //Assume that the email is not yet registered.
        $result = false;

        //This email is registered.
        if($userExists->num_rows != 0)
        {
            $result = true;
        }

        return $result;
    }
?>