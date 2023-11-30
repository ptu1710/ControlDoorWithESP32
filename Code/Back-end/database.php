<?php
$servername = "localhost";
$dbname = "doors_data";
$username = "admin";
$password = "patus";

session_start();

function createDoor($name, $status, $io)
{
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO Doors (name, status, io, last_open) VALUES ('" . $name . "', '" . $status . "', '" . $io . "', NOW())";

    if ($conn->query($sql) === TRUE) {
        return "New door created successfully";
    } else {
        return "Error: " . $sql . "<br>" . $conn->error;
    }
    $conn->close();
}

function deleteDoor($id)
{
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "DELETE FROM Doors WHERE id='" . $id .  "'";

    if ($conn->query($sql) === TRUE) {
        return "Door deleted successfully";
    } else {
        return "Error: " . $sql . "<br>" . $conn->error;
    }
    $conn->close();
}

function updateDoor($id, $status)
{
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "UPDATE Doors SET status='" . $status . "' WHERE id='" . $id .  "'";
    $conn->query($sql);
    if ($status == "1"){
    $sql = "UPDATE Doors SET last_open=NOW() WHERE id='" . $id .  "'";
    $conn->query($sql);
    }
    $conn->close();
}

function updateDoor1($io, $status)
{
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "UPDATE Doors SET status='" . $status . "' WHERE io='" . $io .  "'";
    $conn->query($sql);
    if ($status == "1"){
    $sql = "UPDATE Doors SET last_open=NOW() WHERE io='" . $io .  "'";
    $conn->query($sql);
    }
    $conn->close();
    return '{"status":"OK"}';
}


function getAllDoors()
{
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, name, status, io, last_open FROM Doors ORDER BY name";
    $rtn = $conn->query($sql);
    $conn->close();
    return $rtn;
}

function checkUserLogin($user, $pass)
{
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM Users WHERE user='$user' AND pass='$pass'";
    $result = $conn->query($sql);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $_SESSION['uname'] = $user;
        return true;
    }
    $conn->close();
    return false;
}

function logout($name)
{
    if (isset($_SESSION['uname']) && $_SESSION['uname'] == $name) {
        session_destroy();
    }
    return "OK";
}

function checkSession()
{
    if (isset($_SESSION['uname'])) {
        return $_SESSION['uname'];
    }
    return "";
}
