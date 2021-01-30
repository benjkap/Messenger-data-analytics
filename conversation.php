<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('memory_limit', '1024M');

if (!isset($_GET['id']) || !isset($_GET['path'])) {
    header('Location: ./');
    exit();
}

//Debug functions (not work with all)
$logCslPhp = [];
function consoleLog()
{
    $variables = [];
    global $logCslPhp;
    foreach (func_get_args() as $arg) {
        $escapers = array("\\", "/", "'", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\'", "\\n", "\\r", "\\t", "\\f", "\\b");
        array_push($variables, "JSON.parse('" . str_replace($escapers, $replacements, json_encode(($arg))) . "'),");
    }
    $codeJavascript = "<script>console.log(";
    foreach ($variables as $variable) $codeJavascript .= $variable;
    $codeJavascript .= ");</script>";
    array_push($logCslPhp, str_split($codeJavascript, 10000));
}

function consoleTable()
{
    $variables = [];
    global $logCslPhp;
    foreach (func_get_args() as $arg) {
        $escapers = array("\\", "/", "'", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\'", "\\n", "\\r", "\\t", "\\f", "\\b");
        array_push($variables, "JSON.parse('" . str_replace($escapers, $replacements, json_encode($arg)) . "'),");
    }
    $codeJavascript = "<script>console.table(";
    foreach ($variables as $variable) $codeJavascript .= $variable;
    $codeJavascript .= ");</script>";
    array_push($logCslPhp, str_split($codeJavascript, 10000));
}

$dir = './messages/'. $_GET['path'] .'/';
$convs = scandir($dir);

$dirName = $convs[intval($_GET['id'])];
$path = $dir . $dirName . '/';
$dirConv = scandir($path);

foreach ($dirConv as $file) {
    if (strpos($file, "message") === false) {
        if (($key = array_search($file, $dirConv)) !== false) unset($dirConv[$key]);
    } else if (($key = array_search($file, $dirConv)) !== false) $dirConv[$key] = $path . $dirConv[$key];
}
array_multisort(array_map('strlen', $dirConv), $dirConv);

$title = json_decode(file_get_contents($dirConv[0]), true)['title'];

$participants = [];
foreach ((array)json_decode(file_get_contents($dirConv[0]), true)['participants'] as $participant) array_push($participants, $participant['name']);

$messages = [];
foreach ($dirConv as $file) foreach ((array)json_decode(file_get_contents($file), true)['messages'] as $message) array_push($messages, $message);

$firstMessage = $messages[sizeof($messages) - 1];
$lastMessage = $messages[0];


//message par jour --------------------------------------------------------------

$dates = [];

$startDate = new DateTime();
$startDate->setTimestamp(round($firstMessage['timestamp_ms'] / 1000));
$str = $startDate->format('d/m/Y') . ' 00:00:00';
$startDate = date_create_from_format('d/m/Y H:i:s', $str);

$endDate = new DateTime();
$endDate->setTimestamp(round($lastMessage['timestamp_ms'] / 1000));
$str = $endDate->format('d/m/Y') . ' 00:00:00';
$endDate = date_create_from_format('d/m/Y H:i:s', $str);

$iDate = clone $startDate;

while ($iDate <= $endDate) {
    $dates[$iDate->getTimestamp() * 1000] = 0;
    $iDate->add(new DateInterval('P1D'));
}

foreach ($messages as $message) {
    $date = new DateTime();
    $date->setTimestamp(round($message['timestamp_ms'] / 1000));
    $str = $date->format('d/m/Y') . ' 00:00:00';
    $day = date_create_from_format('d/m/Y H:i:s', $str);

    $dates[$day->getTimestamp() * 1000]++;
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messenger data analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment-with-locales.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
    <script src="script/conversation.js"></script>
</head>
<body>
<div id="messagePerDays" style="display: none;"><?php echo json_encode($dates); ?></div>

<div class="mx-3 mt-3">
    <div class="row d-flex">
        <div class="col-4 flex-fill" style="width: 300px; flex: 0 1 auto!important;">
            <div class="list-group" id="list-tab" role="tablist">
                <button class="list-group-item list-group-item-action"  data-bs-toggle="list" role="tab" disabled aria-controls="profile"><?php echo utf8_decode($title); ?></button>
                <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#nbMessage" role="tab" aria-controls="home">Nombre de messages</a>
            </div>
        </div>
        <div class="col-8 flex-fill">
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nbMessage" role="tabpanel" aria-labelledby="list-home-list">
                    <div class="card">
                        <div class="card-body">
                            Nombre de messages: <span class="fw-bold"><?php echo sizeof($messages); ?></span>
                        </div>
                    </div>
                    <div class="chart-container w-100 mx-auto mt-3">
                        <canvas id="myChart" width="1920" height="540"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade" id="home" role="tabpanel" aria-labelledby="list-profile-list">...
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW"
        crossorigin="anonymous"></script>
<?php
//en fin de page
foreach ($logCslPhp as $log) {
    foreach ($log as $logPart) echo $logPart;
    echo "\n";
} ?>
</html>
