.PHONY: up down ip docker-ip access

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

docker-ip: ip

access:
	@echo "=== Application access / network info ==="; \
	if command -v docker >/dev/null 2>&1; then \
		CID=$$(docker compose ps -q web 2>/dev/null || docker-compose ps -q web 2>/dev/null); \
		if [ -n "$$CID" ]; then \
			echo "web container id: $$CID"; \
			echo "container IP: $$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID)"; \
			echo "container -> host port mapping:"; \
			docker compose port web 80 2>/dev/null || docker-compose port web 80 2>/dev/null || echo "  (no mapping found)"; \
			echo "docker bridge gateway: $$(docker network inspect bridge --format '{{range .IPAM.Config}}{{.Gateway}}{{end}}' 2>/dev/null || echo 'n/a')"; \
		else \
			echo "web container not running"; \
		fi; \
	else \
		echo "docker not found on PATH"; \
	fi; \
	HIPS=$$(hostname -I 2>/dev/null || echo 'n/a'); \
	echo "host IP(s): $$HIPS"; \
	DEF_IP=$$(ip route get 8.8.8.8 2>/dev/null | sed -n 's/.*src \([^ ]*\).*/\1/p' || echo 'n/a'); \
	IFACE=$$(ip route get 8.8.8.8 2>/dev/null | sed -n 's/.*dev \([^ ]*\).*/\1/p' || echo 'n/a'); \
	echo "default interface: $$IFACE (source IP: $$DEF_IP)"; \
	echo ""; \
	echo "URLs to try:"; \
	echo " - Local (this machine): http://localhost:8080 or https://localhost:8443"; \
	if [ "$$DEF_IP" != "n/a" ] && [ -n "$$DEF_IP" ]; then echo " - From another device on the same LAN (mobile): http://$$DEF_IP:8080"; fi; \
	if command -v getent >/dev/null 2>&1 && getent hosts host.docker.internal >/dev/null 2>&1; then echo " - host.docker.internal -> $$(getent hosts host.docker.internal | awk '{print $$1}')"; fi; \
	if command -v docker >/dev/null 2>&1 && [ -n "$$CID" ]; then echo " - Container direct (only if reachable): http://$$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $$CID):80"; fi; \
	echo ""; \
	echo "If you cannot reach the app from mobile: check that your phone is on the same Wi-Fi, confirm firewall rules and that port 8080 is mapped on the host."; \
	echo "=======================================";