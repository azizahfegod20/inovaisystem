FROM node:22-alpine

WORKDIR /app

# Install dependencies first (cached layer)
COPY package.json package-lock.json* pnpm-lock.yaml* ./

RUN corepack enable && corepack prepare pnpm@10.33.0 --activate \
    && pnpm install --frozen-lockfile || npm install

# Copy source (will be overridden by volume in dev)
COPY . .

# Nuxt dev server
EXPOSE 3000

CMD ["npx", "nuxt", "dev", "--host", "0.0.0.0", "--port", "3000"]
