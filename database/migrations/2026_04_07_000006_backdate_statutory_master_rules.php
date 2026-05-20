<?php

use App\Models\StatutoryComponent;
use App\Models\StatutoryRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $componentIds = StatutoryComponent::query()
            ->whereIn('code', ['EPF', 'ESIC', 'PT'])
            ->pluck('id')
            ->all();

        if (!empty($componentIds)) {
            StatutoryRule::query()
                ->whereIn('component_id', $componentIds)
                ->whereNull('created_by')
                ->whereDate('effective_from', '2026-04-01')
                ->update(['effective_from' => '2020-01-01']);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
