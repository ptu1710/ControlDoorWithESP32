<?php
include_once('database.php');
global $conn;
$uname = checkSession();

if ($uname != "") {
    echo "<script>location.href='home.php'</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, maximum-scale=1.0, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="./assets/favicon.png">
    <link rel="stylesheet" href="./assets/grid.css">
    <link rel="stylesheet" href="./assets/login.css">
    <title>Door Control</title>

</head>

<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<body>
    <div class="main">
        <div class="header">
            <div class="header-wel">
                <span><b>WELCOME</b></span>
            </div>
        </div>

        <div class="container grid">
            <div class="row">
                <div class="login-form col c-12">
                    <div class="login-logo">
                        <img src="./assets/login.png" alt="login">
                    </div>
                    <?php
                    if ($_POST) {
                        $user = $_POST["Username"];
                        $pass = $_POST["Password"];

                        if (checkUserLogin($user, $pass)) {
                            header("Location: home.php");
                        } else {
                            echo '<p style="color: red;">Wrong username or password!</p>';
                        }
                    }
                    ?>

                    <form class="form-input" action="login.php" method="post">
                        <label for="username">Username:</label>
                        <input type="text" name="Username" id="username" autocomplete="off" required>
                        <label for="password">Password:</label>
                        <input type="password" name="Password" id="password" required>
                        <div class="login-btn-div" style="display: flex; justify-content: center;">
                            <button id="login-btn" type="submit">SIGN IN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
