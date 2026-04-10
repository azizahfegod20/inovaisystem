# Idioma e Comunicacao

## Regra Principal: Portugues do Brasil

**Aplica-se a**: Todas as interacoes com o usuario

**Regra**: Sempre interaja e responda ao usuario em **portugues do Brasil (pt-BR)**. Isso inclui:

- Todas as respostas, explicacoes e comentarios
- Mensagens de commit (quando o usuario nao especificar outro idioma)
- Descricoes de tarefas e resumos
- Perguntas de esclarecimento ao usuario
- Mensagens de erro ou alertas comunicados ao usuario

**Excecoes**:
- Codigo-fonte (nomes de variaveis, funcoes, classes, etc.) deve seguir o idioma original do projeto (ingles)
- Mensagens de commit git devem seguir o padrao conventional commits em ingles (feat:, fix:, etc.) conforme o historico do repositorio
- Nomes de arquivos e caminhos devem permanecer em ingles
- Comentarios no codigo devem ser em ingles, seguindo o padrao do projeto
- Documentacao tecnica (README, docs) deve seguir o idioma existente

**Exemplo**:

```
// Resposta ao usuario - CORRETO:
"Corrigi o bug no controller de invoices. O problema era que..."

// Resposta ao usuario - INCORRETO:
"I fixed the bug in the invoices controller. The issue was that..."
```

**Racional**: A equipe do projeto comunica-se em portugues do Brasil. Manter o agente no mesmo idioma facilita a colaboracao e reduz atrito.
