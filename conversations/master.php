<?php

class Master {
    private string $clarifyPhrase;
    private string $transferPhrase;
    private string $folder;

    public function setAttributes(string $clarify, string $transfer, string $folder) {
        $this->clarifyPhrase = $clarify;
        $this->transferPhrase = $transfer;
        $this->folder = $folder;
    }

    public function askToClarify(string $gptResponse, string $helpdeskId, string $duration){
        $filePath = $this->folder . '/' . $helpdeskId . '.json';
        if($gptResponse === $this->clarifyPhrase){
            $increment = 1;
            $expirationDate = date('Y-m-d H:i:s', strtotime($duration));
            if (file_exists($filePath)) {
                $fileData = json_decode(file_get_contents($filePath), true);
                if ($fileData !== null && json_last_error() === JSON_ERROR_NONE) {
                    if (isset($fileData['expiration_date']) && isset($fileData['increment'])) { 
                        $increment = (int) $fileData['increment'] + 1;
                        $expirationDate = $fileData['expiration_date'];
                    }
                }
            } 
            file_put_contents($filePath, json_encode(['increment' => $increment, 'expiration_date' => $expirationDate]));
            return $increment > 2;
        }
        return false;
    }

    public function generateContext(string $model, mixed $apiRes){
        // Cria array apenas com o conteúdo de content
        $content = array_map(function($item) {
            return $item['content'];
        }, $apiRes);
        if($model === "clarification"){
            return "You are a Tesla support agent. You must answer strictly based on the provided context only. Do not use any external knowledge or perform any external search. If the user makes a general question about a topic that is included in the context, use the given context to respond with a short general idea about it, the user will ask more if needed (but do not force them to). And again, use only the provided context, do not add any extra information. If the information context includes forwarding to someone, ignore this part and answer only with the rest of the context, the user will ask more only if they want, so do not force it. If the information is not available in the context or If you are unsure, ask for clarification using ONLY this sentence to respond: \"" . $this->clarifyPhrase . "\"\n\nContext:\n" . implode("\n- ", $content);
        }elseif($model === "handover"){
            // Verifica se tem conteudo N2
            $itemsN2 = array_filter($apiRes, function($item) {
                return ($item['type'] ?? null) === 'N2';
            });
            $_SESSION['humanAgent'] = !empty($itemsN2);
            return "You are a Tesla support agent. You must answer strictly based on the provided context only. Do not use any external knowledge or perform any external search. If the user asks generally about a topic that is included in the context, use the given context to respond it shortly but ignore content that includes forwarding or redirectiong to someone. If the question is unclear, if the subject requires human or any specialized assistance (when it includes forwarding or redirecting to someone), if you are uncertain about the answer THEN respond ONLY with this exact phrase: \"" . $this->transferPhrase . "\"\n\nContext:\n" . implode("\n- ", $content);
        }
    }

    public function fetchApi(string $api, mixed $content, string $method, mixed $token = null, mixed $authType = null, bool $json = true){
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

    public function loadEnv(){
        $filePath = "../.env";
        if (!file_exists($filePath)) throw new RuntimeException("env file not found");
        $allowedKeys = ['OPENAI_API_KEY','AZURE_API_KEY','OPENAI_API_URL','AZURE_API_URL','OPENAI_API_CHAT'];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue; // Ignora linhas comentadas
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if (!in_array($key, $allowedKeys, true)) throw new RuntimeException("Invalid key found in env file");
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1); // Remove aspas simples
                } elseif (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1); // Remove aspas duplas
                    $value = str_replace(['\\"', '\\\'', '\\\\'], ['"', "'", '\\'], $value); // lida com caracteres escape
                }
                $value = $this->validateType("string", $value, false);
                if ($value === false) throw new RuntimeException("env files can not contain empty values");
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
        

    public function clearFolder(string $helpdeskId){
        if (!is_dir($this->folder)) mkdir($this->folder, 0755, true); // Cria a pasta se não existir
        $files = glob($this->folder . '/*.json'); // Lista todos os arquivos json da pasta
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== ($helpdeskId . '.json')) {
                $fileData = json_decode(file_get_contents($file), true);
                if ($fileData !== null && json_last_error() === JSON_ERROR_NONE) {
                    if (isset($fileData['expiration_date']) && isset($fileData['increment'])) {
                        $expirationTime = strtotime($fileData['expiration_date']);
                        $currentTime = time();
                        if ($currentTime > $expirationTime) unlink($file);
                    } else {
                        unlink($file);
                    }
                } else {
                    unlink($file);
                }
            }
        }
    }

    public function inSeconds(string $hours) {
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hours)) throw new InvalidArgumentException("Invalid Format");
        list($h, $m, $s) = explode(':', $hours);
        return ($h * 3600) + ($m * 60) + $s;
    }

    public function validateType(string $expected, mixed $value, mixed $fall){
        if ($expected === "string") return (!is_string($value) || empty(trim($value))) ? $fall : $value;
        if ($expected === "int") return (!is_int($value)) ? $fall : $value;
        if ($expected === "bool") return (!is_bool($value)) ? $fall : $value;
        return $fall;
    }

}
