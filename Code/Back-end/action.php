<?php
    include_once('database.php');

    $action = $id = $name = $status = $last_open = $last_close = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = test_input($_POST["action"]);
        if ($action == "door_create") {
            $name = test_input($_POST["name"]);
            $io = test_input($_POST["io"]);
            $status = test_input($_POST["status"]);
            $result = createDoor($name, $status, $io);
            echo $result;
        }
        else {
            echo "No data posted with HTTP POST.";
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = test_input($_GET["action"]);
        if ($action == "door_status") {
            $result = getAllDoors();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[$row["io"]] = $row["status"];
                }
            }
            echo json_encode($rows);
        }
        else if ($action == "door_update") {
            $id = test_input($_GET["id"]);
            $status = test_input($_GET["status"]);
            $result = updateDoor($id, $status);
            echo $result;
        }
	else if ($action == "esp_update") {
            $io = test_input($_GET["door"]);
            $status = test_input($_GET["status"]);
            $result = updateDoor1($io, $status);
            echo $result;
        }

        else if ($action == "door_delete") {
            $id = test_input($_GET["id"]);
            $result = deleteDoor($id);
            echo $result;
        }
        else if ($action == "logout") {
            $name = test_input($_GET["name"]);
            $result = logout($name);
            echo $result;
        }
        else {
            echo "Invalid HTTP request.";
        }
    }

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
?>
