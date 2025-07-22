# Como funciona?

Esse script contém uma API que retorna respostas de chatbot baseada em um conteúdo específico de uma determinada empresa. A ideia é que o usuário faça perguntas relacionadas à empresa e estas sejam respondidas pela inteligência artificial, no caso o GPT, que deve usar apenas o contexto fornecido pela empresa. Existem 2 modos disponíveis para o fluxo de respostas, que são o clarification e o handover.

No modo clarification, o GPT deve direcionar o usuário para o atendimento humano caso ele tenha pedido esclarecimentos ao usuário mais de 2 vezes. No modo handover, isso acontecerá se a pergunta do usuário estiver relacionada ao contexto classificado como N2, o qual exige que um agente especializado responda ao invés do GPT.

O script vem por padrão configurado para o modo clarification, mas pode ser alterado para o modo handover. Para isso, basta acessar o arquivo config.php e alterar a flag $model para handover.


# Especificações

A API foi desenvolvida em PHP e não contém nenhum outro framework para backend. O script também conta com uma página web onde é possível simular a visualização do cliente do chat. A página web foi desenvlvida em HTML, CSS, JavaScript e Bootstrap.


# Instalação

A instalação do ambiente e as dependências é feita através do Docker. Uma vez que os arquivos desse repositório estejam baixados, basta instalar com essa linha de comando:

```yml
docker-compose up --build
```

Na segunda execução, em caso de não possuir o Docker Desktop instalado, execute essa linha de comando:

```yml
docker-compose up
```

Para acessar a API do Postman, use:

```
http://localhost:3000/conversations/completions
```

Para acessar a API com o frontend do navegador, use:

```
http://localhost:3000
```

⚠️ ATENÇÃO: Antes de testar o sistema, leia as configurações abaixo!


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

⚠️ ATENÇÃO: Se alterar o nome da pasta temporária, não se esqueça de alterar o nome da pasta criada também!


## Funcionamento do modo CLARIFICATION

Uma vez que nesse modo o GPT só pode pedir esclarecimentos 2 vezes no máximo, a contagem de vezes em que isso ocorre é incrementada em um arquivo .json que é salvo com o ID do help desk na pasta temps. Nesse arquivo também consta a data e horário em que o pedido de esclarecimento foi realizado com intuito de gerenciar o prazo que esse arquivo deve permanecer no servidor. Levando em consideração que um atendimento de help desk não costuma ser muito prolongado, por padrão, o prazo para esses arquivos temporários serem excluídos é de 25 minutos (podendo ser alterado em config.php).

Por padrão, a função de limpeza da pasta temps será chamada a cada requisição à API. Entretanto, caso esse não seja o fluxo desejado, basta alterar a flag $setScheduler para true. Essa flag é responsável por ativar um agendamento de limpeza da pasta temps, o qual será executado no horário definido em $targetTime.

⚠️ ATENÇÃO: É recomendado que o horário seja definido pelo menos a partir das 23:00 porque o sistema verifica se o horário atual é maior que o horário de agendamento, então, se o agendamento estiver definido para as 14:00, qualquer requisição após esse horário irá executar a limpeza da pasta (no caso só arquivos com cadastro feito a mais de 25 minutos da hora atual).

Para conteúdo classificado como N2, o GPT irá responder dentro do contexto fornecido mas não irá redirecionar para atendimento humano. O GPT foi instruído para identificar palavras como "forward" e "redirect" para entender que se trata desse caso.


## Funcionamento do modo HANDOVER

Neste modo, o GPT irá verificar se o conteúdo enviado pelo usuário requer atendimento humano. O GPT foi instruído para realizar esse redirecionamento se o contexto utilizado para responder a pergunta específica do usuário conter palavras chaves como "forward" e "redirect".




### What You'll Need to Do

