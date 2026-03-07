<?php

use App\Enums\AccountType;
use App\Enums\AnalyticsPeriod;
use App\Enums\AnalyticsTopContentSort;
use App\Enums\BillingUnitType;
use App\Enums\CatalogPlanSort;
use App\Enums\CatalogPlanStatusFilter;
use App\Enums\CatalogProductSort;
use App\Enums\CatalogProductStatusFilter;
use App\Enums\CatalogSourceType;
use App\Enums\ClientType;
use App\Enums\DemographicType;
use App\Enums\InvoiceStatus;
use App\Enums\MediaType;
use App\Enums\PlatformType;
use App\Enums\ProposalStatus;
use App\Enums\ScheduledPostStatus;
use App\Enums\SocialNetwork;
use App\Enums\SyncStatus;
use App\Services\Auth\SocialiteClient;
use App\Services\SocialMedia\Instagram\Client as InstagramClient;
use App\Services\SocialMedia\Tiktok\Client as TikTokClient;

it('defines all media type enum cases', function (): void {
    expect(MediaType::cases())->toHaveCount(3)
        ->and(MediaType::Post->value)->toBe('post')
        ->and(MediaType::Reel->value)->toBe('reel')
        ->and(MediaType::Story->value)->toBe('story');
});

it('provides media type filters including all option', function (): void {
    expect(MediaType::filters())->toBe([
        'all',
        'post',
        'reel',
        'story',
    ]);
});

it('parses image and carousel media as post', function (): void {
    expect(MediaType::parse(['media_type' => 'IMAGE']))->toBe(MediaType::Post)
        ->and(MediaType::parse(['media_type' => 'CAROUSEL_ALBUM']))->toBe(MediaType::Post);
});

it('parses reels media product type as reel', function (): void {
    expect(MediaType::parse([
        'media_type' => 'VIDEO',
        'media_product_type' => 'REELS',
    ]))->toBe(MediaType::Reel);
});

it('parses video permalink containing reel path as reel', function (): void {
    expect(MediaType::parse([
        'media_type' => 'VIDEO',
        'permalink' => 'https://instagram.com/reel/abc123',
    ]))->toBe(MediaType::Reel);
});

it('parses non-reel video as post', function (): void {
    expect(MediaType::parse([
        'media_type' => 'VIDEO',
        'media_product_type' => 'FEED',
        'permalink' => 'https://instagram.com/p/abc123',
    ]))->toBe(MediaType::Post);
});

it('parses unknown media type as post', function (): void {
    expect(MediaType::parse(['media_type' => 'STORY']))->toBe(MediaType::Post)
        ->and(MediaType::parse([]))->toBe(MediaType::Post);
});

it('defines all client type enum cases', function (): void {
    expect(ClientType::cases())->toHaveCount(2)
        ->and(ClientType::Brand->value)->toBe('brand')
        ->and(ClientType::Individual->value)->toBe('individual');
});

it('provides client type values and filters', function (): void {
    expect(ClientType::values())->toBe([
        'brand',
        'individual',
    ])->and(ClientType::filters())->toBe([
        'all',
        'brand',
        'individual',
    ]);
});

it('provides client type labels and badge classes', function (): void {
    expect(ClientType::Brand->label())->toBe('Brand')
        ->and(ClientType::Individual->label())->toBe('Individual')
        ->and(ClientType::Brand->badgeClasses())->toContain('bg-sky-100')
        ->and(ClientType::Individual->badgeClasses())->toContain('bg-emerald-100');
});

it('defines all proposal status enum cases', function (): void {
    expect(ProposalStatus::cases())->toHaveCount(5)
        ->and(ProposalStatus::Draft->value)->toBe('draft')
        ->and(ProposalStatus::Sent->value)->toBe('sent')
        ->and(ProposalStatus::Approved->value)->toBe('approved')
        ->and(ProposalStatus::Rejected->value)->toBe('rejected')
        ->and(ProposalStatus::Revised->value)->toBe('revised');
});

it('provides proposal status values and filters', function (): void {
    expect(ProposalStatus::values())->toBe([
        'draft',
        'sent',
        'approved',
        'rejected',
        'revised',
    ])->and(ProposalStatus::filters())->toBe([
        'all',
        'draft',
        'sent',
        'approved',
        'rejected',
        'revised',
    ]);
});

