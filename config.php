<?php
// Definições necessárias para definir um horário para limpar a pasta temps (MANTENHA A DATA NO PASSADO)
date_default_timezone_set('America/Sao_Paulo'); // Fuso horário
$timeNow = strtotime("2000-01-01 " . date('H:i:s')); // Horário no momento
$scheduledTime = strtotime("2000-01-01 15:45:00"); // Horário agendado
$setScheduler = true; // Mantenha True se quiser que o agendamento funcione




// Sessão para controlar a transferência para atendimento humano
$_SESSION['humanAgent'] = false;

// Nome da pasta para arquivos temporários
$folder = 'teste';

// Tempo de duração dos arquivos temporários
$fileDuration = '+2 minutes';

// Modelo para as respostas da IA
$model = "clarification";
// $model = "handover";

// Numero de frases para trazer da azure API e contextualizar o GPT
$amount = 10;

// Frase que o GPT vai usar para pedir esclarecimento
$phrase = "Could you please clarify your question? I need a bit more detail to help you better.";

// Frase que o GPT vai usar quando trocar para atendimento humano
$transferPhrase = "I'm transferring you to a specialized agent for further assistance. They will be with you shortly.";
