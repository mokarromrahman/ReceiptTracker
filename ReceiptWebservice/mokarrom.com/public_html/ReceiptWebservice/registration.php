<?php
    require_once('functions.php');

    $status = null;
    
    //if the user has submitted then take the neccessary fields
    if(isset($_POST["submit"]))
    {
        $newUser = array();
        $newUser["firstName"] = $_POST["firstName"];
        $newUser["lastName"] = $_POST["lastName"];
        $newUser["email"] = $_POST["email"];
        $newUser["password1"]= $_POST["password1"];
        $newUser["password2"] = $_POST["password2"];

        $status = RegisterUser($newUser);
    }
?>

<html>
    <head>
        <link href="style.css" rel="stylesheet" type="text/css"/>
    </head>

    <body>
        <form method="POST" action="">
            <table border="0" align="center" cellpadding="5">
                <tr>
                    <td align="right">First Name:</td>
                    <td><input type="text" name="firstName" required/></td>
                </tr>
                <tr>
                    <td align="right">Last Name:</td>
                    <td><input type="text" name="lastName" required/></td>
                </tr><tr>
                    <td align="right">Email Address:</td>
                    <td><input type="email" name="email" required/></td>
                </tr>
                <tr>
                    <td align="right">Password:</td>
                    <td><input type="password" name="password1" required/></td>
                </tr>
                <tr>
                    <td align="right">Repeat password:</td>
                    <td><input type="password" name="password2" required/></td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" name="submit" value="Register" retuired/>
                    </td>
                </tr>
            </table>
        </form>
        <center>
        <?php
            echo $status;
        ?>
        </center>
    </body>
</html>
