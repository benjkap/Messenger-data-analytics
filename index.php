<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

function base62_encode($data) {
    $outstring = '';
    $l = strlen($data);
    for ($i = 0; $i < $l; $i += 8) {
        $chunk = substr($data, $i, 8);
        $outlen = ceil((strlen($chunk) * 8)/6);
        $x = bin2hex($chunk);
        $w = gmp_strval(gmp_init(ltrim($x, '0'), 16), 62);
        $pad = str_pad($w, $outlen, '0', STR_PAD_LEFT);
        $outstring .= $pad;
    }
    return $outstring;
}

//Debug functions (not work with all)
function consoleLog($variable)
{
    $variable = json_encode($variable);
    $variable = str_replace("'", "\\'", $variable);
    $codeJavascript = "<script>console.log(JSON.parse('" . $variable . "'));</script>";
    echo($codeJavascript);
}

function consoleTable($variable)
{
    $variable = json_encode($variable);
    $variable = str_replace("'", "\\'", $variable);
    $codeJavascript = "<script>console.table(JSON.parse('" . $variable . "'));</script>";
    echo($codeJavascript);
}

//option
$displayId = false;

$dirs = ['./messages/inbox/',
    './messages/archived_threads/'];


$messages = [];
$char = [];
$path = [];
$formData = [];
$letters = [];

$nbInbox = 0;
$nbNull = 2 * sizeof($dirs);

foreach ($dirs as $dir) {

    $convs = scandir($dir);
    $folder = explode('/', $dir)[2];

    array_push($formData, null);
    array_push($formData, null);
    array_push($path, null);
    array_push($path, null);
    array_push($messages, null);
    array_push($messages, null);
    array_push($char, null);
    array_push($char, null);

    foreach ($convs as $conv) {

        $dirPath = $dir . $conv . '/';
        if (file_exists($dirPath . 'message_1.json')) {

            $convName = json_decode(file_get_contents($dirPath . 'message_1.json'), true)['title'];
            if ($convName !== "") {

                //push de nom, lettre et dossier
                array_push($formData, $convName);
                array_push($letters, strtoupper(substr($convName, 0, 1)));
                array_push($path, $folder);

                //scan des fichers json
                $convDir = scandir($dirPath);
                foreach ($convDir as $file) {
                    if (strpos($file, "message") === false) {
                        if (($key = array_search($file, $convDir)) !== false) unset($convDir[$key]);
                    } else if (($key = array_search($file, $convDir)) !== false) $convDir[$key] = $dirPath . $convDir[$key];
                } array_multisort(array_map('strlen', $convDir), $convDir);

                //recuperation des messages
                $messageConv = [];
                foreach ($convDir as $file) foreach ((array)json_decode(file_get_contents($file), true)['messages'] as $message) array_push($messageConv, $message);

                //ajout du nombre de messages
                array_push($messages, sizeof($messageConv));

                //ajout du nombre de caractères
                $tmp = 0;
                foreach ($messageConv as $msg) if (isset($msg['content'])) $tmp += strlen(utf8_decode($msg['content']));
                array_push($char, $tmp);
                unset($tmp);

                //supression des variables
                unset($message);
                unset($messageConv);

            } else {
                $nbNull++;
                array_push($formData, null);
                array_push($path, null);
                array_push($messages, null);
                array_push($char, null);
            }
        }
    }
    if($nbInbox === 0) $nbInbox = sizeof($formData);
    $letters = array_unique($letters);
    sort($letters);

}

//nombre total de conversations
$nbConvs = sizeof($messages) - $nbNull;

//nombre total de messages
$nbMessages = 0;
foreach ($messages as $message) if ($message !== null) $nbMessages += $message;

//nombre total de caractères
$nbChar = 0;
foreach ($char as $c) if ($c !== null) $nbChar += $c;

$moy = $nbChar/$nbMessages;

$data = [];

$data['labels'] = [];
foreach ($formData as $label) if ($label !== null) array_push($data['labels'], utf8_decode($label));

$data['messages'] = [];
foreach ($messages as $m) if ($m !== null) array_push($data['messages'], $m);


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
    <script src="script/home.js"></script>
    <style>
        .scrollbar
        {
            margin-left: 30px;
            float: left;
            height: 300px;
            width: 65px;
            overflow-y: scroll;
            margin-bottom: 25px;
            padding-right: 15px;
        }

        .scrollbar::-webkit-scrollbar-track
        {
            -webkit-box-shadow: inset 0 0 6px rgba(101, 101, 101, 0.3);
            border-radius: 10px;
        }

        .scrollbar::-webkit-scrollbar
        {
            width: 12px;
        }

        .scrollbar::-webkit-scrollbar-thumb
        {
            border-radius: 10px;
            -webkit-box-shadow: inset 0 0 6px rgba(101, 101, 101, 0.3);
            background-color: #b4b4b4;
        }
    </style>
