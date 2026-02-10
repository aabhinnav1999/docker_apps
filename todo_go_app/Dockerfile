# Stage 1
FROM golang:1.24-alpine AS builder

WORKDIR /app

RUN apk add --no-cache git

COPY go.mod go.sum ./
RUN go mod download

COPY . .

# Build a static binary
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
    go build -o todo-app main.go

# Stage 2
FROM alpine:3.19

WORKDIR /app

# Create a directory for SQLite file
RUN mkdir -p /app/data

COPY --from=builder /app/todo-app .

EXPOSE 8080

CMD ["./todo-app"]
