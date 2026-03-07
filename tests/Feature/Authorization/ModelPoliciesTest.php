<?php

use App\Enums\InvoiceStatus;
use App\Enums\ProposalStatus;
use App\Models\Campaign;
use App\Models\CatalogPlan;
use App\Models\CatalogPlanItem;
use App\Models\CatalogProduct;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\ProposalLineItem;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Models\SocialAccountMedia;
use App\Models\TaxRate;
use App\Models\User;
use App\Policies\CampaignPolicy;
use App\Policies\CatalogPlanItemPolicy;
use App\Policies\CatalogPlanPolicy;
use App\Policies\CatalogProductPolicy;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProposalLineItemPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\ScheduledPostPolicy;
use App\Policies\SocialAccountMediaPolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\TaxRatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

it('auto-discovers all RFC 012 policies', function (): void {
    expect(Gate::getPolicyFor(SocialAccount::class))->toBeInstanceOf(SocialAccountPolicy::class)
        ->and(Gate::getPolicyFor(Campaign::class))->toBeInstanceOf(CampaignPolicy::class)
        ->and(Gate::getPolicyFor(CatalogProduct::class))->toBeInstanceOf(CatalogProductPolicy::class)
        ->and(Gate::getPolicyFor(CatalogPlan::class))->toBeInstanceOf(CatalogPlanPolicy::class)
        ->and(Gate::getPolicyFor(CatalogPlanItem::class))->toBeInstanceOf(CatalogPlanItemPolicy::class)
        ->and(Gate::getPolicyFor(Client::class))->toBeInstanceOf(ClientPolicy::class)
        ->and(Gate::getPolicyFor(Proposal::class))->toBeInstanceOf(ProposalPolicy::class)
        ->and(Gate::getPolicyFor(ProposalLineItem::class))->toBeInstanceOf(ProposalLineItemPolicy::class)
        ->and(Gate::getPolicyFor(Invoice::class))->toBeInstanceOf(InvoicePolicy::class)
        ->and(Gate::getPolicyFor(TaxRate::class))->toBeInstanceOf(TaxRatePolicy::class)
        ->and(Gate::getPolicyFor(ScheduledPost::class))->toBeInstanceOf(ScheduledPostPolicy::class)
        ->and(Gate::getPolicyFor(SocialAccountMedia::class))->toBeInstanceOf(SocialAccountMediaPolicy::class);
});

