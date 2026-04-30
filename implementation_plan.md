# Desenvolvimento do Site INEMA

O objectivo é construir uma landing page altamente fiel ao design UI/UX fornecido para o "Sistema de Registro de Ocorrências do INEMA". Vamos focar em criar uma arquitectura limpa, responsiva e com um visual premium utilizando tecnologias base (HTML, CSS e JS), sem a necessidade de frameworks adicionais, garantindo controlo total.

## Proposed Changes

A arquitectura do projecto (pastas e ficheiros) será organizada na pasta actual obedecendo a uma boa Separação de Preocupações (Separation of Concerns):

---

### Estrutura Base
#### [NEW] [index.html](file:///c:/Users/moise/Desktop/tcc/index.html)
O documento principal com a marcação semântica HTML de todas as secções estruturadas (Nav, Hero, Serviços, Testemunhos, Contactos e Rodapé).

---

### Estilos (CSS)
Os estilos estarão na directoria `assets/css/` para facilitar a manutenção:
#### [NEW] [assets/css/variables.css](file:///c:/Users/moise/Desktop/tcc/assets/css/variables.css)
Definições visuais: cores (Amarelo INEMA, Azul Escuro Institucional), tipografia (moderna e legível) e espaçamentos globais.
#### [NEW] [assets/css/reset.css](file:///c:/Users/moise/Desktop/tcc/assets/css/reset.css)
Normalização de base para garantir consistência em todos os browsers.
#### [NEW] [assets/css/components.css](file:///c:/Users/moise/Desktop/tcc/assets/css/components.css)
Modularidade: regras para botões dinâmicos, cartões polidos e blocos reutilizáveis.
#### [NEW] [assets/css/layout.css](file:///c:/Users/moise/Desktop/tcc/assets/css/layout.css)
A estrutura visual macro e micro das secções completas com recurso a Flexbox/CSS Grid.

---

### Scripts (JavaScript & Utilitários)
#### [NEW] [assets/js/main.js](file:///c:/Users/moise/Desktop/tcc/assets/js/main.js)
Interacções do UI (scroll interactivo para secções, mudança do estado da Navbar ou inicialização do slider de ocorrências).
#### [NEW] [util/helpers.js](file:///c:/Users/moise/Desktop/tcc/util/helpers.js)
Funções auxiliares para lógicas complementares.

---

### Assets de Imagens
- Em vez de deixar áreas cinzentas (placeholders), vou usar a minha capacidade de **Gerar Imagens** para produzir as fotografias de médicos, viaturas e cenários de socorro que vemos no design final. Isto garante que tudo esteja com aspecto "Pronto" e incrivelmente profissional.

## Open Questions
- Alguma preferência de tipo de letra (font) no Google Fonts ou concorda com o uso de fontes harmoniosas e premiums (como *Inter* ou *Outfit*)?
- As âncoras/links do menu (Início, Sobre, etc) deverão deslizar a página (smooth scroll) para as respectivas áreas neste site?

## Verification Plan
1. **Estrutura & Mockup:** Primeiro codificarei o Layout HTML. 
2. **Estilização Dinâmica:** Após o HTML, implementarei as cores, tamanhos responsivos e efeitos interativos exigidos no manual de UI Premium.
3. **Imagens Reais & JS:** Gerarei todas as imagens que faltem com IA e testarei num pequeno servidor local garantindo alta performance e polimento.
