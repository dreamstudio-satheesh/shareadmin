## Deploy Reverb in Production

When deploying your application, ensure that the Reverb server is managed properly:

**Using Supervisor:**

Create a Supervisor configuration file:

```
[program:reverb]
process_name=%(program_name)s
command=php artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/reverb.log

```

Replace `/path/to/your/project/` with your actual project path.

**Start Supervisor:**


```
sudo supervisorctl reread 
sudo supervisorctl update 
sudo supervisorctl start reverb`
```

This setup ensures that Reverb runs continuously and restarts automatically if it crashes.