it('applies pricing catalog and proposal line item policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $catalogProduct = CatalogProduct::factory()->for($owner)->create();
    $catalogPlan = CatalogPlan::factory()->for($owner)->create();
    $catalogPlanItem = CatalogPlanItem::factory()
        ->for($catalogPlan)
        ->for($catalogProduct)
        ->create();
    $taxRate = TaxRate::factory()->for($owner)->create();

    $client = Client::factory()->for($owner)->create();
    $proposal = Proposal::factory()->for($owner)->for($client)->create();
    $proposalLineItem = ProposalLineItem::factory()->for($proposal)->create();

    $matchingClientUser = ClientUser::factory()->for($client)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $clientUserGate = Gate::forUser($matchingClientUser);

    expect($ownerGate->allows('viewAny', CatalogProduct::class))->toBeTrue()
        ->and($ownerGate->allows('create', CatalogProduct::class))->toBeTrue()
        ->and($ownerGate->allows('view', $catalogProduct))->toBeTrue()
        ->and($ownerGate->allows('update', $catalogProduct))->toBeTrue()
        ->and($ownerGate->allows('delete', $catalogProduct))->toBeTrue()
        ->and($outsiderGate->allows('view', $catalogProduct))->toBeFalse()
        ->and($outsiderGate->allows('update', $catalogProduct))->toBeFalse()
        ->and($outsiderGate->allows('delete', $catalogProduct))->toBeFalse()
        ->and($clientUserGate->allows('viewAny', CatalogProduct::class))->toBeFalse()
        ->and($clientUserGate->allows('create', CatalogProduct::class))->toBeFalse()
        ->and($clientUserGate->allows('view', $catalogProduct))->toBeFalse()
        ->and($ownerGate->allows('viewAny', CatalogPlan::class))->toBeTrue()
        ->and($ownerGate->allows('create', CatalogPlan::class))->toBeTrue()
        ->and($ownerGate->allows('view', $catalogPlan))->toBeTrue()
        ->and($ownerGate->allows('update', $catalogPlan))->toBeTrue()
        ->and($ownerGate->allows('delete', $catalogPlan))->toBeTrue()
        ->and($outsiderGate->allows('view', $catalogPlan))->toBeFalse()
        ->and($ownerGate->allows('viewAny', CatalogPlanItem::class))->toBeTrue()
        ->and($ownerGate->allows('create', CatalogPlanItem::class))->toBeTrue()
        ->and($ownerGate->allows('view', $catalogPlanItem))->toBeTrue()
        ->and($ownerGate->allows('update', $catalogPlanItem))->toBeTrue()
        ->and($ownerGate->allows('delete', $catalogPlanItem))->toBeTrue()
        ->and($outsiderGate->allows('view', $catalogPlanItem))->toBeFalse()
        ->and($clientUserGate->allows('view', $catalogPlanItem))->toBeFalse()
        ->and($ownerGate->allows('viewAny', TaxRate::class))->toBeTrue()
        ->and($ownerGate->allows('create', TaxRate::class))->toBeTrue()
        ->and($ownerGate->allows('view', $taxRate))->toBeTrue()
        ->and($ownerGate->allows('update', $taxRate))->toBeTrue()
        ->and($ownerGate->allows('delete', $taxRate))->toBeTrue()
        ->and($outsiderGate->allows('view', $taxRate))->toBeFalse()
        ->and($outsiderGate->allows('update', $taxRate))->toBeFalse()
        ->and($outsiderGate->allows('delete', $taxRate))->toBeFalse()
        ->and($clientUserGate->allows('viewAny', TaxRate::class))->toBeFalse()
        ->and($clientUserGate->allows('create', TaxRate::class))->toBeFalse()
        ->and($clientUserGate->allows('view', $taxRate))->toBeFalse()
        ->and($ownerGate->allows('viewAny', ProposalLineItem::class))->toBeTrue()
        ->and($ownerGate->allows('create', ProposalLineItem::class))->toBeTrue()
        ->and($ownerGate->allows('view', $proposalLineItem))->toBeTrue()
        ->and($ownerGate->allows('update', $proposalLineItem))->toBeTrue()
        ->and($ownerGate->allows('delete', $proposalLineItem))->toBeTrue()
        ->and($outsiderGate->allows('view', $proposalLineItem))->toBeFalse()
        ->and($clientUserGate->allows('view', $proposalLineItem))->toBeFalse();
});

it('applies instagram account policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $firstAccount = SocialAccount::factory()->for($owner)->create();
    $secondAccount = SocialAccount::factory()->for($owner)->create();
    $singleAccount = SocialAccount::factory()->for($outsider)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);

    expect($ownerGate->allows('view', $firstAccount))->toBeTrue()
        ->and($ownerGate->allows('update', $firstAccount))->toBeTrue()
        ->and($ownerGate->allows('delete', $firstAccount))->toBeTrue()
        ->and($outsiderGate->allows('view', $firstAccount))->toBeFalse()
        ->and($outsiderGate->allows('update', $firstAccount))->toBeFalse()
        ->and($outsiderGate->allows('delete', $firstAccount))->toBeFalse()
        ->and($outsiderGate->allows('delete', $singleAccount))->toBeFalse();

});

