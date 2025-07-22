<?php
// Definir um horário para limpar a pasta temporária
date_default_timezone_set('America/Sao_Paulo'); // Fuso horário
$targetTime = '09:40:00'; // Horário agendado
$setScheduler = true; // true para o agendamento funcionar

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
