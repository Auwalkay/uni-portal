<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create {name} {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with a specific domain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        $this->info("Creating Tenant: {$name}");

        $tenant = Tenant::create([
            'id' => \Illuminate\Support\Str::slug($name),
            'name' => $name,
        ]);

        $this->info("Assigning Domain: {$domain}");

        $tenant->domains()->create([
            'domain' => $domain,
        ]);

        $this->info("Tenant '{$name}' created successfully on domain '{$domain}'.");
        return Command::SUCCESS;
    }
}
