<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include("../OpenDbConn.php");

// Returns one row per team for the season: record, win %, points for/against
// per game. Used to plot win% against scoring offense/defense across the
// whole league, so we don't filter by team_id here.

$season = isset($_GET['season']) ? intval($_GET['season']) : 2026;

$sql = "SELECT
            t.team_id,
            t.team_name,
            COUNT(*) AS games,
            SUM(tb.team_winner) AS wins,
            ROUND(AVG(tb.team_score), 1) AS ppg,
            ROUND(AVG(tb.opponent_team_score), 1) AS papg
        FROM team_box tb
        JOIN teams t ON tb.team_id = t.team_id
        WHERE tb.season = $season
        GROUP BY t.team_id, t.team_name
        HAVING COUNT(*) > 0
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
    $games = (int)$row['games'];
    $wins  = (int)$row['wins'];
    $data[] = [
        "team_id"   => (int)$row['team_id'],
        "team_name" => $row['team_name'],
        "games"     => $games,
        "wins"      => $wins,
        "losses"    => $games - $wins,
        "win_pct"   => $games > 0 ? round($wins / $games * 100, 1) : 0,
        "ppg"       => (float)$row['ppg'],
        "papg"      => (float)$row['papg'],
    ];
}

echo json_encode($data);

mysqli_close($db);
?>