# 🎓 Sistema de Inscrição Educacional — Carmo de Minas
### Projeto de Análise e Desenvolvimento de Sistemas 2026

### 👥 Equipe
> Ana Carolina Valim Faria <br>
> Naomi Marra Marcondes <br>
> Luccas Lima Barboza <br>

Sistema web para **inscrição, classificação e leilão de vagas** na contratação temporária de profissionais da educação (Designação 2027/2028) do Departamento Municipal de Educação de Carmo de Minas.

Do cadastro do candidato até a atribuição da vaga, tudo em um só lugar: **sem planilhas, sem filas e com cada etapa registrada para auditoria.**

![PHP](https://img.shields.io/badge/PHP-OO-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)


## 💡 O que é

Um processo seletivo municipal tem muitas regras: prazos rígidos, limite de inscrições, critérios de desempate em ordem fixa, recursos, chamadas presenciais e punições por desistência. Este sistema automatiza tudo isso seguindo o edital.

**Para quem é:**

| Perfil | O que faz no sistema |
|---|---|
| 🙋 **Candidato** | Cria conta, escolhe cargos, envia documentos, edita a inscrição e baixa o comprovante |
| 🛡️ **Comissão / Admin** | Registra recursos, ajusta dados, publica classificações e conduz o leilão de vagas |
| 🌐 **Público** | Consulta a classificação preliminar e definitiva |

---

## ⚙️ Como funciona

```
Cadastro  →  Inscrição  →  Classificação  →  Recursos  →  Classificação  →  Leilão
(CPF, +18)   (até 2 cargos)  preliminar       (presencial)   definitiva       de vagas
```

### 1. Cadastro e login
Autocadastro com CPF, e-mail e senha criptografada. O sistema valida o CPF e confirma que o candidato tem **18 anos ou mais** na data da inscrição.

### 2. Inscrição
- Escolha entre **10 cargos** (Servente Escolar, Educador Infantil, Inspetor de Alunos, Auxiliar de Secretaria, professores, especialistas da educação e Psicopedagogo).
- Informe tempo de serviço na rede municipal e titulação.
- Anexe os comprovantes (identidade, CPF, título, residência, diploma etc.).
- **Máximo de 2 inscrições ativas por CPF.**
- Edite quantas vezes quiser **até 23h59 de 10/01/2027**. Depois disso, tudo é bloqueado automaticamente.
- A cada edição, um **novo comprovante em PDF** é emitido, com código único, QR Code e carimbo de data/hora.

### 3. Classificação
O motor de classificação ordena os candidatos pelos critérios de desempate do edital, nesta ordem:

1. Habilitação / escolaridade mínima exigida
2. Maior tempo de serviço na rede municipal de Carmo de Minas
3. Maior titulação
4. Maior idade

### 4. Recursos
A comissão registra os recursos protocolados presencialmente. Durante essa fase, o **cargo do candidato fica travado**. Com base nos pareceres, a administração ajusta os dados e publica a **classificação definitiva**.

### 5. Leilão de vagas
- Check-in documental do candidato no momento da chamada.
- Desclassificação por documentação incompleta ou divergente.
- Atribuição do contrato e registro de desistência de cargo anterior.
- **Suspensão automática de 120 dias** para quem abandona ou desiste de uma vaga, impedindo novos leilões no mesmo cargo.

---

## 📋 Regras de negócio principais

| Código | Regra |
|---|---|
| RN01 | Impede uma 3ª inscrição para o mesmo CPF |
| RN02 | Edições permitidas somente até 23h59 de 10/01/2027 |
| RN03 | Cargo bloqueado durante a fase de recurso |
| RN04 | Suspensão de 120 dias a partir da data formal de desistência |
| RN05 | Desempate final pela data de nascimento mais antiga |

---

## 🗂️ Arquitetura

Desenvolvido em **PHP orientado a objetos**, com banco **MySQL**.

**Modelos**

- `Usuario` (classe abstrata) → `Candidato` e `Admin`
- `Cargo`, `Inscricao`, `DocumentoInscricao`, `Recurso`
- `LeilaoVaga`, `ContratoLeilao`, `PunicaoCandidato`

**Serviços**

- `MotorClassificacao`: aplica os critérios de desempate
- `PdfGenerator`: comprovantes com QR Code e relatórios de classificação
- `AuthMiddleware` / `Sessao`: login e permissões (Candidato × Admin)

**Banco de dados**

`Candidato` · `Cargo` · `Inscricao` · `DocumentoInscricao` · `Recurso` · `LeilaoVaga` · `ContratoLeilao` · `PunicaoCandidato`

---

## 🔒 Segurança e qualidade

- Senhas com hash
- Logs auditáveis: quem alterou, quando e de qual IP
- Histórico de versões de cada inscrição
- Conformidade com a **LGPD**
- Interface responsiva (celular, tablet e desktop)

---

## 🚀 Como testar

**Requisitos:** PHP 8+, MySQL e um servidor local (XAMPP, WAMP ou similar).

1. Clone o repositório na pasta do servidor (ex.: `htdocs`):
   ```bash
   git clone https://github.com/SEU-USUARIO/SEU-REPOSITORIO.git
   ```
2. Crie um banco no MySQL e importe o script SQL do projeto.
3. Configure a conexão com o banco no arquivo de configuração (host, banco, usuário e senha).
4. Inicie Apache e MySQL.
5. Acesse `http://localhost/SEU-REPOSITORIO`.

**Roteiro rápido de teste:**

- Cadastre um candidato e confirme que menores de 18 anos são recusados.
- Crie 2 inscrições e confirme que a 3ª é bloqueada.
- Edite uma inscrição e baixe o novo comprovante.
- Entre como admin e registre um recurso.
- Rode um leilão e registre uma desistência para ver a suspensão de 120 dias.

## 🌐 Link de Acesso: 

Deploy no servidor Oficina HotRods. Feito para teste e avaliação do professor. [Acessar Site](https://oficinahotrods.com.br/equipeX/)