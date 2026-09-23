<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include("../OpenDbConn.php");

$season = isset($_GET['season']) ? intval($_GET['season']) : 2026;

$sql = "SELECT DISTINCT tb.team_id, t.team_name
        FROM team_box tb
        JOIN teams t ON tb.team_id = t.team_id
        WHERE tb.season = $season
        ORDER BY t.team_name ASC";

$result = mysqli_query($db, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);

mysqli_close($db);
?>