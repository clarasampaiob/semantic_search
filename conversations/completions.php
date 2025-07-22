<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../config.php";
require_once "master.php";
$master = new Master($phrase, $transferPhrase, $folder);


// Verifica se a requisição é POST / Erro: Método não permitido (405)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed']);
  exit;
}

// Carrega as chaves e URLs das APIs // Erro: Erro no Servidor (500)
try {
  Master::loadEnv();
  $openaiKey = $_ENV['OPENAI_API_KEY'];
  $azureKey = $_ENV['AZURE_API_KEY'];
  $openaiApi = $_ENV['OPENAI_API_URL'];
  $azureApi = $_ENV['AZURE_API_URL'];
  $openaiChat = $_ENV['OPENAI_API_CHAT'];
} catch (RuntimeException $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}

// Recebe o conteúdo da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Verifica se o conteúdo recebido era JSON / Erro: Formato incorreto (400)
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  exit('Invalid JSON format');
}

// Verifica se os campos obrigatórios estão inclusos no conteúdo / Erro: Formato incorreto (400)
if (!$input || !isset($input['helpdeskId']) || !isset($input['projectName']) || !isset($input['messages'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid Request Body']);
  exit;
}




if($setScheduler){
  try {
      $timeNow = $master->inSeconds(date('H:i:s'));
      $scheduledTime = $master->inSeconds($targetTime);
  } catch (Exception $e) {
      $setScheduler = false;
  }
}

// Limpa arquivos que registraram o número de pedidos de esclarecimentos feitos pelo GPT de conversas com outros helpDeskIds
if (!$setScheduler || ($setScheduler && $scheduledTime < $timeNow)) $master->clearFolder($input['helpdeskId']);






// Separa os dados vindos do chat em role (user) e mensagem enviada (content)
$message = end($input['messages']);
$prompt = $message['content'];

// Dados que serão enviados a API da Openai
$openaiData = [
  'model' => 'text-embedding-3-large',
  'input' => $prompt
];

// Chamada a API
$openaiRes = $master->fetchApi($openaiApi, $openaiData, "POST", $openaiKey, "Authorization: Bearer");

// Armazena o resultado do RAG (Retrieval Augmented Generation)
$embedding = $openaiRes['data'][0]['embedding'];

// Dados que serão enviados a API da Azure
$azureData = [
  'count' => true,
  'select' => 'content, type',
  'top' => 10,
  'filter' => "projectName eq 'tesla_motors'",
  'vectorQueries' => [
    (object)[
      'vector' => $embedding,
      'k' => $amount,
      'fields' => 'embeddings',
      'kind' => 'vector'
    ]
  ]
];

// Chamada a API
$azureRes = $master->fetchApi($azureApi, $azureData, "POST", $azureKey, "api-key:");

// Guarda todos os score e content recebidos
$allResults = array_map(function($item) {
  return [
    'score' => $item['@search.score'],
    'content' => $item['content']
  ];
}, $azureRes['value']);

// Gerar contexto para Chat GPT
$context = $master->generateContext($model, $azureRes['value']);



// Dados que serão enviados ao Chat GPT
$gptData = [
  "model" => "gpt-4o",
  "messages" => [
    [
      "role" => "system",
      "content" => $context
    ],
    [
      "role" => "user",
      "content" => $prompt
    ]
  ]
];

// Chamada a API
$gptRes = $master->fetchApi($openaiChat, $gptData, "POST", $openaiKey, "Authorization: Bearer");



// Verifica se precisa de agente humano no atendimento
 if($model === "clarification"){
  $_SESSION['humanAgent'] = $master->askToClarify($gptRes['choices'][0]['message']['content'], $input['helpdeskId'], $fileDuration);
  if($_SESSION['humanAgent']) $gptRes['choices'][0]['message']['content'] = $transferPhrase;
}

 



// Retorno ao usuário
$response = [
    'humanAgent' => $_SESSION['humanAgent'],
    // 'teste' => $teste,
    'contentN1' => $context,
    // 'gpt' => $gptRes,
    'reply' => $gptRes['choices'][0]['message']['content'],
    'azure' => $azureRes,
    // 'embeding' => $azureRes['value']
];



// $response = [
//   "messages" => [
//     [
//       "role" => "USER",
//       "content" => $prompt
//     ],
//     [
//       "role" => "AGENT",
//       "content" => $gptRes['choices'][0]['message']['content']
//     ]
//   ],
//   "handoverToHumanNeeded" => $_SESSION['humanAgent'],
//   "sectionsRetrieved" => $allResults
// ];


echo json_encode($response);
