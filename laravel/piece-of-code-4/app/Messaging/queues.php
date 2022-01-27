<?php

use Vinelab\Bowler\Facades\Registrator;

Registrator::subscriber('instagram-scheduler-campaign-posts-info',
    'App\Messaging\Handlers\SchedulerCampaignPostsInfoMessageHandler',
    [
        'campaign.instagram.posts',
    ],
    'tracking'
);

Registrator::subscriber('instagram-audience-accounts-pub-sub',
    'App\Messaging\Handlers\CachedDataRemovalHandler',
    [
        'talent-instagram-account-disconnected',
        'talent-instagram-account-access-revoked',
    ]
);

Registrator::subscriber('instagram:fetch.insights.public',
    'App\Messaging\Handlers\FetchInsightsWithPublicConnectionMessageHandler',
    [
        'instagram.connected.public',
    ],
    'social_account_events'
);

Registrator::subscriber('instagram:fetch.insights.authorized',
    'App\Messaging\Handlers\FetchInsightsWithAuthorizedConnectionMessageHandler',
    [
        'instagram.connected.authorized',
    ],
    'social_account_events'
);
