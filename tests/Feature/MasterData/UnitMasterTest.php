<?php

namespace Tests\Feature\MasterData;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Business;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class UnitMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_units_are_seeded_per_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['KCF', 'WG', 'LBY'] as $code) {
            $business = Business::query()->where('code', $code)->firstOrFail();

            $this->assertDatabaseHas('units', [
                'business_id' => $business->id,
                'name' => 'Porsi',
                'symbol' => 'porsi',
                'type' => 'quantity',
                'is_active' => true,
            ]);
            $this->assertSame(6, Unit::query()->where('business_id', $business->id)->count());
        }
    }

    public function test_unit_resource_only_shows_units_from_active_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $user->forceFill(['current_business_id' => $business->id])->save();
        $this->actingAs($user);

        $businessIds = UnitResource::getEloquentQuery()
            ->select('business_id')
            ->distinct()
            ->pluck('business_id')
            ->all();

        $this->assertSame([$business->id], $businessIds);
    }

    public function test_creating_unit_auto_fills_business_id_from_active_context(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'WG')->firstOrFail();
        $user->forceFill(['current_business_id' => $business->id])->save();
        $this->actingAs($user);

        $page = new CreateUnit;
        $method = new ReflectionMethod(CreateUnit::class, 'mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $data = $method->invoke($page, [
            'name' => 'Box',
            'symbol' => 'box',
            'type' => 'custom',
            'is_active' => true,
        ]);

        $unit = Unit::query()->create($data);

        $this->assertSame($business->id, $unit->business_id);
        $this->assertDatabaseHas('units', [
            'business_id' => $business->id,
            'name' => 'Box',
            'symbol' => 'box',
            'type' => 'custom',
        ]);
    }

    public function test_user_without_active_context_is_redirected_from_unit_resource(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->actingAs($user)
            ->get('/admin/units')
            ->assertRedirect(route('filament.admin.pages.manage-active-context'));
    }

    public function test_unit_name_is_required(): void
    {
        $this->seed(DatabaseSeeder::class);
        $business = Business::query()->where('code', 'KCF')->firstOrFail();

        $this->expectException(QueryException::class);

        Unit::query()->create([
            'business_id' => $business->id,
            'symbol' => 'bad',
            'type' => 'custom',
        ]);
    }
}
