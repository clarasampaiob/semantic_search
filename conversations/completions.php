<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se a requisição é POST / Erro: Método não permitido (405)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
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






function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        echo "Erro: O arquivo .env não foi encontrado em: " . $filePath . "\n";
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignora linhas que são comentários
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        
        $parts = explode('=', $line, 2);

        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove aspas simples ou duplas 
            if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
                // Lida com caracteres de escape 
                $value = str_replace(['\\"', '\\\'', '\\\\'], ['"', "'", '\\'], $value);
            }

            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}


$envFilePath = '../.env';

loadEnv($envFilePath);

$openaiKey = $_ENV['OPENAI_API_KEY'];
$azureKey = $_ENV['AZURE_API_KEY'];



$chatPrompt = $input['messages'];
$message = end($chatPrompt);
$role = $message['role'];
$content = $message['content'];



$openaiApi = 'https://api.openai.com/v1/embeddings';
$openaiData = [
        'model' => 'text-embedding-3-large',
        'input' => $content
    ];


$azureApi = 'https://claudia-db.search.windows.net/indexes/claudia-ids-index-large/docs/search?api-version=2023-11-01';





$openaiRes = fetchApi($openaiApi, $openaiData, "POST", $openaiKey, "Authorization: Bearer ");
$embedding = $openaiRes['data'][0]['embedding'];
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




$azureRes = fetchApi($azureApi, $azureData, "POST", $azureKey, "api-key: ");

function fetchApi($api, $content, $method, $token = null, $authType = null, $json = true){
        $headers = [];
        $ch = curl_init($api);
        if ($json) $headers[] = "Content-Type: application/json";
        if ($token) $headers[] = $authType . trim($token);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_POSTFIELDS     => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FAILONERROR => false, 
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) die('CURL Error: ' . curl_error($ch));
        curl_close($ch);
        return json_decode($response, true);
    }




// fetch api com arquivo de debug
function fetchApi2($api, $content, $method, $token = null, $isJsonString = false) {
    $logFile = 'api_log_'.date('Ymd_His').'.txt';
    file_put_contents($logFile, "Iniciando requisição para: $api\n", FILE_APPEND);
    
    $headers = [];
    $ch = curl_init($api);
    
    
    if ($isJsonString) {
        $postData = $content;
        file_put_contents($logFile, "JSON pronto para envio:\n$content\n", FILE_APPEND);
    } else {
        $postData = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($logFile, "Array convertido para JSON:\n$postData\n", FILE_APPEND);
    }
    
    
    $headers[] = 'Content-Type: application/json';
    if ($token) {
        $headers[] = 'api-key: ' . trim($token);
    }
    
    file_put_contents($logFile, "Headers:\n".print_r($headers, true)."\n", FILE_APPEND);
    
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FAILONERROR => false,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen($logFile, 'a'),
        CURLOPT_TIMEOUT => 30,
    ]);
    
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    file_put_contents($logFile, "\nResposta HTTP: $httpCode\n", FILE_APPEND);
    file_put_contents($logFile, "Resposta bruta:\n$response\n", FILE_APPEND);
    
    curl_close($ch);
    
    if ($curlError) {
        file_put_contents($logFile, "Erro cURL: $curlError\n", FILE_APPEND);
        return ['error' => $curlError];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents($logFile, "Erro ao decodificar JSON: ".json_last_error_msg()."\n", FILE_APPEND);
        return ['error' => 'Invalid JSON response', 'raw' => $response];
    }
    
    return $decoded;
}




$response = [
    'azure' => $azureRes,
    'embeding' => $embedding
];




echo json_encode($response);


