## Objetivo

Esse script contém uma API que retorna respostas de chatbot baseada em um conteúdo específico de uma determinada empresa. A ideia é que o usuário faça perguntas relacionadas à empresa e estas sejam respondidas pela inteligência artificial, no caso o GPT, que deve usar apenas o contexto fornecido pela empresa. Existem 2 modos disponíveis para o fluxo de respostas, que são o clarification e o handover.

No modo clarification, o GPT deve direcionar o usuário para o atendimento humano caso ele tenha pedido esclarecimentos ao usuário mais de 2 vezes. No modo handover, isso acontecerá se a pergunta do usuário estiver relacionada ao contexto classificado como N2, o qual exige que um agente especializado responda ao invés do GPT.

O script vem por padrão configurado para o modo clarification, mas pode ser alterado para o modo handover. Para isso, basta acessar o arquivo config.php e alterar a flag $model para handover.


## Especificações

A API foi desenvolvida em PHP e não contém nenhum outro framework para backend. O script também conta com uma página web onde é possível simular a visualização do cliente do chat. A página web foi desenvlvida em HTML, CSS, JavaScript e Bootstrap.


## Instalação

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
OPENAI_API_KEY = "substitua-pela-sua-chave"
AZURE_API_KEY = "substitua-pela-sua-chave"
OPENAI_API_URL = "substitua-pela-url-open-ai"
OPENAI_API_CHAT = "substitua-pela-url-gpt-chat"
AZURE_API_URL = "substitua-pela-url-azure"
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

## Funcionamento do modo Clarification

Uma vez que nesse modo o GPT só pode pedir esclarecimentos 2 vezes no máximo, a contagem de vezes em que isso ocorre é incrementada em um arquivo .json que é salvo com o ID do help desk na pasta temps. Nesse arquivo também consta a data e horário em que o pedido de esclarecimento foi realizado com intuito de gerenciar o prazo que esse arquivo deve permanecer no servidor. Levando em consideração que um atendimento de help desk não costuma ser muito prolongado, por padrão, o prazo para esses arquivos temporários serem excluídos é de 30 minutos (podendo ser alterado em config.php).

Por padrão, a função de limpeza da pasta temps será chamada a cada requisição à API. Entretanto, caso esse não seja o fluxo desejado, basta alterar a flag $setScheduler para true. Essa flag é responsável por ativar um agendamento de limpeza da pasta temps, o qual será executado no horário definido em $targetTime.

É recomendado que o horário seja definido pelo menos a partir das 23:00 porque o sistema verifica se o horário atual é maior que o horário de agendamento, então, se o agendamento estiver definido para as 14:00, qualquer requisição após esse horário irá executar a limpeza da pasta (no caso só arquivos com cadastro feito a mais de 30 minutos da hora atual).

Para conteúdo classificado como N2, o GPT irá responder dentro do contexto fornecido mas não irá redirecionar para atendimento humano. O GPT foi instruído para identificar palavras como "forward" e "redirect" para entender que se trata desse caso.

Exemplo de um arquivo temporário:

```json
{"increment":1,"expiration_date":"2025-07-22 15:35:57"}
```


## Funcionamento do modo Handover

Neste modo, o GPT irá verificar se o conteúdo enviado pelo usuário requer atendimento humano. O GPT foi instruído para realizar esse redirecionamento se o contexto utilizado para responder a pergunta específica do usuário conter palavras chaves como "forward" ou "redirect".


## Testes Unitários

Iremos utilizar a empresa TESLA como mock de exemplo. Para receber uma resposta presente nos contextos fornecidos, use essa entrada no Postman. Caso esteja utilizando a página web, basta apenas digitar na caixa de texto a mesma pergunta.

```json
{
    "helpdeskId": "444444",           
    "projectName": "Tesla Support", 
    "messages": [{                  
        "role": "USER",
        "content": "How long does a Tesla battery last before it needs to be replaced?"
    }]
}
```
Tanto para o modo clarification como handover, o resultado será algo assim:

```json
{
    "messages": [
        {
            "role": "USER",
            "content": "How long does a Tesla battery last before it needs to be replaced?"
        },
        {
            "role": "AGENT",
            "content": "Tesla batteries are designed to last many years, and the vehicle will notify you if maintenance is needed. Additionally, Tesla’s battery warranty typically lasts for 8 years or about 150,000 miles, depending on the model."
        }
    ],
    "handoverToHumanNeeded": false,
    "sectionsRetrieved": [
        {
            "score": 0.6085594,
            "content": "How do I know if my Tesla battery needs replacement? Tesla batteries are designed to last many years; the vehicle will notify you if maintenance is needed."
        },
        {
            "score": 0.578509,
            "content": "What is Tesla's battery warranty? Tesla’s battery warranty typically lasts for 8 years or about 150,000 miles, depending on the model."
        },
        {
            "score": 0.4341344,
            "content": "How does the car's heating system work in winter? Tesla has a pre-heating system that can be activated through the app to warm up the interior and battery."
        }
    ]
}
```