it('applies client policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $otherClient = Client::factory()->for($outsider)->create();

    $matchingClientUser = ClientUser::factory()->for($client)->create();
    $mismatchedClientUser = ClientUser::factory()->for($otherClient)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $matchingClientUserGate = Gate::forUser($matchingClientUser);
    $mismatchedClientUserGate = Gate::forUser($mismatchedClientUser);

    expect($ownerGate->allows('viewAny', Client::class))->toBeTrue()
        ->and($ownerGate->allows('create', Client::class))->toBeTrue()
        ->and($ownerGate->allows('view', $client))->toBeTrue()
        ->and($ownerGate->allows('update', $client))->toBeTrue()
        ->and($ownerGate->allows('delete', $client))->toBeTrue()
        ->and($outsiderGate->allows('view', $client))->toBeFalse()
        ->and($outsiderGate->allows('update', $client))->toBeFalse()
        ->and($outsiderGate->allows('delete', $client))->toBeFalse()
        ->and($matchingClientUserGate->allows('view', $client))->toBeTrue()
        ->and($matchingClientUserGate->allows('viewAny', Client::class))->toBeFalse()
        ->and($matchingClientUserGate->allows('create', Client::class))->toBeFalse()
        ->and($matchingClientUserGate->allows('update', $client))->toBeFalse()
        ->and($matchingClientUserGate->allows('delete', $client))->toBeFalse()
        ->and($mismatchedClientUserGate->allows('view', $client))->toBeFalse();
});

it('applies campaign policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $otherClient = Client::factory()->for($outsider)->create();

    $campaign = Campaign::factory()->for($client)->create();
    $ownerProposal = Proposal::factory()->for($owner)->for($client)->create();
    $otherProposal = Proposal::factory()->for($outsider)->for($otherClient)->create();

    $matchingClientUser = ClientUser::factory()->for($client)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $clientUserGate = Gate::forUser($matchingClientUser);

    expect($ownerGate->allows('viewAny', Campaign::class))->toBeTrue()
        ->and($ownerGate->allows('create', Campaign::class))->toBeTrue()
        ->and($ownerGate->allows('view', $campaign))->toBeTrue()
        ->and($ownerGate->allows('update', $campaign))->toBeTrue()
        ->and($ownerGate->allows('delete', $campaign))->toBeTrue()
        ->and($ownerGate->allows('linkProposal', [$campaign, $ownerProposal]))->toBeTrue()
        ->and($ownerGate->allows('linkProposal', [$campaign, $otherProposal]))->toBeFalse()
        ->and($outsiderGate->allows('view', $campaign))->toBeFalse()
        ->and($outsiderGate->allows('update', $campaign))->toBeFalse()
        ->and($outsiderGate->allows('delete', $campaign))->toBeFalse()
        ->and($outsiderGate->allows('linkProposal', [$campaign, $ownerProposal]))->toBeFalse()
        ->and($clientUserGate->allows('viewAny', Campaign::class))->toBeFalse()
        ->and($clientUserGate->allows('create', Campaign::class))->toBeFalse()
        ->and($clientUserGate->allows('view', $campaign))->toBeFalse()
        ->and($clientUserGate->allows('update', $campaign))->toBeFalse()
        ->and($clientUserGate->allows('delete', $campaign))->toBeFalse()
        ->and($clientUserGate->allows('linkProposal', [$campaign, $ownerProposal]))->toBeFalse();
});

