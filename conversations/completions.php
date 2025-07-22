<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../config.php";
require_once "master.php";
$master = new Master($phrase, $transferPhrase, $folder);

// Carrega as chaves e URLs das APIs / Erro: Erro no Servidor (500)
try {
  $master->loadEnv();
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

// Verifica se a requisição é POST / Erro: Método não permitido (405)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed']);
  exit;
}

// Recebe o conteúdo da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Verifica se o conteúdo recebido é JSON / Erro: Formato incorreto (400)
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON format']);
  exit;
}

// Verifica se os campos obrigatórios estão inclusos no conteúdo / Erro: Formato incorreto (400)
if (!$input || !isset($input['helpdeskId']) || !isset($input['projectName']) || !isset($input['messages'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Request Body Incomplete']);
  exit;
}

// Validação do conteúdo recebido
$message = end($input['messages']);
$role = $master->validateType("string", $message['role'], false);
$prompt = $master->validateType("string", $message['content'], false);
$helpDeskId = $master->validateType("string", $input['helpdeskId'], false);

// Presença de conteúdo inválido / Erro: Formato incorreto (400)
if ($helpDeskId === false || $prompt === false || $role === false) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid Request Body']);
  exit;
}

// Verifica se o agendamento está ativo e se os dados são válidos
if($setScheduler){
  try {
      $timeNow = $master->inSeconds(date('H:i:s'));
      $scheduledTime = $master->inSeconds($targetTime);
  } catch (Exception $e) {
      $setScheduler = false;
  }
}

// Limpa arquivos que registraram o número de pedidos de esclarecimentos feitos pelo GPT de conversas com outros helpDeskIds
if (!$setScheduler || ($setScheduler && $scheduledTime < $timeNow)) $master->clearFolder($helpDeskId);

// Dados que serão enviados a API da Openai
$openaiData = [
  'model' => 'text-embedding-3-large',
  'input' => $prompt
];

// Chamada a API
$openaiRes = $master->fetchApi($openaiApi, $openaiData, "POST", $openaiKey, "Authorization: Bearer");

// Verifica se não foi possível retornar o dado esperado / Erro: Erro no Servidor (500)
if (!isset($openaiRes['data'][0]['embedding'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Request to Openai API Failed']);
  exit;
}

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

// Verifica se não foi possível retornar o dado esperado / Erro: Erro no Servidor (500)
if (!isset($azureRes['value'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Request to Azure API Failed']);
  exit;
}

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

// Verifica se não foi possível retornar o dado esperado / Erro: Erro no Servidor (500)
if (!isset($gptRes['choices'][0]['message']['content'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Request to GPT API Failed']);
  exit;
}

// Atualiza a resposta para o usuário
$answer = $gptRes['choices'][0]['message']['content'];

// Verifica se precisa de agente humano no atendimento
 if($model === "clarification"){
  $_SESSION['humanAgent'] = $master->askToClarify($gptRes['choices'][0]['message']['content'], $helpDeskId, $fileDuration);
  if($_SESSION['humanAgent']) $answer = $transferPhrase;
}

// Resposta para o usuário
$response = [
  "messages" => [
    [
      "role" => "USER",
      "content" => $prompt
    ],
    [
      "role" => "AGENT",
      "content" => $answer
    ]
  ],
  "handoverToHumanNeeded" => $_SESSION['humanAgent'],
  "sectionsRetrieved" => $allResults
];

echo json_encode($response);
