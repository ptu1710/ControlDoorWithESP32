<?php
include_once('database.php');

$uname = checkSession();

if ($uname == "") {
    echo "<script>location.href='login.php'</script>";
}

$result = getAllDoors();
$html_buttons = null;
$html_history = null;

if ($result) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        if ($row["status"] == "1") {
            $button_checked = "checked";
        } else {
            $button_checked = "";
        }
        $html_buttons .= '<li><div class="idDoor">' . $count . '</div>
        <div><div class="buttontitle">' . $row["name"] . ' - GPIO ' . $row["io"] . '</div>
            <div class="groupButton">
                <label class="switch"><input type="checkbox" onchange="updateDoor(this)" id="' . $row["id"] . '" ' . $button_checked . '>
                    <span class="slider"><p class="status"><b>OPENED</b></p><p class="status"><b>CLOSED</b></p></span></label>
            </div></div>
        <i><a onclick="deleteDoor(this)" href="javascript:void(0);" id="' . $row["id"] . '" class="deleteButton"><img class="iconDelete" src="./assets/delete.png" /></a></i></li>';
        $html_history .= '<li><div class="historyInfor">' . $row["name"] . ' was opened</div><div class="timeInfor">' . $row["last_open"] . '</div></li>';
    }
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="./assets/favicon.png">
    <link rel="stylesheet" type="text/css" href="./assets/home.css">
    <title>Doors Control</title>
</head>

<body>
    <div id="header">
        <div id="header-logo">Doors Control</div>
        <div id="header-button">
            <div class="buttonMenu">
                <img class="button" src="./assets/history.png" />
                <ul class="subnav">
                    <?php echo $html_history; ?>
                </ul>
            </div>
            <div onclick="onLogout();"><img class="button" src="./assets/logout.png" /></div>
        </div>
    </div>
    <div id="bodyApp">
        <div class="bodyitem">
            <div class="bodyitem-title">Doors</div>
            <div class="bodyitem-app" id="DoorbuttonArea">
                <ol id="listButton">
                    <?php echo $html_buttons; ?>
                </ol>
            </div>
        </div>
        <hr class="hritem">
        <div class="bodyitem">
            <div class="bodyitem-title">Create New</div>
            <div class="bodyitem-app">
                <div id="enterInfor">
                    <form">
                        <label class="titleInfor" for="doorName">Name:</label>
                        <input class="InputInfor" type="text" name="name" id="doorName" autocomplete="FALSE" required />
                        <label class="titleInfor" for="gpioNum">GPIO Number:</label>
                        <input class="InputInfor" type="number" name="gpio" min="0" id="gpioNum" required />
                        <label class="titleInfor" for="doorStatus">Current Status:</label>
                        <select class="InputInfor" id="doorStatus" name="status">
                            <option value="0" selected>0 = Close</option>
                            <option value="1">1 = Open</option>
                        </select>
                        </form>
                </div>
                <button onclick="createDoor();" type="submit" class="enterinfor-button">CREATE</button>
            </div>
        </div>
    </div>
    <div id="Note">
        <u><i>Note</i></u>: Refresh the page after
        <span style="color: #589E3F; margin: 0 4px;">Creating</span>
        or
        <span style="color: #589E3F; margin: 0 4px;"> Deleting</span> devices.
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            setInterval(function() {
                $("#listButton").load("refresh.php");
                refresh();
            }, 2000);
        });
    </script>
    <script>
        $(document).ready(function() {
            setInterval(function() {
                $("#subnav").load("refresh1.php");
                refresh();
            }, 2000);
        });
    </script>
    <script>
        function updateDoor(element) {
            var xhr = new XMLHttpRequest();
            if (element.checked) {
                xhr.open("GET", "action.php?action=door_update&id=" + element.id + "&status=1", true);
            } else {
                xhr.open("GET", "action.php?action=door_update&id=" + element.id + "&status=0", true);
            }
            xhr.send();
        }

        function deleteDoor(element) {
            var result = confirm("Want to delete this door?");
            if (result) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "action.php?action=door_delete&id=" + element.id, true);
                xhr.send();
                window.location.reload();
            }
        }

        function createDoor(element) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "action.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    window.location.href = "home.php";
                }
            }
            var doorName = document.getElementById("doorName").value;
            var gpioNum = document.getElementById("gpioNum").value;
            var doorStatus = document.getElementById("doorStatus").value;
            var httpRequestData = "action=door_create&name=" + doorName + "&io=" + gpioNum + "&status=" + doorStatus;
            xhr.send(httpRequestData);
        }

        function onLogout() {
            var name = "<?php print($uname); ?>";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "action.php?action=logout&name=" + name, true);
            xhr.send();
            window.location.reload();
        }
    </script>
</body>

</html>
