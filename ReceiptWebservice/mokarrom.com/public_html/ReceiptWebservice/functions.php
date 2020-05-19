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
    //entered by the user.
    // function Validate($validate)
    // {
        
    //     global $mysql_connection, $mysql_response, $mysql_response;

    //     //striping tags in this case is not necessary
    //     //$validate['username'] = strip_tags($validate['username']);
    //     //$validate['password'] = strip_tags($validate['password']);

    //     //if(array_key_exists($validate['username'],$userTable))
    //     $validate['username'] = $mysql_connection->real_escape_string($validate['username']);
    //     $validate['password'] = $mysql_connection->real_escape_string($validate['password']);

    //     $userExists = mysqlQuery("select * from `prj_users` p where p.username like '" .$validate['username'] ."';");

    //     /*if(!isset($userTable[$validate['username']]))
    //     {
    //         $validate['response'] = "Failed to find " . $validate['username'] . ".";
    //         $validate['status'] = false;
    //         return $validate;
    //     }*/
    //     //echo $userExists;
    //     if($userExists->num_rows != 1)
    //     {
    //         $validate['response'] = "Failed to find " . $validate['username'] . ".";
    //         $validate['status'] = false;
    //         return $validate;
    //     }
        
    //     $row = $userExists->fetch_assoc();

    //     if(password_verify($validate['password'], $row['password']))
    //     {
    //         $_SESSION['username'] = $validate['username'];
    //         $_SESSION['userID'] = $row['userID'];
    //         $validate['response'] = "Welcome " . $validate['username'] . ", you have been logged in.";
    //         $validate['status'] = true;
    //     }
    //     else
    //     {
    //         $validate['response'] = "Your password is incorrect try again.";
    //         $validate['status'] = false;
    //     }

    //     return $validate;
    // }

    

    function RegisterUser($newUser)
    {
        //make sure there is access to the db
        global $mysql_connection, $mysql_response, $mysql_response;

        //check to make sure the passwords are the same
        if($newUser['password1'] != $newUser['password2'])
        {
            return "<p>Make sure your passwords are matching.</p>";
        }

        if(UserExist($newUser))
        {
            return "<p>This email has already been registered.</p>";
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
            return "<p>Something went wrong.</p>";
        }

        //Send the activation emai.
        return SendActivationEmail($firstName, $lastName, $email, $vkey);
    }

    //Send activation email to the user with the vkey
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

        $status = "hello?";

        if($mail->send())
        {
            //$status = "email sent";
            $status = "<p>" .$firstName. " " .$lastName. " thank you for registering. An activation link has been sent to ". $email. " to activate your account.</p>";
        }
        else
        {
            //$status = "email not sent";
            $status = "<p>Something went wrong:" .$mail->ErrorInfo. "</p>";
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

        //this email is registered.
        if($userExists->num_rows != 0)
        {
            $result = true;
        }

        return $result;
    }
?>