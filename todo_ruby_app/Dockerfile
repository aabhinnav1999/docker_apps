FROM ruby:3.3-alpine

RUN apk add --no-cache \
    build-base \
    sqlite-dev \
    sqlite

WORKDIR /app

COPY Gemfile ./
RUN bundle install

COPY . .

# Expose sinatra port
EXPOSE 4567

# Start the app
CMD ["ruby", "app.rb"]
