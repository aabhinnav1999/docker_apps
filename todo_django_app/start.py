import os
import sys
import subprocess

BASE_DIR = "/app"

def run(cmd):
    result = subprocess.run(cmd, cwd=BASE_DIR)
    if result.returncode != 0:
        sys.exit(result.returncode)

def main():
    os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
    run([sys.executable, "manage.py", "migrate", "--noinput"])
    os.execv(
        sys.executable,
        [sys.executable, "manage.py", "runserver", "0.0.0.0:8000"]
    )

if __name__ == "__main__":
    main()