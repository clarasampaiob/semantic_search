# Como funciona?

Esse script contém uma API que retorna respostas de chatbot baseada em um conteúdo específico de uma determinada empresa. A ideia é que o usuário faça perguntas relacionadas à empresa e estas sejam respondidas pela inteligência artificial, no caso o GPT, que deve usar apenas o contexto fornecido pela empresa. Existem 2 modos disponíveis para o fluxo de respostas, que são o clarification e o handover.

No modo clarification, o GPT deve direcionar o usuário para o atendimento humano caso ele tenha pedido esclarecimentos ao usuário mais de 2 vezes. No modo handover, isso acontecerá se a pergunta do usuário estiver relacionada ao contexto classificado como N2, o qual exige que um agente especializado responda ao invés do GPT.

O script vem por padrão configurado para o modo clarification, mas pode ser alterado para o modo handover. Para isso, basta acessar o arquivo config.php e alterar a flag $model para handover.


# Especificações

A API foi desenvolvida em PHP e não contém nenhum outro framework para backend. O script também conta com uma página web onde é possível simular a visualização do cliente do chat. A página web foi desenvlvida em HTML, CSS, JavaScript e Bootstrap.


# Instalação

A instalação do ambiente e as dependências é feita através do Docker. Uma vez que os arquivos desse repositório estejam baixados, basta instalar com essa linha de comando:

```bash
docker-compose up --build
```

Na segunda execução, em caso de não possuir o Docker Desktop instalado, execute essa linha de comando:

```bash
docker-compose up
```

Para acessar a API pelo Postman, use:

```url
http://localhost:3000/conversations/completions
```

Para acessar a API pelo navegador, use:

```url
http://localhost:3000
```

ATENÇÃO: Antes de testar o sistema, leia as configurações abaixo!


## Configurações Básicas

É necessário criar um arquivo .env na raíz do projeto com as chaves e as URLs das APIs. O arquivo deve conter o seguinte conteúdo:

```env
OPENAI_API_KEY = "sua-chave"
AZURE_API_KEY = "sua-chave"
OPENAI_API_URL = "url-open-ai"
OPENAI_API_CHAT = "url-gpt-chat"
AZURE_API_URL = "url-azure"
```

Na pasta conversations crie uma pasta chamada temps. Essa pasta servirá para armazenar arquivos temporários.


## Configurações Personalizáveis

Na raiz do projeto, use o arquivo config.php para alterar algumas configurações. O código abaixo contém uma breve explicação de cada parâmetro.

```php
// Fuso Horário
date_default_timezone_set('America/Sao_Paulo');

// @type string - Horário para executar a limpeza de pasta
$targetTime = '23:40:00';

// @type bool - Use true para ativar o agendamento
$setScheduler = false;

// @type bool - Sessão para controlar a transferência para atendimento humano
$_SESSION['humanAgent'] = false;

// @type string - Nome da pasta para arquivos temporários
$folder = 'temps';

// @type string - Tempo de duração dos arquivos temporários
$fileDuration = '+30 minutes';

// @type string - Modelo para as respostas da IA - Opções: "clarification" ou "handover"
$model = "clarification";

// @type int - Numero de frases para trazer da azure API e contextualizar o GPT
$amount = 10;

// @type string - Frase que o GPT vai usar para pedir esclarecimento
$phrase = "Could you please clarify your question? I need a bit more detail to help you better.";

// @type string - Frase que o GPT vai usar quando trocar para atendimento humano
$transferPhrase = "I'm transferring you to a specialized agent for further assistance. They will be with you shortly.";

// @type string - Frase exibida pro usuário final
$answer = "We had a problem. Please, try again later.";

```

ATENÇÃO: Se alterar o nome da pasta temporária, não se esqueça de alterar o nome da pasta criada também!


## Funcionamento do modo CLARIFICATION

Uma vez que nesse modo o GPT só pode pedir esclarecimentos 2 vezes no máximo, a contagem de vezes em que isso ocorre é incrementada em um arquivo .json que é salvo com o ID do help desk na pasta temps. Nesse arquivo também consta a data e horário em que o pedido de esclarecimento foi realizado com intuito de gerenciar o prazo que esse arquivo deve permanecer no servidor. Levando em consideração que um atendimento de help desk não costuma ser muito prolongado, por padrão, o prazo para esses arquivos temporários serem excluídos é de 30 minutos (podendo ser alterado em config.php).

Por padrão, a função de limpeza da pasta temps será chamada a cada requisição à API. Entretanto, caso esse não seja o fluxo desejado, basta alterar a flag $setScheduler para true. Essa flag é responsável por ativar um agendamento de limpeza da pasta temps, o qual será executado no horário definido em $targetTime.

É recomendado que o horário seja definido pelo menos a partir das 23:00 porque o sistema verifica se o horário atual é maior que o horário de agendamento, então, se o agendamento estiver definido para as 14:00, qualquer requisição após esse horário irá executar a limpeza da pasta (no caso só arquivos com cadastro feito a mais de 30 minutos da hora atual).

Para conteúdo classificado como N2, o GPT irá responder dentro do contexto fornecido mas não irá redirecionar para atendimento humano. O GPT foi instruído para identificar palavras como "forward" e "redirect" para entender que se trata desse caso.

Exemplo de um arquivo temporário:

```json
{"increment":1,"expiration_date":"2025-07-22 15:35:57"}
```

## Funcionamento do modo HANDOVER

Neste modo, o GPT irá verificar se o conteúdo enviado pelo usuário requer atendimento humano. O GPT foi instruído para realizar esse redirecionamento se o contexto utilizado para responder a pergunta específica do usuário conter palavras chaves como "forward" ou "redirect".




