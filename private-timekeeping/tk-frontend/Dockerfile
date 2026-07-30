# Multi-stage build for Vue.js frontend
FROM node:20-alpine AS builder
# Set working directory
WORKDIR /app
# Copy package files
COPY package*.json ./
# Install dependencies
RUN npm i
# Copy source code
COPY . .
ARG VITE_API_URL=http://nginx/api
ENV VITE_API_URL=${VITE_API_URL}
# Build the application
RUN npm run build
# Production stage with Nginx
FROM nginx:alpine AS production
# Copy built application from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html
# Create a simple nginx config for the frontend container
# nginx.conf /etc/nginx/conf.d/default.conf
RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /usr/share/nginx/html; \
    index index.html; \
    location / { \
        try_files $uri $uri/ /index.html; \
    } \
}' > /etc/nginx/conf.d/default.conf
# Expose port
EXPOSE 80
# Start Nginx
CMD ["nginx", "-g", "daemon off;"]