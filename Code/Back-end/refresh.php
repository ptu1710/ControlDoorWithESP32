<?php
include_once('database.php');

$result = getAllDoors();
$html_buttons = null;

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
    }
    echo $html_buttons;
}
echo "";
?>