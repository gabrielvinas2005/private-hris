# Multi-stage build for Vue.js frontend
FROM node:22-alpine AS builder

# Set working directory
WORKDIR /app

# Copy package files first for better caching
COPY package*.json ./

# Install dependencies
RUN NODE_ENV=development npm i

# Copy source code (EXCLUDING node_modules if .dockerignore exists)
COPY . .

# Set build arguments and environment variables
ARG VITE_API_URL=http://nginx/api
ENV VITE_API_URL=${VITE_API_URL}

# Build the application
RUN npm run build

# Production stage with Nginx
FROM nginx:alpine AS production

COPY --from=builder /app/dist /usr/share/nginx/html

RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /usr/share/nginx/html; \
    index index.html; \
    location / { \
        try_files $uri $uri/ /index.html; \
    } \
}' > /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
