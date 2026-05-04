<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_vendor_emits_a_created_audit_log(): void
    {
        $vendor = Vendor::factory()->create();

        $log = AuditLog::query()
            ->where('auditable_type', Vendor::class)
            ->where('auditable_id', $vendor->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(['vendor'], $log->tags);
        $this->assertNotEmpty($log->new_values);
    }

    public function test_updating_a_vendor_emits_an_updated_audit_log_with_diff(): void
    {
        $vendor = Vendor::factory()->create(['org_name' => 'Old Co']);

        $vendor->update(['org_name' => 'New Co']);

        $log = AuditLog::query()
            ->where('auditable_type', Vendor::class)
            ->where('auditable_id', $vendor->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Old Co', $log->old_values['org_name'] ?? null);
        $this->assertSame('New Co', $log->new_values['org_name'] ?? null);
    }

    public function test_deleting_a_vendor_emits_a_deleted_audit_log(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->delete();

        $this->assertTrue(
            AuditLog::query()
                ->where('auditable_type', Vendor::class)
                ->where('auditable_id', $vendor->id)
                ->where('event', 'deleted')
                ->exists()
        );
    }
}
