<?php

/**
 * Fetch last episode from podcast feed
 */
function fetch_last_episode($feed_url): SimpleXMLElement|false
{
    $feed = simplexml_load_file($feed_url);
    return $feed->channel->item[0];
}

/**
 * Publish last episode to social Mastodon
 */
function publish_to_mastodon($last_episode, $mastodon_url, $mastodon_token, $template): false|string
{
    $content = str_replace(
        ['{title}', '{link}'],
        [escape($last_episode->title), escape($last_episode->link)],
        $template
    );

    $data = array(
        'status' => $content,
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                "Authorization: Bearer $mastodon_token\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $response = file_get_contents($mastodon_url, false, stream_context_create($options));
    // log error
    if ($response === false) {
        error_log(error_get_last());
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
    $link = $last_episode->link;

    file_put_contents($file_path, "$link\n", FILE_APPEND);
}

/**
 * Search episode link into file
 */
function is_just_published($last_episode, $file_path): bool
{
    $link = $last_episode->link;
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
    }
}