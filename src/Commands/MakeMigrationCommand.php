<?php

namespace AshiqFardus\ApprovalProcess\Commands;

use Illuminate\Console\Command;
use ApprovalWorkflow\ApprovalProcess\Models\ApprovalRequest;

class MakeMigrationCommand extends Command
{
    protected $signature = 'approval:make-migration {name}';
    protected $description = 'Create a custom approval workflow migration';

    public function handle(): int
    {
        $name = $this->argument('name');
        $timestamp = now()->format('Y_m_d_His');
        $filename = "{$timestamp}_create_{$name}_workflows_table.php";

        $migrationPath = database_path("migrations/{$filename}");

        $stub = $this->getStub();
        $content = str_replace(
            ['{{ class }}', '{{ table }}'],
            [$this->getClassName($name), $name],
            $stub
        );

        if (!\File::put($migrationPath, $content)) {
            $this->error("Failed to create migration: {$filename}");
            return self::FAILURE;
        }

        $this->info("Migration created: {$filename}");
        return self::SUCCESS;
    }

    protected function getClassName(string $name): string
    {
        return 'Create' . str()->studly($name) . 'WorkflowsTable';
    }

    protected function getStub(): string
    {
        return <<<'STUB'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{{ table }}_workflows', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{{ table }}_workflows');
    }
};
STUB;
    }
}
