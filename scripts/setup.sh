#!/usr/bin/env bash
#
# scripts/setup.sh — Build e start completo do ambiente de desenvolvimento
#
# Uso: bash scripts/setup.sh
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSE="docker compose -f $ROOT_DIR/docker-compose.yml"

# ── Cores ──────────────────────────────────────────────
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

step() { echo -e "\n${CYAN}▸ $1${NC}"; }
ok()   { echo -e "${GREEN}  ✔ $1${NC}"; }
warn() { echo -e "${YELLOW}  ⚠ $1${NC}"; }

# ── 1. Subir infra (Postgres + Redis) ─────────────────
step "Subindo PostgreSQL e Redis..."
$COMPOSE up -d postgres redis
ok "Postgres e Redis rodando"

# ── 2. Aguardar health checks ─────────────────────────
step "Aguardando serviços ficarem saudáveis..."
$COMPOSE up -d postgres redis
timeout=30
elapsed=0
until $COMPOSE exec -T postgres pg_isready -U inovai > /dev/null 2>&1; do
  sleep 1
  elapsed=$((elapsed + 1))
  if [ $elapsed -ge $timeout ]; then
    warn "Timeout esperando Postgres"; break
  fi
done
ok "Postgres pronto"

until $COMPOSE exec -T redis redis-cli ping 2>/dev/null | grep -q PONG; do
  sleep 1
  elapsed=$((elapsed + 1))
  if [ $elapsed -ge $timeout ]; then
    warn "Timeout esperando Redis"; break
  fi
done
ok "Redis pronto"

# ── 3. Build das imagens (frontend + backend) ─────────
step "Build das imagens Docker..."
$COMPOSE build frontend backend
ok "Imagens construídas"

# ── 4. Backend — dependências + .env + migrations ─────
step "Configurando backend Laravel..."
$COMPOSE run --rm --no-deps backend bash -c '
  composer install --prefer-dist --no-interaction
  if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
  fi
'
ok "Dependências do backend instaladas"

step "Rodando migrations..."
$COMPOSE run --rm backend php artisan migrate --force
ok "Migrations executadas"

# ── 5. Frontend — dependências ─────────────────────────
step "Instalando dependências do frontend..."
$COMPOSE run --rm --no-deps frontend sh -c '
  if command -v pnpm > /dev/null 2>&1; then
    pnpm install
  else
    npm install
  fi
'
ok "Dependências do frontend instaladas"

# ── 6. Subir tudo ─────────────────────────────────────
step "Subindo todos os serviços..."
$COMPOSE up -d
ok "Todos os serviços rodando!"

# ── Resumo ─────────────────────────────────────────────
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Ambiente de desenvolvimento pronto!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "  Frontend (Nuxt):   ${CYAN}http://localhost:3000${NC}"
echo -e "  Backend (Laravel): ${CYAN}http://localhost:8000${NC}"
echo -e "  PostgreSQL:        ${CYAN}localhost:5432${NC}  (inovai/inovai_secret)"
echo -e "  Redis:             ${CYAN}localhost:6379${NC}"
echo ""
echo -e "  Logs:  ${YELLOW}docker compose logs -f${NC}"
echo -e "  Parar: ${YELLOW}docker compose down${NC}"
echo ""