it('provides client proposal status values and filters', function (): void {
    expect(ProposalStatus::clientViewableValues())->toBe([
        'sent',
        'approved',
        'rejected',
        'revised',
    ])->and(ProposalStatus::clientFilters())->toBe([
        'all',
        'sent',
        'approved',
        'rejected',
        'revised',
    ]);
});

it('provides proposal status labels and badge classes', function (): void {
    expect(ProposalStatus::Draft->label())->toBe('Draft')
        ->and(ProposalStatus::Sent->label())->toBe('Sent')
        ->and(ProposalStatus::Approved->label())->toBe('Approved')
        ->and(ProposalStatus::Rejected->label())->toBe('Rejected')
        ->and(ProposalStatus::Revised->label())->toBe('Revised')
        ->and(ProposalStatus::Draft->badgeClasses())->toContain('bg-zinc-100')
        ->and(ProposalStatus::Sent->badgeClasses())->toContain('bg-blue-100')
        ->and(ProposalStatus::Approved->badgeClasses())->toContain('bg-emerald-100')
        ->and(ProposalStatus::Rejected->badgeClasses())->toContain('bg-rose-100')
        ->and(ProposalStatus::Revised->badgeClasses())->toContain('bg-amber-100');
});

it('defines all invoice status enum cases', function (): void {
    expect(InvoiceStatus::cases())->toHaveCount(5)
        ->and(InvoiceStatus::Draft->value)->toBe('draft')
        ->and(InvoiceStatus::Sent->value)->toBe('sent')
        ->and(InvoiceStatus::Paid->value)->toBe('paid')
        ->and(InvoiceStatus::Overdue->value)->toBe('overdue')
        ->and(InvoiceStatus::Cancelled->value)->toBe('cancelled');
});

it('provides pending invoice statuses', function (): void {
    expect(InvoiceStatus::pendingValues())->toBe([
        'sent',
        'overdue',
    ]);
});

it('provides invoice status filter values', function (): void {
    expect(InvoiceStatus::values())->toBe([
        'draft',
        'sent',
        'paid',
        'overdue',
        'cancelled',
    ])->and(InvoiceStatus::filters())->toBe([
        'all',
        'draft',
        'sent',
        'paid',
        'overdue',
        'cancelled',
    ]);
});

it('provides invoice status labels and badge classes', function (): void {
    expect(InvoiceStatus::Draft->label())->toBe('Draft')
        ->and(InvoiceStatus::Sent->label())->toBe('Sent')
        ->and(InvoiceStatus::Paid->label())->toBe('Paid')
        ->and(InvoiceStatus::Overdue->label())->toBe('Overdue')
        ->and(InvoiceStatus::Cancelled->label())->toBe('Cancelled')
        ->and(InvoiceStatus::Draft->badgeClasses())->toContain('bg-zinc-100')
        ->and(InvoiceStatus::Sent->badgeClasses())->toContain('bg-sky-100')
        ->and(InvoiceStatus::Paid->badgeClasses())->toContain('bg-emerald-100')
        ->and(InvoiceStatus::Overdue->badgeClasses())->toContain('bg-rose-100')
        ->and(InvoiceStatus::Cancelled->badgeClasses())->toContain('bg-zinc-100');
});

it('defines all scheduled post status enum cases', function (): void {
    expect(ScheduledPostStatus::cases())->toHaveCount(3)
        ->and(ScheduledPostStatus::Planned->value)->toBe('planned')
        ->and(ScheduledPostStatus::Published->value)->toBe('published')
        ->and(ScheduledPostStatus::Cancelled->value)->toBe('cancelled');
});

it('defines all demographic type enum cases', function (): void {
    expect(DemographicType::cases())->toHaveCount(4)
        ->and(DemographicType::Age->value)->toBe('age')
        ->and(DemographicType::Gender->value)->toBe('gender')
        ->and(DemographicType::City->value)->toBe('city')
        ->and(DemographicType::Country->value)->toBe('country');
});