it('applies proposal policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $otherClient = Client::factory()->for($outsider)->create();

    $draftProposal = Proposal::factory()->for($owner)->for($client)->draft()->create();
    $revisedProposal = Proposal::factory()->for($owner)->for($client)->revised()->create();
    $sentProposal = Proposal::factory()->for($owner)->for($client)->sent()->create();

    $matchingClientUser = ClientUser::factory()->for($client)->create();
    $mismatchedClientUser = ClientUser::factory()->for($otherClient)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $matchingClientUserGate = Gate::forUser($matchingClientUser);
    $mismatchedClientUserGate = Gate::forUser($mismatchedClientUser);

    expect($ownerGate->allows('viewAny', Proposal::class))->toBeTrue()
        ->and($ownerGate->allows('create', Proposal::class))->toBeTrue()
        ->and($ownerGate->allows('view', $draftProposal))->toBeTrue()
        ->and($ownerGate->allows('update', $draftProposal))->toBeTrue()
        ->and($ownerGate->allows('update', $sentProposal))->toBeFalse()
        ->and($ownerGate->allows('delete', $draftProposal))->toBeTrue()
        ->and($ownerGate->allows('send', $draftProposal))->toBeTrue()
        ->and($ownerGate->allows('send', $revisedProposal))->toBeTrue()
        ->and($ownerGate->allows('send', $sentProposal))->toBeFalse()
        ->and($outsiderGate->allows('view', $draftProposal))->toBeFalse()
        ->and($outsiderGate->allows('update', $draftProposal))->toBeFalse()
        ->and($outsiderGate->allows('delete', $draftProposal))->toBeFalse()
        ->and($outsiderGate->allows('send', $draftProposal))->toBeFalse()
        ->and($matchingClientUserGate->allows('viewAny', Proposal::class))->toBeTrue()
        ->and($matchingClientUserGate->allows('view', $draftProposal))->toBeTrue()
        ->and($matchingClientUserGate->allows('create', Proposal::class))->toBeFalse()
        ->and($matchingClientUserGate->allows('update', $draftProposal))->toBeFalse()
        ->and($matchingClientUserGate->allows('delete', $draftProposal))->toBeFalse()
        ->and($matchingClientUserGate->allows('send', $draftProposal))->toBeFalse()
        ->and($mismatchedClientUserGate->allows('view', $draftProposal))->toBeFalse();

    expect($sentProposal->status)->toBe(ProposalStatus::Sent);
});

it('applies invoice policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $otherClient = Client::factory()->for($outsider)->create();

    $draftInvoice = Invoice::factory()->for($owner)->for($client)->draft()->create();
    $sentInvoice = Invoice::factory()->for($owner)->for($client)->sent()->create();
    $overdueInvoice = Invoice::factory()->for($owner)->for($client)->overdue()->create();

    $matchingClientUser = ClientUser::factory()->for($client)->create();
    $mismatchedClientUser = ClientUser::factory()->for($otherClient)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $matchingClientUserGate = Gate::forUser($matchingClientUser);
    $mismatchedClientUserGate = Gate::forUser($mismatchedClientUser);

    expect($ownerGate->allows('viewAny', Invoice::class))->toBeTrue()
        ->and($ownerGate->allows('create', Invoice::class))->toBeTrue()
        ->and($ownerGate->allows('view', $draftInvoice))->toBeTrue()
        ->and($ownerGate->allows('update', $draftInvoice))->toBeTrue()
        ->and($ownerGate->allows('delete', $draftInvoice))->toBeTrue()
        ->and($ownerGate->allows('send', $draftInvoice))->toBeTrue()
        ->and($ownerGate->allows('send', $sentInvoice))->toBeTrue()
        ->and($ownerGate->allows('send', $overdueInvoice))->toBeTrue()
        ->and($ownerGate->allows('update', $sentInvoice))->toBeFalse()
        ->and($ownerGate->allows('delete', $sentInvoice))->toBeFalse()
        ->and($outsiderGate->allows('view', $draftInvoice))->toBeFalse()
        ->and($outsiderGate->allows('update', $draftInvoice))->toBeFalse()
        ->and($outsiderGate->allows('delete', $draftInvoice))->toBeFalse()
        ->and($outsiderGate->allows('send', $draftInvoice))->toBeFalse()
        ->and($matchingClientUserGate->allows('viewAny', Invoice::class))->toBeTrue()
        ->and($matchingClientUserGate->allows('view', $draftInvoice))->toBeTrue()
        ->and($matchingClientUserGate->allows('create', Invoice::class))->toBeFalse()
        ->and($matchingClientUserGate->allows('update', $draftInvoice))->toBeFalse()
        ->and($matchingClientUserGate->allows('delete', $draftInvoice))->toBeFalse()
        ->and($matchingClientUserGate->allows('send', $draftInvoice))->toBeFalse()
        ->and($mismatchedClientUserGate->allows('view', $draftInvoice))->toBeFalse();

    expect($sentInvoice->status)->toBe(InvoiceStatus::Sent);
});

