FROM python:3.12-slim AS base
WORKDIR /app
COPY . .
RUN pip install --no-cache-dir -r requirements.txt -t /app/deps

FROM gcr.io/distroless/python3
WORKDIR /app
ENV PYTHONPATH=/app/deps
ENV PYTHONUNBUFFERED=1
COPY --from=base /app /app
EXPOSE 8000
CMD ["manage.py", "runserver", "0.0.0.0:8000"]