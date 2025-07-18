<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "master.php";
$master = new Master();

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
} catch (RuntimeException $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}

// Recebe o conteúdo da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Verifica se os campos obrigatórios estão inclusos no conteúdo / Erro: Formato incorreto (400)
if (!$input || !isset($input['helpdeskId']) || !isset($input['projectName']) || !isset($input['messages'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid Request Body']);
  exit;
}

// Separa os dados vindos do chat em role (user) e mensagem enviada (content)
$message = end($input['messages']);
$content = $message['content'];
$role = $message['role'];

// Dados que serão enviados a API da Openai
$openaiData = [
  'model' => 'text-embedding-3-large',
  'input' => $content
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
      'k' => 10,
      'fields' => 'embeddings',
      'kind' => 'vector'
    ]
  ]
];

// Chamada a API
$azureRes = $master->fetchApi($azureApi, $azureData, "POST", $azureKey, "api-key:");

// Retorno ao usuário
$response = [
    'azure' => $azureRes,
    'embeding' => $embedding
];


echo json_encode($response);