it('defines all account type enum cases', function (): void {
    expect(AccountType::cases())->toHaveCount(2)
        ->and(AccountType::Business->value)->toBe('business')
        ->and(AccountType::Creator->value)->toBe('creator');
});

it('defines all social network enum cases', function (): void {
    expect(SocialNetwork::cases())->toHaveCount(4)
        ->and(SocialNetwork::Instagram->value)->toBe('instagram')
        ->and(SocialNetwork::Tiktok->value)->toBe('tiktok')
        ->and(SocialNetwork::Youtube->value)->toBe('youtube')
        ->and(SocialNetwork::Twitch->value)->toBe('twitch');
});

it('provides account connection redirect routes for social networks', function (): void {
    expect(SocialNetwork::Instagram->accountConnectionRedirectRoute())->toBe('instagram-accounts.index')
        ->and(SocialNetwork::Tiktok->accountConnectionRedirectRoute())->toBe('dashboard');
});

it('builds the instagram socialite client from social network enum', function (): void {
    $instagramClient = Mockery::mock(InstagramClient::class);
    app()->bind(InstagramClient::class, fn (): InstagramClient => $instagramClient);

    $client = SocialNetwork::Instagram->socialiteClient('access-token', 'user-id');

    expect($client)->toBe($instagramClient);
});

it('builds the tiktok socialite client from social network enum', function (): void {
    $tiktokClient = Mockery::mock(TikTokClient::class);
    app()->bind(TikTokClient::class, fn (): TikTokClient => $tiktokClient);

    $client = SocialNetwork::Tiktok->socialiteClient('access-token', 'user-id');

    expect($client)->toBe($tiktokClient)
        ->and($client)->toBeInstanceOf(SocialiteClient::class);
});

it('throws when socialite client is requested for unsupported social network', function (): void {
    SocialNetwork::Youtube->socialiteClient('access-token', 'user-id');
})->throws(\LogicException::class, 'YouTube social login client is not configured.');

it('defines all sync status enum cases', function (): void {
    expect(SyncStatus::cases())->toHaveCount(3)
        ->and(SyncStatus::Idle->value)->toBe('idle')
        ->and(SyncStatus::Syncing->value)->toBe('syncing')
        ->and(SyncStatus::Failed->value)->toBe('failed');
});

it('defines analytics period options and default', function (): void {
    expect(AnalyticsPeriod::default())->toBe(AnalyticsPeriod::ThirtyDays)
        ->and(AnalyticsPeriod::values())->toBe([
            '7_days',
            '30_days',
            '90_days',
            'all',
        ])
        ->and(AnalyticsPeriod::options())->toBe([
            '7_days' => '7 Days',
            '30_days' => '30 Days',
            '90_days' => '90 Days',
            'all' => 'All Time',
        ]);
});

it('defines analytics top content sort options and default', function (): void {
    expect(AnalyticsTopContentSort::default())->toBe(AnalyticsTopContentSort::Engagement)
        ->and(AnalyticsTopContentSort::values())->toBe([
            'engagement',
            'reach',
        ])
        ->and(AnalyticsTopContentSort::options())->toBe([
            'engagement' => 'Top by Engagement',
            'reach' => 'Top by Reach',
        ]);
});

it('provides media type labels and ui metadata', function (): void {
    expect(MediaType::Post->label())->toBe('Post')
        ->and(MediaType::Reel->label())->toBe('Reel')
        ->and(MediaType::Story->label())->toBe('Story')
        ->and(MediaType::Post->pluralLabel())->toBe('Posts')
        ->and(MediaType::Reel->pluralLabel())->toBe('Reels')
        ->and(MediaType::Story->pluralLabel())->toBe('Stories')
        ->and(MediaType::Post->badgeClasses())->toContain('bg-sky-100')
        ->and(MediaType::Reel->badgeClasses())->toContain('bg-violet-100')
        ->and(MediaType::Story->badgeClasses())->toContain('bg-amber-100')
        ->and(MediaType::Post->chartColor())->toBe('#3b82f6')
        ->and(MediaType::Reel->chartColor())->toBe('#8b5cf6')
        ->and(MediaType::Story->chartColor())->toBe('#f59e0b');
});

