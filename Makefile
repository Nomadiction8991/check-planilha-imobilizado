.PHONY: up down restart ip access logs-tail logs-count logs-by-path logs-by-ip logs-grep help

# ──────────────────────────────────────────────────────────────
#  Variaveis internas
# ──────────────────────────────────────────────────────────────
LOG_FILE = /var/log/apache2/access.log
LOG_ALT  = /var/log/httpd/access_log

# ──────────────────────────────────────────────────────────────
#  Containers
# ──────────────────────────────────────────────────────────────

## Sobe os containers (reconstroi a imagem do servico web)
up:
	docker compose up --build -d

## Para e remove containers, volumes e imagens geradas
down:
	docker compose down --volumes --rmi all

## Reinicia apenas o servico web (sem rebuild)
restart:
	docker compose restart web

# ──────────────────────────────────────────────────────────────
#  Rede / Acesso
# ──────────────────────────────────────────────────────────────

## Exibe IP do container web e IPs do host
ip:
	@CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -n "$$CID" ]; then \
		printf "ID do container  : %s\\n" "$$CID"; \
		printf "IP do container  : %s\\n" "$$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID)"; \
	else \
		printf "Container web nao esta em execucao.\\n"; \
	fi; \
	printf "IPs do host      : %s\\n" "$$(hostname -I)"

## Mostra todas as URLs disponiveis para acessar o sistema
access:
	@printf "=== Informacoes de Acesso e Rede ===\\n"; \
	CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -n "$$CID" ]; then \
		printf "ID do container web : %s\\n" "$$CID"; \
		printf "IP do container     : %s\\n" "$$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID)"; \
		printf "Porta mapeada       : "; \
		docker compose port web 80 2>/dev/null || printf "(sem mapeamento)\\n"; \
	else \
		printf "Container web nao esta em execucao.\\n"; \
	fi; \
	DEF_IP=$$(ip route get 8.8.8.8 2>/dev/null | sed -n 's/.*src \([^ ]*\).*/\1/p'); \
	IFACE=$$(ip route get 8.8.8.8 2>/dev/null | sed -n 's/.*dev \([^ ]*\).*/\1/p'); \
	printf "IPs do host         : %s\\n" "$$(hostname -I)"; \
	printf "Interface principal : %s  (IP: %s)\\n" "$$IFACE" "$$DEF_IP"; \
	printf "\\nURLs disponiveis:\\n"; \
	printf "  [Esta maquina]          http://localhost:8080\\n"; \
	if [ -n "$$DEF_IP" ]; then \
		printf "  [Outro dispositivo/LAN] http://%s:8080\\n" "$$DEF_IP"; \
	fi; \
	if [ -n "$$CID" ]; then \
		CONT_IP=$$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID); \
		printf "  [Direto ao container]   http://%s:80\\n" "$$CONT_IP"; \
	fi; \
	printf "\\nDica: se outro dispositivo nao conseguir acessar, verifique se esta\\n"; \
	printf "na mesma rede Wi-Fi e se a porta 8080 nao e bloqueada pelo firewall.\\n"; \
	printf "=====================================\\n"

# ──────────────────────────────────────────────────────────────
#  Monitoramento de acessos (logs Apache)
# ──────────────────────────────────────────────────────────────

## Exibe as ultimas N linhas do log de acesso  (padrao: N=100)
##   Exemplo: make logs-tail N=200
logs-tail:
	@N=$${N:-100}; \
	CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -z "$$CID" ]; then printf "Container web nao esta em execucao.\\n"; exit 1; fi; \
	docker exec $$CID sh -c "tail -n $$N $(LOG_FILE) 2>/dev/null || tail -n $$N $(LOG_ALT) 2>/dev/null || printf 'Log nao encontrado.\\n'"

## Conta o total de requisicoes registradas no log de acesso
logs-count:
	@CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -z "$$CID" ]; then printf "Container web nao esta em execucao.\\n"; exit 1; fi; \
	TOTAL=$$(docker exec $$CID sh -c "wc -l < $(LOG_FILE) 2>/dev/null || wc -l < $(LOG_ALT) 2>/dev/null || printf 0"); \
	printf "Total de requisicoes no log: %s\\n" "$$TOTAL"

