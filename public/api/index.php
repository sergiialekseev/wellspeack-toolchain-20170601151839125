<?php

require 'Slim/Slim.php';

$app = new Slim();
$app->response()->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT');
$app->response()->header('Access-Control-Allow-Origin', '*');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && (
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST' ||
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'DELETE' ||
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'PUT' )) {
             header('Access-Control-Allow-Origin: *');
             header("Access-Control-Allow-Credentials: true");
             header('Access-Control-Allow-Headers: X-Requested-With');
             header('Access-Control-Allow-Headers: Content-Type');
             header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
             header('Access-Control-Max-Age: 86400');
      }
  exit;
}


$app->get('/authorization', 'getWatsonsToken');
$app->get('/rules', 'getRules');
$app->get('/ruleTypes', 'getRuleTypes');
$app->get('/rulesExamples/:languageId', 'getRulesExamples');
$app->get('/rulePhrase/:ruleId', 'getRulePhrase');
$app->post('/suggestions', 'addSuggestion');

$app->run();

function getWatsonsToken(){
    $tokens = [];

    $urlSTT = "https://stream.watsonplatform.net/authorization/api/v1/token\?url=https://stream.watsonplatform.net/speech-to-text/api/v1/";
    $urlTTS = "https://stream.watsonplatform.net/authorization/api/v1/token\?url=https://stream.watsonplatform.net/text-to-speech/api/v1/";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $urlSTT);
    curl_setopt($ch, CURLOPT_USERPWD, "563ddaf9-7fa2-4359-ab31-cc67a1c6f7ef:aziPJxHUItbD");

    $tokens[0] = curl_exec($ch);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $urlTTS);
    curl_setopt($ch, CURLOPT_USERPWD, "f2ef0e4b-88b4-4481-985d-3158499f7b30:KrYxhtn15tN0");

    $tokens[1] = curl_exec($ch);

    curl_close($ch);
    echo json_encode($tokens);
}

function getRules() {
    $sql = "select * FROM Rules WHERE active=1";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $rules = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($rules, JSON_NUMERIC_CHECK);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function getRuleTypes() {
    $sql = "select * FROM RuleTypes";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $ruleTypes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        echo json_encode($ruleTypes, JSON_NUMERIC_CHECK);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function getRulesExamples($languageId) {
    $sql = "select * FROM RulesShortExamples WHERE languageId=:languageId";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("languageId", $languageId);
        $stmt->execute();
        $examples = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($examples, JSON_NUMERIC_CHECK);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function getRulePhrase($ruleId) {
    $phrase = (object)[];
    try {
        $db = getConnection();
        $stmt = $db->prepare("select * FROM RulesPhrases WHERE ruleId=:ruleId");
        $stmt->bindParam("ruleId", $ruleId);
        $stmt->execute();
        $rulePhrasesArray = $stmt->fetchAll(PDO::FETCH_OBJ);

        $random = array_rand($rulePhrasesArray);
        $randomPhrase = $rulePhrasesArray[$random];

        $stmt = $db->prepare("select * FROM Phrases WHERE Id=:randomPhraseId");
        $stmt->bindParam("randomPhraseId", $randomPhrase->PhraseId);
        $stmt->execute();
        $phrase = $stmt->fetch(PDO::FETCH_OBJ);

        $stmt = $db->prepare("select * FROM Translations WHERE Id=:randomTranslationId");
        $stmt->bindParam("randomTranslationId", $randomPhrase->TranslationId);
        $stmt->execute();
        $phrase->Translation = $stmt->fetch(PDO::FETCH_OBJ)->Translation;

        $db = null;
        echo json_encode($phrase, JSON_NUMERIC_CHECK);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function addSuggestion() {
    $request = Slim::getInstance()->request();
    $suggestion = json_decode($request->getBody());
    $sql = "INSERT INTO Suggestions (UserId,CreatedOn,SuggestionText,SuggestionType) VALUES (:userId,:createdOn,:text,:type)";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("userId", $suggestion->userId);
        $stmt->bindParam("createdOn", $suggestion->createdOn);
        $stmt->bindParam("text", $suggestion->text);
        $stmt->bindParam("type", $suggestion->type);
        $stmt->execute();
        $suggestion->id = $db->lastInsertId();
        $db = null;
        echo '{ "suggestion":'. json_encode($suggestion) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function getConnection() {
 $dbhost="us-cdbr-iron-east-03.cleardb.net";
 $dbuser="b2c82313f7ed4b";
 $dbpass="69a241e0";
 $dbname="ad_4175a4277b4b4fb";
 $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);

 $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 return $dbh;
}



?>
