from django.db import models
from django.utils import timezone

class Task(models.Model):
    PRIORITY_CHOICES = [
        ('low', 'Low'),
        ('medium', 'Medium'),
        ('high', 'High'),
    ]

    title = models.CharField(max_length=200)
    description = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    due_date = models.DateField(default=timezone.now)
    priority = models.CharField(max_length=10, choices=PRIORITY_CHOICES, default='medium')
    is_completed = models.BooleanField(default=False)

    def __str__(self):
        return self.title

    class Meta:
        ordering = ['due_date', '-priority']