<?php

class Master {

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

}