Para informações não presentes no contexto, utilize essa entrada:

```json
{
    "helpdeskId": "33333",           
    "projectName": "Tesla Support", 
    "messages": [{                  
        "role": "USER",
        "content": "Cookies"
    }]
}
```

No modo clarification, o GPT irá pedir por esclarecimentos, uma vez que ele não tem respostas para esse assunto e também não pode pesquisar em fontes externas. O resultado será algo assim:

```json
{
    "messages": [
        {
            "role": "USER",
            "content": "Cookies"
        },
        {
            "role": "AGENT",
            "content": "Could you please clarify your question? I need a bit more detail to help you better."
        }
    ],
    "handoverToHumanNeeded": false,
    "sectionsRetrieved": [
        {
            "score": 0.3590198,
            "content": "What are Tesla hardware upgrades? These are physical improvements, like cameras or chips, that can be installed to enhance the car’s technology."
        },
        {
            "score": 0.35765898,
            "content": "What are the main features added by OTA updates? OTA updates can improve performance, introduce new driving modes, and enhance safety features."
        },
        {
            "score": 0.35685316,
            "content": "How do OTA (Over-the-Air) updates work? Tesla cars receive software updates remotely, improving features and adding functionalities."
        }
    ]
}
```

No modo handover, o GPT irá transferir para o atendimento humano, já que não tem contexto para responder sobre esse assunto. O resultado será algo assim:

```json
{
    "messages": [
        {
            "role": "USER",
            "content": "Cookies"
        },
        {
            "role": "AGENT",
            "content": "I'm transferring you to a specialized agent for further assistance. They will be with you shortly."
        }
    ],
    "handoverToHumanNeeded": true,
    "sectionsRetrieved": [
        {
            "score": 0.3590198,
            "content": "What are Tesla hardware upgrades? These are physical improvements, like cameras or chips, that can be installed to enhance the car’s technology."
        },
        {
            "score": 0.35765898,
            "content": "What are the main features added by OTA updates? OTA updates can improve performance, introduce new driving modes, and enhance safety features."
        },
        {
            "score": 0.35685316,
            "content": "How do OTA (Over-the-Air) updates work? Tesla cars receive software updates remotely, improving features and adding functionalities."
        }
    ]
}
```

O mesmo resultado será exibido se no modo clarification o usuário enviar 2 mensagens que o GPT não entende ou não encontra no contexto fornecido. 

Em casos de conteúdos classificados como N2, para o modo handover, o GPT irá transferir diretamente para um atendente, como consta no exemplo acima. Já para o modo clarification, o GPT vai responder apenas com o conteúdo prévio fornecido, sem direcionar para qualquer atendimento ou mencionar que isso seria necessário. Segue o exemplo de entrada para obter essa situação:

OBSERVAÇÃO: Para de fato receber alguma resposta com N2, coloque em config.php $amount com valor 10. Teste em ambos os modos para ver as diferenças

```json
{
    "helpdeskId": "5555",           
    "projectName": "Tesla Support", 
    "messages": [{                  
        "role": "USER",
        "content": "Buy tesla with crypto"
    }]
}
```

Resultado modo clarification:

```json
{
    "messages": [
        {
            "role": "USER",
            "content": "Buy tesla with crypto"
        },
        {
            "role": "AGENT",
            "content": "Yes, it is possible to buy a Tesla with cryptocurrencies like Dogecoins."
        }
    ],
    "handoverToHumanNeeded": false,
    "sectionsRetrieved": [
        {
            "score": 0.55561596,
            "content": "Can I buy a Tesla with cryptos like DogeCoins? Yes, this is possible, I'll forward you to talk with Elon. Wait a second please."
        },
        {
            "score": 0.48547864,
            "content": "Can I finance a Tesla purchase? Yes, Tesla offers financing options in various countries."
        },
        {
            "score": 0.46273443,
            "content": "Is it safe to buy a used Tesla? Yes, but it is recommended to buy from a dealership or a trusted seller."
        },
        {
            "score": 0.4585055,
            "content": "What is Tesla? Tesla is a technology and automotive company focused on producing electric vehicles, energy solutions, and autonomous driving technology."
        },
        {
            "score": 0.4534218,
            "content": "Does Tesla offer customer support? Yes, Tesla provides support via phone, email, and directly through the app."
        },
        {
            "score": 0.45119298,
            "content": "What car models does Tesla offer? Tesla offers the Model S, Model 3, Model X, Model Y, and the Cybertruck."
        },
        {
            "score": 0.4484507,
            "content": "What is Tesla Insurance? Tesla Insurance is a vehicle insurance program offered by Tesla in some US states."
        },
        {
            "score": 0.44363067,
            "content": "Is Tesla compatible with any country’s power grid? Yes, with adapters, Tesla can be charged on any power grid."
        },
        {
            "score": 0.44334674,
            "content": "What accessories are available for Tesla vehicles? Custom floor mats, seat covers, chargers, and other accessories are offered."
        },
        {
            "score": 0.44106495,
            "content": "Can I use other public chargers for my Tesla? Yes, Tesla is compatible with many third-party chargers, though adapters may be needed."
        }
    ]
}
```

