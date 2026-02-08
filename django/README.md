# Python Based Daily Planner Application

Build using Django web framework, where users can add and manage their daily tasks in the dashboard

## 🛠 Tech Stack
* **Python** (Django)
* **Docker** & **Docker Compose**
* **Nginx**

---

## Getting Started

Follow these steps to get your local development environment up and running.

### 1. Build and Run
First, build the images and start the containers:
```bash
docker-compose up --build
```

### 2. Database Setup
Once the containers are healthy, run the initial migrations to set up your database.
> **Note:** This is only required for the first-time setup.

```bash
docker-compose exec web python manage.py migrate
```

---

## Useful Commands 
| Command | Description |
| :--- | :--- |
| `docker-compose down` | Stop all services |
| `docker-compose logs -f` | View real-time logs |
