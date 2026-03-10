FROM golang:1.24-alpine AS builder

WORKDIR /app

RUN apk add --no-cache git

COPY go.mod go.sum ./
RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o todo-app .

FROM alpine:3.19

WORKDIR /app

COPY --from=builder /app/todo-app .

EXPOSE 8080

CMD ["./todo-app"]