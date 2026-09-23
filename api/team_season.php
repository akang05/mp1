<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include("../OpenDbConn.php");

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$season  = isset($_GET['season'])  ? intval($_GET['season'])  : 2026;

if ($team_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "team_id is required"]);
    mysqli_close($db);
    exit;
}

// --- Season summary ---
$sql = "SELECT
            t.team_name,
            COUNT(*) AS games,
            SUM(tb.team_winner) AS wins,
            SUM(CASE WHEN tb.team_home_away = 'home' THEN 1 ELSE 0 END) AS home_games,
            SUM(CASE WHEN tb.team_home_away = 'home' THEN tb.team_winner ELSE 0 END) AS home_wins,
            SUM(CASE WHEN tb.team_home_away = 'away' THEN 1 ELSE 0 END) AS away_games,
            SUM(CASE WHEN tb.team_home_away = 'away' THEN tb.team_winner ELSE 0 END) AS away_wins,
            ROUND(AVG(tb.team_score), 1) AS ppg,
            ROUND(AVG(tb.opponent_team_score), 1) AS papg,
            ROUND(AVG(tb.field_goal_pct), 1) AS fg_pct,
            ROUND(AVG(tb.three_point_field_goal_pct), 1) AS three_pct,
            ROUND(AVG(tb.free_throw_pct), 1) AS ft_pct,
            ROUND(AVG(tb.total_rebounds), 1) AS reb,
            ROUND(AVG(tb.assists), 1) AS ast,
            ROUND(AVG(tb.turnovers), 1) AS tov,
            ROUND(AVG(tb.steals), 1) AS stl,
            ROUND(AVG(tb.blocks), 1) AS blk
        FROM team_box tb
        JOIN teams t ON tb.team_id = t.team_id
        WHERE tb.team_id = $team_id AND tb.season = $season
        GROUP BY t.team_name";

$result = mysqli_query($db, $sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}
$summary = mysqli_fetch_assoc($result);
if (!$summary) {
    echo json_encode(null); // no games for this team/season
    mysqli_close($db);
    exit;
}

$games = (int)$summary['games'];
$wins = (int)$summary['wins'];
$home_wins = (int)$summary['home_wins'];
$home_games = (int)$summary['home_games'];
$away_wins = (int)$summary['away_wins'];
$away_games = (int)$summary['away_games'];

// --- Game log ---
$sql2 = "SELECT tb.game_date, tb.opponent_team_name, tb.team_score AS pf,
                 tb.opponent_team_score AS pa, tb.team_winner, tb.team_home_away
         FROM team_box tb
         WHERE tb.team_id = $team_id AND tb.season = $season
         ORDER BY tb.game_date ASC";

$result2 = mysqli_query($db, $sql2);
if (!$result2) {
    http_response_code(500);
    echo json_encode(["error" => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}

$games_log = [];
while ($row = mysqli_fetch_assoc($result2)) {
    $date = new DateTime($row['game_date']);
    $games_log[] = [
        "date" => $date->format("n/j"),
        "opp"  => $row['opponent_team_name'],
        "pf"   => (int)$row['pf'],
        "pa"   => (int)$row['pa'],
        "win"  => (bool)$row['team_winner'],
        "home" => $row['team_home_away'] === 'home',
    ];
}

echo json_encode([
    "team_id"     => $team_id,
    "team_name"   => $summary['team_name'],
    "season"      => $season,
    "games"       => $games,
    "wins"        => $wins,
    "losses"      => $games - $wins,
    "win_pct"     => $games > 0 ? round($wins / $games * 100, 1) : 0,
    "ppg"         => (float)$summary['ppg'],
    "papg"        => (float)$summary['papg'],
    "fg_pct"      => (float)$summary['fg_pct'],
    "three_pct"   => (float)$summary['three_pct'],
    "ft_pct"      => (float)$summary['ft_pct'],
    "reb"         => (float)$summary['reb'],
    "ast"         => (float)$summary['ast'],
    "tov"         => (float)$summary['tov'],
    "stl"         => (float)$summary['stl'],
    "blk"         => (float)$summary['blk'],
    "home_record" => "$home_wins-" . ($home_games - $home_wins),
    "away_record" => "$away_wins-" . ($away_games - $away_wins),
    "games_log"   => $games_log,
]);

mysqli_close($db);
?>