it('defines all platform type enum cases and labels', function (): void {
    expect(PlatformType::cases())->toHaveCount(2)
        ->and(PlatformType::Instagram->value)->toBe('instagram')
        ->and(PlatformType::TikTok->value)->toBe('tiktok')
        ->and(PlatformType::values())->toBe([
            'instagram',
            'tiktok',
        ])
        ->and(PlatformType::Instagram->label())->toBe('Instagram')
        ->and(PlatformType::TikTok->label())->toBe('TikTok');
});

it('defines all catalog source type enum cases and labels', function (): void {
    expect(CatalogSourceType::cases())->toHaveCount(3)
        ->and(CatalogSourceType::Product->value)->toBe('product')
        ->and(CatalogSourceType::Plan->value)->toBe('plan')
        ->and(CatalogSourceType::Custom->value)->toBe('custom')
        ->and(CatalogSourceType::values())->toBe([
            'product',
            'plan',
            'custom',
        ])
        ->and(CatalogSourceType::Product->label())->toBe('Product')
        ->and(CatalogSourceType::Plan->label())->toBe('Plan')
        ->and(CatalogSourceType::Custom->label())->toBe('Custom');
});

it('defines all billing unit type enum cases and labels', function (): void {
    expect(BillingUnitType::cases())->toHaveCount(2)
        ->and(BillingUnitType::Deliverable->value)->toBe('deliverable')
        ->and(BillingUnitType::Package->value)->toBe('package')
        ->and(BillingUnitType::values())->toBe([
            'deliverable',
            'package',
        ])
        ->and(BillingUnitType::Deliverable->label())->toBe('Deliverable')
        ->and(BillingUnitType::Package->label())->toBe('Package');
});

it('defines catalog product status filter options', function (): void {
    expect(CatalogProductStatusFilter::default())->toBe(CatalogProductStatusFilter::Active)
        ->and(CatalogProductStatusFilter::values())->toBe([
            'active',
            'archived',
        ])
        ->and(CatalogProductStatusFilter::options())->toBe([
            'active' => 'Active',
            'archived' => 'Archived',
        ])
        ->and(CatalogProductStatusFilter::Active->activeValue())->toBeTrue()
        ->and(CatalogProductStatusFilter::Archived->activeValue())->toBeFalse();
});

it('defines catalog product sort options', function (): void {
    expect(CatalogProductSort::default())->toBe(CatalogProductSort::Newest)
        ->and(CatalogProductSort::values())->toBe([
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'price_asc',
            'price_desc',
        ])
        ->and(CatalogProductSort::options())->toBe([
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'name_asc' => 'Name (A-Z)',
            'name_desc' => 'Name (Z-A)',
            'price_asc' => 'Price (Low to High)',
            'price_desc' => 'Price (High to Low)',
        ]);
});

it('defines catalog plan status filter options', function (): void {
    expect(CatalogPlanStatusFilter::default())->toBe(CatalogPlanStatusFilter::Active)
        ->and(CatalogPlanStatusFilter::values())->toBe([
            'active',
            'archived',
        ])
        ->and(CatalogPlanStatusFilter::options())->toBe([
            'active' => 'Active',
            'archived' => 'Archived',
        ])
        ->and(CatalogPlanStatusFilter::Active->activeValue())->toBeTrue()
        ->and(CatalogPlanStatusFilter::Archived->activeValue())->toBeFalse();
});

it('defines catalog plan sort options', function (): void {
    expect(CatalogPlanSort::default())->toBe(CatalogPlanSort::Newest)
        ->and(CatalogPlanSort::values())->toBe([
            'newest',
            'oldest',
            'name_asc',
            'name_desc',
            'bundle_price_asc',
            'bundle_price_desc',
            'items_desc',
            'items_asc',
        ])
        ->and(CatalogPlanSort::options())->toBe([
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'name_asc' => 'Name (A-Z)',
            'name_desc' => 'Name (Z-A)',
            'bundle_price_asc' => 'Bundle Price (Low to High)',
            'bundle_price_desc' => 'Bundle Price (High to Low)',
            'items_desc' => 'Most Items',
            'items_asc' => 'Fewest Items',
        ]);
});
