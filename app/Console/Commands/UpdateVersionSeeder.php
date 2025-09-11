<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Database\Seeders\VersionSeeder;

class UpdateVersionSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:update-seeder {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the VersionSeeder to include the latest version';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        try {
            // Run the VersionSeeder
            $seeder = new VersionSeeder();
            $seeder->run();

            $this->info('VersionSeeder updated successfully!');
        } catch (\Exception $e) {
            $this->error('Error updating VersionSeeder: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }

    /**
     * Check if the command should proceed.
     *
     * @return bool
     */
    protected function confirmToProceed()
    {
        if ($this->option('force')) {
            return true;
        }

        if ($this->laravel->environment() === 'production') {
            return $this->confirm('This will update the VersionSeeder in production. Are you sure you want to proceed?');
        }

        return true;
    }
}
