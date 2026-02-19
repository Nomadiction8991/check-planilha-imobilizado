.PHONY: up down ip

up:
	docker compose up --build -d

down:
	docker compose down --volumes --rmi all

ip:
	@CID=$$(docker compose ps -q web 2>/dev/null || docker-compose ps -q web 2>/dev/null); \
	if [ -n "$$CID" ]; then \
		echo "web container id: $$CID"; \
		docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID; \
	else \
		echo "web container not running"; \
	fi; \
	echo "Host IP addresses: $$(hostname -I)"; \
	echo "Open: http://localhost:8080 or https://localhost:8443";