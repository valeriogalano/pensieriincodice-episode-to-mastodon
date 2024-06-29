<?php

/**
 * Fetch last episode from podcast feed
 */
function fetch_last_episode(string $feed_url): SimpleXMLElement|false
{
    $feed = simplexml_load_file($feed_url);

    if ($feed === false) {
        error_log('Error fetching feed: ' . print_r(error_get_last(), true));
        exit(1);
    }

    $item = $feed->channel->item[0];

    if ($item === null) {
        error_log('Error fetching last episode: ' . print_r(error_get_last(), true));
        exit(1);
    }

    return $item;
}

/**
 * Publish last episode to social Mastodon
 */
function publish_to_mastodon(SimpleXMLElement $last_episode, string $mastodon_url, string $mastodon_token, string $template): false|string
{
    if (empty($title = $last_episode->title) || empty($link = $last_episode->link)) {
        error_log('Error fetching last episode: ' . print_r(error_get_last(), true));
        exit(1);
    }

    $content = str_replace(
        ['{title}', '{link}'],
        [escape($title), escape($link)],
        $template
    );

    // log content
    echo "Publishing to Mastodon: $content\n";

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                "Authorization: Bearer $mastodon_token\r\n",
            'method' => 'POST',
            'content' => http_build_query([
                'status' => $content,
            ]),
        ),
    );

    $response = file_get_contents($mastodon_url, false, stream_context_create($options));
    // log error
    if ($response === false) {
        error_log('Error publishing to Mastodon: ' . print_r(error_get_last(), true));
        exit(1);
    }
    return $response;
}

/**
 * @param array|string $string
 * @return array|string|string[]
 */
function escape(array|string $string): string|array
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Add episode link into file
 */
function mark_as_published($last_episode, $file_path): void
{
    if (($link = $last_episode->link) === null) {
        error_log('Error fetching last episode: ' . print_r(error_get_last(), true));
        exit(1);
    }

    echo "Marking as published: $link\n";

    file_put_contents($file_path, "$link\n", FILE_APPEND);
}

/**
 * Search episode link into file
 */
function is_just_published($last_episode, $file_path): bool
{
    if (($link = $last_episode->link) === null) {
        error_log('Error fetching last episode: ' . print_r(error_get_last(), true));
        exit(1);
    }

    $content = file_get_contents($file_path);

    return str_contains($content, $link);
}

$feed_url = getenv('PODCAST_RSS_URL');
$mastodon_url = 'https://mastodon.uno/api/v1/statuses';
$mastodon_token = getenv('MASTODON_TOKEN');
$template = getenv('MASTODON_MESSAGE_TEMPLATE');
$file_path = './published_episodes.txt';

if ($last_episode = fetch_last_episode($feed_url)) {
    echo "Last episode fetched successfully: " . $last_episode->link . "\n";
}

if (!is_just_published($last_episode, $file_path)) {
    if (publish_to_mastodon($last_episode, $mastodon_url, $mastodon_token, $template)) {
        mark_as_published($last_episode, $file_path);
    } else {
        echo "Error publishing to Mastodon\n";
    }
} else {
    echo "Episode already published\n";
}