it('applies scheduled post policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $scheduledPost = ScheduledPost::factory()->for($owner)->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);

    expect($ownerGate->allows('viewAny', ScheduledPost::class))->toBeTrue()
        ->and($ownerGate->allows('create', ScheduledPost::class))->toBeTrue()
        ->and($ownerGate->allows('view', $scheduledPost))->toBeTrue()
        ->and($ownerGate->allows('update', $scheduledPost))->toBeTrue()
        ->and($ownerGate->allows('delete', $scheduledPost))->toBeTrue()
        ->and($outsiderGate->allows('view', $scheduledPost))->toBeFalse()
        ->and($outsiderGate->allows('update', $scheduledPost))->toBeFalse()
        ->and($outsiderGate->allows('delete', $scheduledPost))->toBeFalse();
});

it('applies instagram media policy rules', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $account = SocialAccount::factory()->for($owner)->create();
    $media = SocialAccountMedia::factory()->for($account, 'socialAccount')->create();

    $matchingClientUser = ClientUser::factory()->create();

    $ownerGate = Gate::forUser($owner);
    $outsiderGate = Gate::forUser($outsider);
    $clientUserGate = Gate::forUser($matchingClientUser);

    expect($ownerGate->allows('view', $media))->toBeTrue()
        ->and($ownerGate->allows('linkToClient', $media))->toBeTrue()
        ->and($outsiderGate->allows('view', $media))->toBeFalse()
        ->and($outsiderGate->allows('linkToClient', $media))->toBeFalse()
        ->and($clientUserGate->allows('view', $media))->toBeFalse()
        ->and($clientUserGate->allows('linkToClient', $media))->toBeFalse();
});

it('returns 403 for unauthorized instagram account view', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $account = SocialAccount::factory()->for($owner)->create();
    $uri = '/_test/policies/instagram-accounts/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($account) {
        Gate::authorize('view', $account);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized instagram media linking', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $account = SocialAccount::factory()->for($owner)->create();
    $media = SocialAccountMedia::factory()->for($account, 'socialAccount')->create();
    $uri = '/_test/policies/instagram-media/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($media) {
        Gate::authorize('linkToClient', $media);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized client view', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $uri = '/_test/policies/clients/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($client) {
        Gate::authorize('view', $client);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized campaign update', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $campaign = Campaign::factory()->for($client)->create();
    $uri = '/_test/policies/campaigns/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($campaign) {
        Gate::authorize('update', $campaign);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized proposal send', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $proposal = Proposal::factory()->for($owner)->for($client)->draft()->create();
    $uri = '/_test/policies/proposals/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($proposal) {
        Gate::authorize('send', $proposal);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized invoice update', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $invoice = Invoice::factory()->for($owner)->for($client)->draft()->create();
    $uri = '/_test/policies/invoices/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($invoice) {
        Gate::authorize('update', $invoice);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized invoice send', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $client = Client::factory()->for($owner)->create();
    $invoice = Invoice::factory()->for($owner)->for($client)->draft()->create();
    $uri = '/_test/policies/invoices-send/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($invoice) {
        Gate::authorize('send', $invoice);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});

it('returns 403 for unauthorized scheduled post view', function (): void {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $scheduledPost = ScheduledPost::factory()->for($owner)->create();
    $uri = '/_test/policies/scheduled-posts/'.Str::uuid();

    Route::middleware(['web', 'auth'])->get($uri, function () use ($scheduledPost) {
        Gate::authorize('view', $scheduledPost);

        return response()->noContent();
    });

    $this->actingAs($outsider)
        ->get($uri)
        ->assertForbidden();
});
