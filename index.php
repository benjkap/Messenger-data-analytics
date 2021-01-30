<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('memory_limit', '1024M');

function base62_encode($data)
{
    $outstring = '';
    $l = strlen($data);
    for ($i = 0; $i < $l; $i += 8) {
        $chunk = substr($data, $i, 8);
        $outlen = ceil((strlen($chunk) * 8) / 6);
        $x = bin2hex($chunk);
        $w = gmp_strval(gmp_init(ltrim($x, '0'), 16), 62);
        $pad = str_pad($w, $outlen, '0', STR_PAD_LEFT);
        $outstring .= $pad;
    }
    return $outstring;
}

function doublemax($array)
{
    $maxvalue = max($array);
    foreach ($array as $key => $value) if ($value == $maxvalue) $maxindex = $key;
    return array("v" => $maxvalue, "i" => $maxindex);
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

//option
$displayId = false;

$dirs = ['./messages/inbox/',
    './messages/archived_threads/'];


$messages = [];
$char = [];
$path = [];
$formData = [];
$letters = [];
$users = [];

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

                //verification nom pas présent et changement de nom si necéssaire
                $tmp = false;
                $tmpId = 0;
                $originalConvName = $convName;
                while (!$tmp) {
                    $tmpId++;
                    if (!array_search($convName, $formData)) $tmp = true;
                    else $convName = $originalConvName . ' (' . $tmpId . ')';
                }
                unset($tmp);
                unset($tmpId);
                unset($originalConvName);

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
                }
                array_multisort(array_map('strlen', $convDir), $convDir);

                //recuperation des messages
                $messageConv = [];
                $userConv = [];
                foreach ($convDir as $file) {
                    $json = (array)json_decode(file_get_contents($file), true);
                    foreach ($json['messages'] as $message) array_push($messageConv, $message);
                    foreach ($json['participants'] as $participant) {
                        if (isset($users[utf8_decode($participant['name'])])) $users[utf8_decode($participant['name'])]++;
                        else $users[utf8_decode($participant['name'])] = 1;
                    }
                }

                //ajout du nombre de messages
                array_push($messages, $messageConv);

                //ajout du nombre de caractères
                $tmp = 0;
                foreach ($messageConv as $msg) if ($msg['type'] === 'Generic' && isset($msg['content'])) $tmp += strlen(utf8_decode($msg['content']));
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
    if ($nbInbox === 0) $nbInbox = sizeof($formData);
    $letters = array_unique($letters);
    sort($letters);

}

$username = doublemax($users)['i'];

$data = [];

$data['labels'] = [];
foreach ($formData as $label) if ($label !== null) array_push($data['labels'], utf8_decode($label));

$data['labelsWithoutEnc'] = [];
foreach ($formData as $label) if ($label !== null) array_push($data['labelsWithoutEnc'], $label);

$data['path'] = [];
foreach ($path as $p) if ($p !== null) array_push($data['path'], $p);

$data['key'] = [];
foreach ($formData as $key => $label) if ($label !== null) array_push($data['key'], $key);

$data['id'] = [];
foreach ($formData as $key => $label) if ($label !== null) array_push($data['id'], ($path[$key] === 'archived_threads') ? $key - $nbInbox : $key);

$data['messagesAll'] = [];
foreach ($messages as $m) if ($m !== null) array_push($data['messagesAll'], sizeof($m));

$data['messagesMe'] = [];
foreach ($messages as $message) if ($message !== null) {
    $counter = 0;
    foreach ($message as $m) if ($m['sender_name'] === $username) $counter++;
    array_push($data['messagesMe'], $counter);
}

$data['messagesOther'] = [];
foreach ($messages as $message) if ($message !== null) {
    $counter = 0;
    foreach ($message as $m) if ($m['sender_name'] !== $username) $counter++;
    array_push($data['messagesOther'], $counter);
}

