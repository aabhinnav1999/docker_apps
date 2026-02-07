from django.shortcuts import render

# Create your views here.
from django.shortcuts import render, redirect, get_object_or_404
from .models import Task

def dashboard(request):
    if request.method == "POST":
        title = request.POST.get('title')
        due_date = request.POST.get('due_date')
        priority = request.POST.get('priority')
        if title:
            Task.objects.create(title=title, due_date=due_date, priority=priority)
        return redirect('dashboard')

    tasks = Task.objects.all()
    return render(request, 'planner/index.html', {'tasks': tasks})

def toggle_task(request, task_id):
    task = get_object_or_404(Task, id=task_id)
    task.is_completed = not task.is_completed
    task.save()
    return redirect('dashboard')

def delete_task(request, task_id):
    task = get_object_or_404(Task, id=task_id)
    task.delete()
    return redirect('dashboard')