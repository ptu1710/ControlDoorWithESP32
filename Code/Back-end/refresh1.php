<?php
include_once('database.php');

$result = getAllDoors();
$html_history = null;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $html_history .= '<li><div class="historyInfor">' . $row["name"] . ' was opened</div><div class="timeInfor">' . $row["last_open"] . '</div></li>';
    }
    echo $html_history;
}
?>