<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class QueueConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:config {connection?}';

    /**
     * The console command description.
     */
    protected $description = 'Show current queue configuration or switch between Redis and RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = $this->argument('connection');

        if ($connection) {
            return $this->switchConnection($connection);
        }

        return $this->showCurrentConfig();
    }

    private function showCurrentConfig(): int
    {
        $this->info('Current Queue Configuration:');
        $this->line('');

        $currentConnection = config('queue.default');
        $this->line("Queue Connection: <comment>{$currentConnection}</comment>");

        $horizonConnection = config('horizon.defaults.supervisor-1.connection');
        $this->line("Horizon Connection: <comment>{$horizonConnection}</comment>");

        $this->line('');
        $this->info('Available Commands:');
        $this->line('  php artisan queue:config redis     - Switch to Redis');
        $this->line('  php artisan queue:config rabbitmq  - Switch to RabbitMQ');

        return 0;
    }

    private function switchConnection(string $connection): int
    {
        if (! in_array($connection, ['redis', 'rabbitmq'])) {
            $this->error("Invalid connection. Use 'redis' or 'rabbitmq'");

            return 1;
        }

        $envFile = base_path('.env');

        if (! file_exists($envFile)) {
            $this->error('.env file not found');

            return 1;
        }

        // Update QUEUE_CONNECTION in .env
        $envContent = file_get_contents($envFile);
        $envContent = preg_replace(
            '/^QUEUE_CONNECTION=.*$/m',
            "QUEUE_CONNECTION={$connection}",
            $envContent
        );

        file_put_contents($envFile, $envContent);

        $this->info("Switched queue connection to: <comment>{$connection}</comment>");
        $this->line('');
        $this->warn('Please restart Horizon to apply the new configuration:');
        $this->line('  php artisan horizon:terminate');
        $this->line('  php artisan horizon');

        return 0;
    }
}
