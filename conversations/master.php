<?php

class Master {
    private $clarifyPhrase;
    private $transferPhrase;
    private $folder;

    public function __construct($clarify, $transfer, $folder) {
        $this->clarifyPhrase = $clarify;
        $this->transferPhrase = $transfer;
        $this->folder = $folder; 
    }

    public function askToClarify($gptResponse, $helpdeskId, $duration){
        $filePath = $this->folder . '/' . $helpdeskId . '.json';
        if($gptResponse === $this->clarifyPhrase){ 
            if (file_exists($filePath)) {
                $fileData = json_decode(file_get_contents($filePath), true);
                $increment = (int) $fileData['increment'] + 1;
                $expirationDate = $fileData['expiration_date'];
            } else {
                $increment = 1;
                $expirationDate = date('Y-m-d H:i:s', strtotime($duration)); 
            }
            file_put_contents($filePath, json_encode(['increment' => $increment, 'expiration_date' => $expirationDate]));
            return $increment > 2;
        }
        return false;
    }

    public function generateContext($model, $apiRes){
        // Cria array apenas com o conteúdo de content
        $content = array_map(function($item) {
            return $item['content'];
        }, $apiRes);
        if($model === "clarification"){
            return "You are a Tesla support agent. You must answer strictly based on the provided context only. Do not use any external knowledge or perform any external search. If the information is not available in the context or If you are unsure, ask for clarification using this sentence: \"" . $this->clarifyPhrase . "\"\n\nContext:\n" . implode("\n- ", $content);
        }elseif($model === "handover"){
            // Verifica se tem conteudo N2
            $itemsN2 = array_filter($apiRes, function($item) {
                return ($item['type'] ?? null) === 'N2';
            });
            $_SESSION['humanAgent'] = !empty($itemsN2);
            return "You are a Tesla support agent. You must answer strictly based on the provided context only. Do not use any external knowledge or perform any external search. If the question is unclear, if the subject requires human or any specialized assistance (when it includes forwarding or redirecting to someone), if you are uncertain about the answer THEN respond ONLY with this exact phrase: \"" . $this->transferPhrase . "\"\n\nContext:\n" . implode("\n- ", $content);
        }
    }

    public function fetchApi($api, $content, $method, $token = null, $authType = null, $json = true){
        $headers = [];
        $ch = curl_init($api);
        if ($json) $headers[] = "Content-Type: application/json";
        if ($token) $headers[] = $authType . " " . trim($token);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_POSTFIELDS     => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FAILONERROR    => false,
        ]);
        $response = curl_errno($ch) ? curl_error($ch) : curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public static function loadEnv(){
        $filePath = "../.env";
        if (!file_exists($filePath)) return;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue; // Ignora linhas comentadas
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1); // Remove aspas simples
                } elseif (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1); // Remove aspas duplas
                    $value = str_replace(['\\"', '\\\'', '\\\\'], ['"', "'", '\\'], $value); // lida com caracteres escape
                }
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    public function clearFolder($helpdeskId){
        if (!is_dir($this->folder)) {
            // Cria a pasta se não existir
            mkdir($this->folder, 0755, true);
        } else {
            // Lista todos os arquivos da pasta
            $files = glob($this->folder . '/*'); 
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== ($helpdeskId . '.json')){
                    $fileData = json_decode(file_get_contents($file), true);
                    $expirationTime = strtotime($fileData['expiration_date']);
                    $currentTime = time();
                    if ($currentTime > $expirationTime) unlink($file);
                } 
            }
        }
    }

}
