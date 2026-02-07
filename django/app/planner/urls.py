from django.urls import path
from . import views

urlpatterns = [
    path('', views.dashboard, name='dashboard'),
    path('toggle/<int:task_id>/', views.toggle_task, name='toggle_task'),
    path('delete/<int:task_id>/', views.delete_task, name='delete_task'),
]