FROM python:3.12-slim AS base
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt -t /app/deps
COPY manage.py .
COPY start.py .
COPY config ./config
COPY todos ./todos  

FROM gcr.io/distroless/python3:nonroot
WORKDIR /app
ENV PYTHONPATH=/app/deps
ENV PYTHONUNBUFFERED=1
COPY --from=base /app /app
EXPOSE 8000
CMD ["start.py"]