$data['charsAll'] = [];
foreach ($messages as $message) if ($message !== null) {
    $counter = 0;
    foreach ($message as $m) if ($m['type'] === 'Generic' && isset($m['content'])) $counter += strlen(utf8_decode($m['content']));
    array_push($data['charsAll'], $counter);
}

$data['charsMe'] = [];
foreach ($messages as $message) if ($message !== null) {
    $counter = 0;
    foreach ($message as $m) if ($m['type'] === 'Generic' && isset($m['content']) && $m['sender_name'] === $username) $counter += strlen(utf8_decode($m['content']));
    array_push($data['charsMe'], $counter);
}

$data['charsOther'] = [];
foreach ($messages as $message) if ($message !== null) {
    $counter = 0;
    foreach ($message as $m) if ($m['type'] === 'Generic' && isset($m['content']) && $m['sender_name'] !== $username) $counter += strlen(utf8_decode($m['content']));
    array_push($data['charsOther'], $counter);
}

consoleLog($data);

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
        .scrollbar {
            margin-left: 30px;
            float: left;
            height: 300px;
            width: 65px;
            overflow-y: scroll;
            margin-bottom: 25px;
            padding-right: 15px;
        }

        .btn-outline-primary:hover {
            color: #0d6efd;
            border-color: #0d6efd;
            background-color: white;
        }

        .scrollbar::-webkit-scrollbar-track {
            -webkit-box-shadow: inset 0 0 6px rgba(101, 101, 101, 0.3);
            border-radius: 10px;
        }

        .scrollbar::-webkit-scrollbar {
            width: 12px;
        }

        .scrollbar::-webkit-scrollbar-thumb {
            border-radius: 10px;
            -webkit-box-shadow: inset 0 0 6px rgba(101, 101, 101, 0.3);
            background-color: #b4b4b4;
        }
    </style>
</head>
<body>

<div id="labels" style="display: none;"><?php echo json_encode($data['labels']); ?></div>
<div id="messagesAll" style="display: none;"><?php echo json_encode($data['messagesAll']); ?></div>
<div id="messagesMe" style="display: none;"><?php echo json_encode($data['messagesMe']); ?></div>
<div id="messagesOther" style="display: none;"><?php echo json_encode($data['messagesOther']); ?></div>
<div id="caractèresAll" style="display: none;"><?php echo json_encode($data['charsAll']); ?></div>
<div id="caractèresMe" style="display: none;"><?php echo json_encode($data['charsMe']); ?></div>
<div id="caractèresOther" style="display: none;"><?php echo json_encode($data['charsOther']); ?></div>