OBSERVAÇÃO: Para testar a transferência para atendimento humano no modo clarification, mantenha o mesmo "helpdeskId". Os testes acima tiveram os IDs diferentes para mostrar os resultados diretos ignorando o incremento nos arquivos .json

Para testar o agendamento para o modo clarification, basta ir em config.php e alterar as flags abaixo:

```php 
$targetTime = '10:04:00'; // Coloque um horário próximo ao horário atual
$setScheduler = true; // Deve ser true para funcionar
$fileDuration = '+2 minutes'; // Coloque uma duração curta para os arquivos serem removidos logo
```
OBSERVAÇÃO: O arquivo com o helpdeskID do qual você está fazendo a requisição não será excluído até que outro helpdeskId seja utilizado. Essa é uma medida de segurança para o caso em que o usuário ainda esteja na conversa com o GPT e a contagem de dúvidas ainda precise ser rastreada. Por esse motivo também, foi recomendado colocar a validade dos arquivos para 30 minutos, uma vez que podemos ter muitos helpdeskIds em contato com o Chat ao mesmo tempo.

## Restrições

A execução do código da API será interrompida nos seguintes casos:
* Se qualquer variavel em config.php não tiver o tipo esperado
* Se não houver arquivo .env
* Se o conteúdo de .env for inválido 
* Se a requisição não for POST
* Se o conteúdo recebido não for JSON
* Se o JSON não contiver os dados helpdeskId, projectName e messages
* Se o conteúdo do JSON não for uma string
* Se a requisição à qualquer API externa falhar


## Mapeamento de Código

**A classe Master conta com os seguintes métodos**:

* **askToClarify**: Responsável por criar e editar os arquivos .json temporários quando o GPT pedir esclarecimentos para o usuário. A função recebe a frase do GPT e compara com a frase esperada para tal caso, se forem idênticas, o valor de increment é aumentado e salvo no respectivo arquivo.

```php
// Parâmetros 
string $gptResponse // Resposta do GPT
string $helpdeskId // ID do atendimento
string $duration // Duração do arquivo .json temporário
```

* **generateContext**: Contém as instruções que serão enviadas ao GPT adicionadas dos contextos fornecidos. No caso do modelo handover, é verificado se existe conteúdo do tipo N2, se houver, alteramos a flag de $_SESSION['humanAgent'] para true

```php
// Parâmetros 
string $model // Modelo de resposta do GPT
mixed $apiRes // Conteúdo que contém o contexto
```

* **clearFolder**: Responsável por verificar a pasta temporária e identificar os arquivos do tipo .json que devem ser excluídos a partir do valor de increment e de expiration_date.

```php
// Parâmetros 
string $helpdeskId // ID do atendimento

```

* **fetchApi**: Responsável por fazer as chamadas à APIs externas.

```php
// Parâmetros 
string $api // URL da API
mixed $content // Conteúdo que será enviado a API
string $method // Métodos POST, GET, PUT etc
mixed $token = null // Sua chave de acesso a API
mixed $authType = null // Tipo de autenticação. Ex: "Authorization: Bearer", "api-key:"
bool $json = true // Se o conteúdo vai em formato json
```

* **validateType**: Responsável por fazer a validação de um tipo de variável, como string, int, bool etc.

```php
// Parâmetros 
string $expected // Tipo esperado. Ex: string, bool etc
mixed $value // Conteúdo da variável
mixed $fall // Caso não corresponda ao tipo esperado, esse será o valor atribuido
```

* **inSeconds**: Responsável por converter uma string com um horário em segundos para realizar o cálculo do tempo.

```php
// Parâmetros 
string $hours // HOrário em formato de string. Ex: "13:25:10"
```

* **loadEnv**: Carrega as variáveis de ambiente para o código. O conteúdo que ficará em cada chave do arquivo .env é verificado e validado, e caso seja inválido, a execução da API é interrompida uma vez que não será possível acessar as APIs da openai e da azure. O arquivo .env deve estar na pasta raiz do projeto.

**Fluxo do controller Completions**:

Primeiramente, o código irá validar o conteúdo essencial e aplicar as restrições ou interromper a execução se for o caso. Após isso, basicamente será feito o processamento dos dados recebidos do cliente (via postman ou navegador) e estes serão enviados para a API da openai para obter o conteúdo embbeding.

```php
$openaiData = [
  'model' => 'text-embedding-3-large',
  'input' => $prompt // pergunta do usuário
];
```

Após obter o conteudo embedding, este será enviado a API da azure para obter o contexto relacionado a pergunta do usuário

```php
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
```

O conteúdo obtido será adicionado ao contexto que deve ser enviado ao GPT pela api da openai

```php
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
```

O resultado obtido será processado de acordo com o model escolhido e será enviado ao usuário como resposta.