</head>
<body>

<div id="labels" style="display: none;"><?php echo json_encode($data['labels']); ?></div>
<div id="messages" style="display: none;"><?php echo json_encode($data['messages']); ?></div>

<div class="mx-3 mt-3">
    <div class="row d-flex">
        <div class="col-4 flex-fill" style="width: 300px; flex: 0 1 auto!important;">
            <div class="list-group" id="list-tab" role="tablist">
                <a class="list-group-item list-group-item-action active" data-bs-toggle="list"
                   href="#list-home" role="tab" aria-controls="home">Tableau de bord</a>
                <a class="list-group-item list-group-item-action" id="list-profile-list" data-bs-toggle="list"
                   href="#list-conv" role="tab" aria-controls="profile">Conversations</a>
                <a class="list-group-item list-group-item-action" id="list-messages-list" data-bs-toggle="list"
                   href="#list-messages" role="tab" aria-controls="messages">Messages</a>
                <a class="list-group-item list-group-item-action" id="list-settings-list" data-bs-toggle="list"
                   href="#list-settings" role="tab" aria-controls="settings">Settings</a>
            </div>
        </div>
        <div class="col-8 flex-fill">
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" id="list-home" role="tabpanel" aria-labelledby="list-home-list">

                    <div class="mx-auto">
                        <h3 class="text-center">Tableau de bord</h3>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            Nombre de conversation: <span class="fw-bold"><?php echo number_format($nbConvs,0, '', ' ') ?></span>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            Nombre de messages: <span class="fw-bold"><?php echo number_format($nbMessages,0, '', ' ') ?></span>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            Nombre de caractères: <span class="fw-bold"><?php echo number_format($nbChar,0, '', ' ') ?></span>
                        </div>
                    </div>
                    <div class="chart-container w-100 mx-auto mt-3 scrollbar" style="height: 500px; overflow-y: scroll; position: relative;">
                        <canvas id="myChart" width="100" height="300"></canvas>
                    </div>

                </div>
                <div class="tab-pane fade" id="list-conv" role="tabpanel" aria-labelledby="list-profile-list">

                    <div class="mx-auto">
                        <h3 class="text-center">Les conversations</h3>
                        <ul class="nav nav-tabs mt-3" id="myTab" role="tablist">
                            <?php $bool = true;
                            foreach ($letters as $letter) { ?>
                                <li class="nav-item" role="presentation"><a class="nav-link <?php if ($bool) echo 'active'; $bool = false; ?>" data-bs-toggle="tab" href="#nav-<?php echo base62_encode($letter); ?>" role="tab" aria-controls="<?php echo $letter; ?>" aria-selected="true"><?php echo $letter; ?></a></li>
                            <?php } ?>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <?php $bool = true;
                            foreach ($letters as $letter) { ?>
                                <div class="tab-pane fade show <?php if ($bool) echo 'active'; $bool = false; ?>" id="nav-<?php echo base62_encode($letter); ?>" role="tabpanel" aria-labelledby="home-tab">
                                    <div class="row row-cols-1 row-cols-md-6 g-4 mt-3">
                                        <?php
                                        foreach ($formData as $name) {
                                            if ($name !== null && strtoupper(substr($name, 0, 1)) === $letter) {
                                                $id = array_search($name, $formData);
                                                if($path[array_search($name, $formData)] === 'archived_threads') $id -= $nbInbox; ?>
                                                <div class="col">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo ucfirst(utf8_decode($name)); ?> <?php if($displayId) echo ' ('. array_search($name, $formData) . ')'; ?></h5>
                                                            <p class="card-text"> Nombre de messages: <span class="fw-bold"><?php echo number_format($messages[array_search($name, $formData)],0, '', ' '); ?></span></p>
                                                            <p class="card-text"> Nombre de caractères: <span class="fw-bold"><?php echo number_format($char[array_search($name, $formData)],0, '', ' '); ?></span></p>
                                                            <a href="conversation.php?id=<?php echo $id ?>&path=<?php echo $path[array_search($name, $formData)]; ?>" class="stretched-link"></a>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php }
                                        } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" id="list-messages" role="tabpanel" aria-labelledby="list-messages-list">...
                </div>
                <div class="tab-pane fade" id="list-settings" role="tabpanel" aria-labelledby="list-settings-list">...
                </div>
            </div>
        </div>
    </div>
</div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW"
        crossorigin="anonymous"></script>
</html>