## Lista os TOP caminhos mais acessados  (padrao: TOP=20)
##   Exemplo: make logs-by-path TOP=10
logs-by-path:
	@TOP=$${TOP:-20}; \
	CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -z "$$CID" ]; then printf "Container web nao esta em execucao.\\n"; exit 1; fi; \
	printf "Top %s caminhos mais acessados:\\n" "$$TOP"; \
	docker exec $$CID sh -c "awk '{print $$7}' $(LOG_FILE) 2>/dev/null || awk '{print $$7}' $(LOG_ALT) 2>/dev/null" \
		| grep -v '^\-' | sort | uniq -c | sort -rn | head -n "$$TOP" \
		| awk '{printf "  %6s acessos  ->  %s\\n", $$1, $$2}'

## Lista os TOP IPs que mais fizeram requisicoes  (padrao: TOP=20)
##   Exemplo: make logs-by-ip TOP=5
logs-by-ip:
	@TOP=$${TOP:-20}; \
	CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -z "$$CID" ]; then printf "Container web nao esta em execucao.\\n"; exit 1; fi; \
	printf "Top %s IPs com mais acessos:\\n" "$$TOP"; \
	docker exec $$CID sh -c "awk '{print $$1}' $(LOG_FILE) 2>/dev/null || awk '{print $$1}' $(LOG_ALT) 2>/dev/null" \
		| sort | uniq -c | sort -rn | head -n "$$TOP" \
		| awk '{printf "  %6s requisicoes  ->  %s\\n", $$1, $$2}'

## Busca uma palavra ou expressao no log de acesso
##   Exemplo: make logs-grep PATTERN=10.0.0.144
logs-grep:
	@if [ -z "$(PATTERN)" ]; then \
		printf "Uso: make logs-grep PATTERN=<texto>\\n"; \
		printf "Exemplo: make logs-grep PATTERN=10.0.0.144\\n"; \
		exit 1; \
	fi; \
	CID=$$(docker compose ps -q web 2>/dev/null); \
	if [ -z "$$CID" ]; then printf "Container web nao esta em execucao.\\n"; exit 1; fi; \
	docker exec $$CID sh -c "grep '$(PATTERN)' $(LOG_FILE) 2>/dev/null || grep '$(PATTERN)' $(LOG_ALT) 2>/dev/null" \
		|| printf "Nenhuma ocorrencia encontrada para: $(PATTERN)\\n"

# ──────────────────────────────────────────────────────────────
#  Ajuda
# ──────────────────────────────────────────────────────────────

## Exibe esta mensagem de ajuda
help:
	@printf "Makefile - comandos disponiveis\n"
	@printf "\n"
	@printf "  CONTAINERS\n"
	@printf "    make up                       Sobe os containers (reconstroi a imagem)\n"
	@printf "    make down                     Para e remove containers + volumes + imagens\n"
	@printf "    make restart                  Reinicia o servico web sem rebuild\n"
	@printf "\n"
	@printf "  REDE / ACESSO\n"
	@printf "    make ip                       Mostra IP do container e IPs do host\n"
	@printf "    make access                   Mostra todas as URLs de acesso (LAN inclusa)\n"
	@printf "\n"
	@printf "  MONITORAMENTO DE ACESSOS\n"
	@printf "    make logs-tail [N=100]        Ultimas N linhas do log Apache\n"
	@printf "    make logs-count               Total de requisicoes no log\n"
	@printf "    make logs-by-path [TOP=20]    Caminhos mais acessados\n"
	@printf "    make logs-by-ip   [TOP=20]    IPs com mais acessos\n"
	@printf "    make logs-grep PATTERN=texto  Busca por texto no log\n"
	@printf "\n"
	@printf "  Exemplos:\n"
	@printf "    make up\n"
	@printf "    make access\n"
	@printf "    make logs-tail N=200\n"
	@printf "    make logs-by-ip TOP=5\n"
	@printf "    make logs-grep PATTERN=10.0.0.144\n"
