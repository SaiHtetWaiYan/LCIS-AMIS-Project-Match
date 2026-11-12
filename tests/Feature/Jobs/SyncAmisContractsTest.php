<?php

namespace Tests\Feature\Jobs;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Jobs\SyncAmisContracts;
use App\Models\AmisCompany;
use App\Models\AmisContract;
use App\Models\AmisContractItem;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncAmisContractsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{contracts: list<array<string, mixed>>, companies: list<array<string, mixed>>, quantity: ?int, error: ?array{int, array<string, string>}}
     */
    private array $amis = ['contracts' => [], 'companies' => [], 'quantity' => null, 'error' => null];

    private bool $amisFaked = false;

    public function test_stores_contracts_items_companies_and_links(): void
    {
        $this->fakeAmis(
            contracts: [$this->fixture('amis/rent_land_company_linked.json'), $this->fixture('amis/rent_land_legacy_partner.json')],
            companies: [$this->fixture('amis/company.json')],
        );

        SyncAmisContracts::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncSource::Amis, $run->source);
        $this->assertSame(SyncStatus::Succeeded, $run->status);
        $this->assertSame([2, 2, 2], [$run->expected_count, $run->fetched_count, $run->created_count]);
        $this->assertSame(1, $run->details['company_links']);

        $linked = AmisContract::with(['items', 'companies'])->findOrFail(444);
        $this->assertSame('260/ຄຊລ', $linked->contract_number);
        $this->assertSame('ໂຄງການປູກກາເຟ', $linked->contract_name);
        $this->assertSame([], $linked->partner_names);
        $this->assertSame('121.0000', $linked->area_ha);
        $this->assertSame('2025-06-15', $linked->starts_on->toDateString());
        $this->assertSame([901, 902], $linked->items->pluck('id')->sort()->values()->all());
        $this->assertSame([954], $linked->companies->pluck('id')->all());
        $this->assertSame('60.00', $linked->companies[0]->pivot->shareholding_pct);

        $legacy = AmisContract::findOrFail(252);
        $this->assertSame(['ບໍລິສັດບີເອັມຊີ'], $legacy->partner_names);
        $this->assertSame('0.1300', $legacy->area_ha);
        $this->assertSame(1, AmisContractItem::findOrFail(850)->province_id);

        $company = AmisCompany::findOrFail(954);
        $this->assertSame('637767752000', $company->tax_id);
        $this->assertSame('Samphanxay Mining Co., Ltd', $company->name_eng);
    }

    public function test_skips_company_links_to_contracts_we_do_not_have(): void
    {
        $this->fakeAmis(contracts: [$this->fixture('amis/rent_land_company_linked.json')], companies: [$this->fixture('amis/company.json')]);

        SyncAmisContracts::dispatchSync();

        $this->assertSame([444], DB::table('amis_company_contract')->pluck('amis_contract_id')->all());
    }

    public function test_reports_and_logs_area_units_it_cannot_convert(): void
    {
        Log::spy();
        $contract = $this->fixture('amis/rent_land_company_linked.json');
        $contract['items'][1]['unit'] = 'ໄລ່';
        $this->fakeAmis(contracts: [$contract]);

        SyncAmisContracts::dispatchSync();

        $this->assertNull(AmisContract::findOrFail(444)->area_ha);
        $this->assertSame(['ໄລ່' => 1], SyncRun::sole()->details['unknown_units']);
        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $context === ['unit' => 'ໄລ່', 'items' => 1])->once();
    }

    public function test_marks_contracts_and_companies_missing_and_drops_their_links(): void
    {
        $this->fakeAmis(contracts: [$this->fixture('amis/rent_land_company_linked.json'), $this->fixture('amis/rent_land_legacy_partner.json')], companies: [$this->fixture('amis/company.json')]);
        SyncAmisContracts::dispatchSync();
        $this->fakeAmis(contracts: [$this->fixture('amis/rent_land_legacy_partner.json')]);

        SyncAmisContracts::dispatchSync();

        $run = SyncRun::latest('id')->first();
        $this->assertSame(1, $run->missing_count);
        $this->assertSame(1, $run->details['companies']['missing']);
        $this->assertNotNull(AmisContract::findOrFail(444)->missing_since);
        $this->assertNotNull(AmisCompany::findOrFail(954)->missing_since);
        $this->assertNull(AmisContract::findOrFail(252)->missing_since);
        $this->assertSame(0, DB::table('amis_company_contract')->count());
    }

    public function test_records_the_amis_api_key_error_code_when_the_key_has_expired(): void
    {
        $existing = AmisContract::factory()->create();
        $this->fakeAmis(error: [403, ['message' => 'API key has expired', 'code' => 'API_KEY_EXPIRED', 'detail' => 'Expired on 2026-11-01']]);

        SyncAmisContracts::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncStatus::Failed, $run->status);
        $this->assertSame('API_KEY_EXPIRED', $run->error_code);
        $this->assertSame('API key has expired', $run->error_message);
        $this->assertTrue($run->isAuthenticationFailure());
        $this->assertNull($existing->refresh()->missing_since);
    }

    /**
     * Sets what the fake AMIS API returns; the fake is registered once, so later calls change
     * the answers for the next sync. With $error, every request answers that status and body.
     *
     * @param  list<array<string, mixed>>  $contracts
     * @param  list<array<string, mixed>>  $companies
     * @param  ?array{int, array<string, string>}  $error
     */
    private function fakeAmis(array $contracts = [], array $companies = [], ?array $error = null): void
    {
        $this->amis = ['contracts' => $contracts, 'companies' => $companies, 'quantity' => count($contracts), 'error' => $error];

        if ($this->amisFaked) {
            return;
        }

        $this->amisFaked = true;
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            ['contracts' => $contracts, 'companies' => $companies, 'quantity' => $quantity, 'error' => $error] = $this->amis;
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if ($error !== null) {
                return Http::response($error[1], $error[0]);
            }

            return match (true) {
                $path === '/api/v1/public/income/rent-land/quantity' => Http::response(['data' => ['quantity' => $quantity]]),
                $path === '/api/v1/public/income/rent-land' => Http::response(['rent_lands' => $contracts, 'total' => count($contracts), 'page' => 1, 'page_size' => 100, 'total_pages' => 1]),
                $path === '/api/v1/public/companies' => Http::response(['companies' => $companies, 'total' => count($companies), 'page' => 1, 'page_size' => 100, 'total_pages' => 1]),
                (bool) preg_match('#^/api/v1/public/(income/rent-land|companies)/\d+$#', $path) => Http::response(['message' => 'Not found', 'code' => 'NOT_FOUND', 'detail' => ''], 404),
                default => Http::response('Unexpected request', 418),
            };
        });
    }
}
