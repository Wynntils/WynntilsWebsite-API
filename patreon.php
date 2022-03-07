<?php

$config = json_decode(file_get_contents('config.json'), true);

// insert your patreon webhook secret here!
$secret_webhook_id = $config['patreon_secret'];

function postToDiscord($data)
{
    global $config;
    $curl = curl_init($config['discord_webhook']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    return curl_exec($curl);
}

// this saves the post data you get on your endpoint
$data = @file_get_contents('php://input');
// decode json post data to arrays
$event_data = json_decode($data, true);


// also get the headers patreon sends
$X_Patreon_Event = $_SERVER['HTTP_X_PATREON_EVENT'];
$X_Patreon_Signature = $_SERVER['HTTP_X_PATREON_SIGNATURE'];

// save data for debugging
if (!is_dir('responses/')) {
    mkdir('responses/');
}
file_put_contents(
    'responses/response_' . date('dmY') . '_' . $X_Patreon_Event . '_' . uniqid('', true) . '.json',
    $data
);


// verify signature
$signature = hash_hmac('md5', $data, $secret_webhook_id);
if (!hash_equals($X_Patreon_Signature, $signature)) {
    die("Patreon Signature didn't match, got: " . $X_Patreon_Signature . " expected: " . $signature);
}
// get all the user info
$pledge_amount = $event_data['data']['attributes']['will_pay_amount_cents'];
$pledge_status = $event_data['data']['attributes']['last_charge_status'];
$patron_id = $event_data['data']['relationships']['user']['data']['id'];
$campaign_id = $event_data['data']['relationships']['campaign']['data']['id'];
foreach ($event_data['included'] as $included_data) {
    if ($included_data['type'] === 'user' && $included_data['id'] == $patron_id) {
        $user_data = $included_data;
    }
    if ($included_data['type'] === 'campaign' && $included_data['id'] == $campaign_id) {
        $campaign_data = $included_data;
    }
}

$patron_url = $user_data['attributes']['url'];
$patron_image = $user_data['attributes']['image_url'];
$patron_fullname = $user_data['attributes']['full_name'];
$patron_count = $campaign_data['attributes']['patron_count'];
$discord = $user_data['attributes']['social_connections']['discord'];

if ($discord !== null) {
    $discord = $discord['user_id'];
}
// send event to discord

switch ($X_Patreon_Event) {
    case 'members:pledge:create';
        $color = 39961;
        break;
    case 'members:pledge:delete';
        $color = 10223616;
        break;
    case 'members:update';
    case 'members:pledge:update';
        $color = 12377861;
        break;
    default:
        die;
}

$data = [
    "content" => ($discord !== null ? " <@{$discord}>" : null),
    "embeds" => [
        [
            "color" => $color,
            "description" => null,
            "fields" => [
                [
                    "name" => "Amount",
                    "value" => "$" . number_format(($pledge_amount / 100), 2, '.', ' '),
                    "inline" => true
                ],
                [
                    "name" => "Payment Status",
                    "value" => $pledge_status,
                    "inline" => true
                ]
            ],
            "author" => [
                "name" => $patron_fullname,
                "url" => $patron_url,
                "icon_url" => $patron_image
            ],
            "footer" => [
                "text" => $X_Patreon_Event . " | Total Patrons: {$patron_count}"
            ]
        ]
    ]
];

postToDiscord($data);
