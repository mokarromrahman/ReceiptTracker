<?php
//This connects to the databse for us.
require_once('dbAccess.php');
require_once('functions.php');

//check to make sure that the user has been verified
//if not verified tell them to check their email for the link
if(isset($_POST["submit"]))
    {
        $user = array();
        $user["email"] = $_POST["email"];
        $user["password"]= $_POST["password"];

        $status = ValidateUser($user);
        //echo json_encode($status);
    }
//send the info to the api page to check if their jwt is within the tokens table
//and send them to the adding data/data viewing page.

?>

<html>

<head>
    <link href="style.css" rel="stylesheet" type="text/css" />
</head>

<body>
    <header align="center"><h1 align="center">Login</h1></header>
        <form method="POST" action="">
            <table border="0" align="center" cellpadding="5">
                <tr>
                    <td align="right">Email Address:</td>
                    <td><input type="email" name="email" required/></td>
                </tr>
                <tr>
                    <td align="right">Password:</td>
                    <td><input type="password" name="password" required/></td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" name="submit" value="Login" required/>
                    </td>
                </tr>
            </table>
        </form>
        <center>
        <?php
            //$status = json_decode($status);
            if($status['found'] && !$status["correctPass"])
                echo $status["response"];
            //echo $_POST["submit"];
            //return json_encode($status);
        ?>
        </center>
</body>
</html>