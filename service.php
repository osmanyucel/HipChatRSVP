<?php
function voteAndGetSummary($user,$vote,$pdo) {
  $pdo->prepare("delete from responses where person = ?")->execute([$user]);
  $pdo->prepare("insert into responses values (?,?)")->execute([$user, $vote]);
  return getSummary($pdo);
}
function setQuestionAndGetSummary($question,$pdo) {
  $pdo->prepare("delete from extraData where dataKey = ?")->execute(["question"]);
  $pdo->prepare("insert into extraData values (?,?)")->execute(["question", $question]);
  return getSummary($pdo);
}
function resetAndGetSummary($pdo) {
  $pdo->prepare("delete from responses")->execute();
  return getSummary($pdo);
}
function getSummary($pdo){
  $question = $pdo->query("SELECT dataVal FROM extraData where dataKey = 'question'")->fetchAll()[0]["dataVal"];
  $yeses = $pdo->query("SELECT person FROM responses where response = 1")->fetchAll();
  $maybes = $pdo->query("SELECT person FROM responses where response = 0")->fetchAll();
  $nos = $pdo->query("SELECT person FROM responses where response = -1")->fetchAll();
  return $question."\n".count($yeses)." Yes\n".count($maybes)." Maybe\n".count($nos)." No";
}
function getList($pdo){
  $listMessage="";
  $yeses = $pdo->query("SELECT person FROM responses where response = 1")->fetchAll();
  $maybes = $pdo->query("SELECT person FROM responses where response = 0")->fetchAll();
  $nos = $pdo->query("SELECT person FROM responses where response = -1")->fetchAll();
  foreach ($yeses as $person) $listMessage = $listMessage."Yes\t".$person["person"]."\n";
  foreach ($maybes as $person) $listMessage = $listMessage."Maybe\t".$person["person"]."\n";
  foreach ($nos as $person) $listMessage = $listMessage."No\t".$person["person"]."\n";
  $listMessage = $listMessage. count($yeses)." Yes\n".count($maybes)." Maybe\n".count($nos)." No";
  return $listMessage;
}
function createResponse($message){
  $response["color"] = "green";
  $response["message"] = $message;
  $response["notify"] = false;
  $response["message_format"] = "text";

  echo json_encode($response);
}
function connectDB($host,$db,$user,$pass){
  $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
  $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  return new PDO($dsn, $user, $pass, $opt);
}

#error_reporting(0);
    $data = json_decode( file_get_contents( 'php://input' ), true );

$host = 'localhost';
$db   = ''/*dbname goes here*/;
$user = ''/*dbUserName goes here*/;
$pass = ''/*pass goes here*/;
$pdo  = connectDB($host,$db,$user,$pass);
$command = trim($data["item"]["message"]["message"]);
if (strcasecmp($command, "/rsvp yes") == 0)       $message = voteAndGetSummary($data["item"]["message"]["from"]["mention_name"], "1",$pdo);
else if (strcasecmp($command, "/rsvp no") == 0)   $message = voteAndGetSummary($data["item"]["message"]["from"]["mention_name"], "-1",$pdo);
else if (strcasecmp($command, "/rsvp maybe") == 0)$message = voteAndGetSummary($data["item"]["message"]["from"]["mention_name"], "0",$pdo);
else if (strcasecmp($command, "/rsvp reset") == 0)$message = resetAndGetSummary($pdo);
else if (strcasecmp($command, "/rsvp list") == 0) $message = getList($pdo);
else if (strcasecmp(substr($command, 0, 10), "/rsvp setq") == 0) $message = setQuestionAndGetSummary(substr($command, 10),$pdo);
else                                              $message = "Wrong Command";

createResponse($message);
?>