- **Use RAG (Retrieval Augmented Generation)**: Implement this technique in your endpoint (simple explanation [here](https://lucvandonkersgoed.com/2023/12/11/retrieval-augmented-generation-rag-simply-explained/)).
- **Use Provided Resources**: We've supplied sample HTTP calls via CURL that you can import into Postman to speed things up—we don't want to waste your time reading docs.
- **Focus on Code and Solution**: We want to see your code—simplicity, readability, and maintainability are key. Please include explanations in the README.md file at the projet's root about the decisions you made and how it could evolve. 
- **Docker**: Please use docker to make it ease for us to run your code
- **Code language**: Please use English in your code
  
*In your README file, please include:*
  1. **Instructions to Run the Code**: Step-by-step guide on how to set up and run your project
  2. **Main Technical Decisions**: Explain the key choices you made during development.
  3. **Relevant Comments About Your Project**: Any additional insights or considerations.
  4. **Comments in portuguese**: Let's use our mother language there to speed things up ;D


## Rules for the AI Response

1. **Use Only IDS Content**: The AI should not use any information outside of the provided Improved Data Set (IDS) when generating responses.

### Choose one of the following features to implement:

#### Option 1: Clarification Feature

2. **Clarify When Unsure**: If the AI doesn't have enough info to provide a solid answer, it should ask the user for more details.

3. **Limit Clarifications**: The AI can make up to **2 clarifications** per conversation. If a 3rd is needed, it should inform the user that the ticket will be escalated to a human specialist and set `handoverToHumanNeeded: true` in the response.

#### Option 2: Handover Feature

4. **Automatic escalation for N2 content**: Whenever asks something related about a content labeled with type **N2** returned by the Vector DB, the API should return `handoverToHumanNeeded: true` along with the answer. Note: N1 content types are those which the AI should be able to answer by itself entirely; N2 are the content that are marked as "this should be redirected to a human" by our customers (leaders/heads of CX)

**Note**: Your solution doesn't need to be rocket science and there is no right or wrong way of implementing the features above. It can be as simple as some IF statements if you think it would be a good start. Think of what you're building as an MVP; it doesn't have to be perfect. If you have some cool ideas that would be hard to implement or test, please share them in the README. 

# What We Provide

- **OpenAI API Key**: We'll provide you with an API key to use OpenAI's `text-embedding-ada-002` model for embeddings and the `gpt-4` model for chat completions.
- **Vector DB Key**: We refer to our FAQs as the "Improved Data Set" (IDS) — think of it as our beefed-up FAQ on steroids. For this challenge, we've prepared an [IDS](https://docs.google.com/spreadsheets/d/1SbLV3OA6m3dYery6AqgPruxYTjEZ5TJlrtxK7bn8pEA/edit?usp=sharing) that's already populated in our vector database (hosted on Azure AI Search), so you don't need to create or maintain any content for this challenge.
  
Here are the request samples you will need in your application (tip: import them on postman to test and understand how they work):

### Embed the User Question

Use the `$OPEN_AI_KEY` we sent you.

```bash
curl https://api.openai.com/v1/embeddings \
  -H "Authorization: Bearer $OPEN_AI_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "text-embedding-3-large",
    "input": "How long does a Tesla battery last before it needs to be replaced?"
  }'
```

### Semantic Search on Vector DB

Replace `[...embeddings here...]` with the vector returned by the embeddings model. Use the `$AZURE_AI_SEARCH_KEY` we sent you.

```bash
curl --location --request POST 'https://claudia-db.search.windows.net/indexes/claudia-ids-index-large/docs/search?api-version=2023-11-01' \
--header 'Content-Type: application/json' \
--header 'api-key: $AZURE_AI_SEARCH_KEY' \
--data-raw '{
    "count": true,
    "select": "content, type",
    "top": 10,
    "filter": "projectName eq '\''tesla_motors'\''",
    "vectorQueries": [
        {
            "vector": [...embeddings here...],
            "k": 3,
            "fields": "embeddings",
            "kind": "vector"
        }
    ]
}'
```

### Request to Chat Completions model

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Authorization: Bearer $OPEN_AI_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-4o",
    "messages": [
      {"role": "system", "content": "You are a dumb assistant. Always say \"Hello!\" and nothing more"},
      {"role": "user", "content": "Hello! How long does a Tesla battery last before it needs to be replaced?"}
    ]
  }'
```

---

Have fun, and don't hesitate to reach out if you have any questions. We can't wait to see what you come up with! 😍
