# Scheduler

Add to `routes/console.php` or the Laravel scheduler configuration:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('elevateher:send-reminders')->everyMinute()->withoutOverlapping();

// Suggested operational tasks
Schedule::command('queue:prune-failed --hours=168')->dailyAt('04:15');
```

Production cron:

```bash
* * * * * cd /path/to/elevateher360 && php artisan schedule:run >> /dev/null 2>&1
```

If queues are used:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```
