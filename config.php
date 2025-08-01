<?php
// SEGUIR SOMENTE OS TIPOS DE DADOS INFORMADOS

// Fuso Horário
date_default_timezone_set('America/Sao_Paulo');

// @type bool - Sessão para controlar a transferência para atendimento humano
$_SESSION['humanAgent'] = false;

// @type bool - Use true para ativar o agendamento
$setScheduler = false;

// @type string - Horário para executar a limpeza de pasta
$targetTime = '23:40:00';

// @type string - Tempo de duração dos arquivos temporários
$fileDuration = '+30 minutes';

// @type string - Nome da pasta para arquivos temporários
$folder = 'temps';

// @type string - Modelo para as respostas da IA - Opções: "clarification" ou "handover"
$model = "clarification";
// $model = "handover";

// @type int - Numero de frases para trazer da azure API e contextualizar o GPT
$amount = 10;

// @type string - Frase que o GPT vai usar para pedir esclarecimento
$phrase = "Could you please clarify your question? I need a bit more detail to help you better.";

// @type string - Frase que o GPT vai usar quando trocar para atendimento humano
$transferPhrase = "I'm transferring you to a specialized agent for further assistance. They will be with you shortly.";

// @type string - Frase exibida pro usuário final
$answer = "We had a problem. Please, try again later.";