<div class="mx-3 mt-3">
    <div class="row d-flex">
        <div class="col-4 flex-fill" style="width: 300px; flex: 0 1 auto!important;">
            <div class="list-group" id="list-tab" role="tablist">
                <a class="list-group-item list-group-item-action active" data-bs-toggle="list"
                   href="#list-home" role="tab" aria-controls="home">Tableau de bord</a>
                <a class="list-group-item list-group-item-action" id="list-profile-list" data-bs-toggle="list"
                   href="#list-conv" role="tab" aria-controls="profile">Conversations</a>
            </div>
            <div class="btn-group w-100 my-3" role="group" aria-label="Basic checkbox toggle button group">
                <input type="checkbox" class="btn-check" id="meSwitch1" autocomplete="off" checked>
                <label class="btn btn-outline-primary" for="meSwitch1">Moi</label>
                <input type="checkbox" class="btn-check" id="otherSwitch1" autocomplete="off" checked>
                <label class="btn btn-outline-primary" for="otherSwitch1">Autres</label>
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
                            Nombre de conversation: <span id="nbConv" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            Nombre de messages: <span id="nbMessages" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            Nombre de caractères: <span id="nbChar" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="mx-auto d-flex justify-content-center mt-3">
                        <button type="button" style="background-color: rgb(54, 162, 235); border-color: rgb(48,152,216)"
                                class="btn btn-primary mx-3" id="messagesButton1" disabled>Messages
                        </button>
                        <button type="button" style="background-color: rgb(255, 159, 64); border-color: rgb(217,141,51)"
                                class="btn btn-primary mx-3" id="charsButton1">Caractères
                        </button>
                    </div>
                    <div class="chart-container w-100 mx-auto mt-3 scrollbar"
                         style="height: 500px; overflow-y: scroll; position: relative;">
                        <canvas id="myChart" width="100" height="300"></canvas>
                    </div>

                </div>
                <div class="tab-pane fade" id="list-conv" role="tabpanel" aria-labelledby="list-profile-list">

                    <div class="mx-auto">
                        <h3 class="text-center">Les conversations</h3>
                        <div class="d-flex">
                            <ul class="nav nav-tabs mt-3 flex-grow-1" id="myTab" role="tablist">
                                <?php $bool = true;
                                foreach ($letters as $letter) { ?>
                                    <li class="nav-item" role="presentation"><a
                                                class="nav-link <?php if ($bool) echo 'active';
                                                $bool = false; ?>" data-bs-toggle="tab"
                                                href="#nav-<?php echo base62_encode($letter); ?>" role="tab"
                                                aria-controls="<?php echo $letter; ?>"
                                                aria-selected="true"><?php echo $letter; ?></a></li>
                                <?php } ?>
                            </ul>
                            <button type="button" style="width: 150px" class="btn btn-primary mx-3 mt-3" id="sortButton"
                                    data="messages">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                     class="bi bi-sort-numeric-down mx-auto" viewBox="0 0 16 16">
                                    <path d="M12.438 1.668V7H11.39V2.684h-.051l-1.211.859v-.969l1.262-.906h1.046z"/>
                                    <path fill-rule="evenodd"
                                          d="M11.36 14.098c-1.137 0-1.708-.657-1.762-1.278h1.004c.058.223.343.45.773.45.824 0 1.164-.829 1.133-1.856h-.059c-.148.39-.57.742-1.261.742-.91 0-1.72-.613-1.72-1.758 0-1.148.848-1.835 1.973-1.835 1.09 0 2.063.636 2.063 2.687 0 1.867-.723 2.848-2.145 2.848zm.062-2.735c.504 0 .933-.336.933-.972 0-.633-.398-1.008-.94-1.008-.52 0-.927.375-.927 1 0 .64.418.98.934.98z"/>
                                    <path d="M4.5 2.5a.5.5 0 0 0-1 0v9.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L4.5 12.293V2.5z"/>
                                </svg>
                                <span id="innerText">Par messages</span>
                            </button>
                        </div>
                        <div class="tab-content" id="myTabContent">
                            <?php $bool = true;
                            foreach ($letters as $letter) { ?>
                                <div class="tab-pane fade show <?php if ($bool) echo 'active';
                                $bool = false; ?>" id="nav-<?php echo base62_encode($letter); ?>" role="tabpanel">
                                    <div class="convs-pages row row-cols-1 row-cols-md-5 g-4 my-3">
                                        <?php
                                        foreach ($data['labels'] as $key => $name) {
                                            if (strtoupper(substr($data['labelsWithoutEnc'][$key], 0, 1)) === $letter) { ?>
                                                <div class="col conversation"
                                                     data_id="<?php echo $key; ?>"
                                                     data_char="<?php echo $data['charsAll'][$key] ; ?>"
                                                     data_msg="<?php echo $data['messagesAll'][$key]  ?>">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo ucfirst($name) ?><?php if ($displayId) echo ' (' . $data['id'][$key] . ',' . $data['path'][$key] . ')'; ?></h5>
                                                            <p class="card-text"> Nombre de messages: <span class="fw-bold msg"></span></p>
                                                            <p class="card-text"> Nombre de caractères: <span class="fw-bold char"></span></p>
                                                            <a href="conversation.php?id=<?php echo $data['id'][$key] ?>&path=<?php echo $data['path'][$key] ?>" class="stretched-link"></a>
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
