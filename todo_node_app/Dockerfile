FROM node:20-alpine

WORKDIR /app

# Install dependencies first (better layer caching)
COPY package*.json ./
RUN npm ci --only=production

COPY . .

# Ensure the data directory exists inside the container
RUN mkdir -p /app/data

EXPOSE 3000

# Run the server
CMD ["node", "server.js"]
