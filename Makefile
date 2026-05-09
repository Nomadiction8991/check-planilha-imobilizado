SHELL := /bin/bash

.PHONY: help up down

help:
	@echo "Comandos disponiveis:"
	@echo "  make help  - Mostra esta ajuda"
	@echo "  make up    - Cria .env (se faltar) e sobe os containers"
	@echo "  make down  - Derruba containers, remove imagens/volumes e limpa .env/vendor"

up:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ".env criado a partir de .env.example"; \
	else \
		echo ".env ja existe, mantendo arquivo atual"; \
	fi
	docker compose up

down:
	docker compose down --rmi all --volumes --remove-orphans
	@if [ -f .env ]; then \
		rm .env; \
		echo ".env removido"; \
	fi
	@if [ -d vendor ]; then \
		rm -rf vendor; \
		echo "vendor removido"; \
	fi
