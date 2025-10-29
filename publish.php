<?php

/**
 * Load podcasts configuration
 */
function load_podcasts_config(string $config_file): array
{
	if (!file_exists($config_file)) {
		error_log("Configuration file not found: $config_file");
		exit(1);
	}

	$config = json_decode(file_get_contents($config_file), true);

	if ($config === null) {
		error_log('Error parsing configuration file: ' . json_last_error_msg());
		exit(1);
	}

	return $config;
}

/**
 * Get tracking file path for a specific podcast
 */
function get_tracking_file(string $podcast_id): string
{
	return "./published_episodes_{$podcast_id}.txt";
}

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
	// Extract hashtags from itunes:keywords
	$hashtags = '';
	$itunes_ns = $last_episode->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
	if (isset($itunes_ns->keywords)) {
		$keywords = (string)$itunes_ns->keywords;
		if (!empty($keywords)) {
			$keywords_array = array_map('trim', explode(',', $keywords));
			$hashtags_array = array_map(function($keyword) {
				return '#' . str_replace(' ', '', $keyword);
			}, $keywords_array);
			$hashtags = implode(' ', $hashtags_array);
		}
	}

    $content = str_replace(
	    ['{title}', '{link}', '{hashtags}'],
		[(string)$title, (string)$link, (string)$hashtags],
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

	// Create file if it doesn't exist
	if (!file_exists($file_path)) {
		touch($file_path);
	}

    $content = file_get_contents($file_path);

    return str_contains($content, $link);
}

// Main execution
$mastodon_url = 'https://mastodon.uno/api/v1/statuses';
$mastodon_token = getenv('MASTODON_TOKEN');
$config_file = './podcasts.json';

// Load all podcasts configuration
$podcasts = load_podcasts_config($config_file);

echo "Found " . count($podcasts) . " podcast(s) to process\n\n";

// Process each podcast
foreach ($podcasts as $podcast) {
	echo "========================================\n";
	echo "Processing: {$podcast['name']}\n";
	echo "========================================\n";

	$feed_url = $podcast['feed_url'];
	$template = $podcast['template'];
	$file_path = get_tracking_file($podcast['id']);

	// Fetch last episode
    if ($last_episode = fetch_last_episode($feed_url)) {
		echo "Last episode fetched: " . $last_episode->link . "\n";
	} else {
		echo "Error fetching episode for {$podcast['name']}, skipping...\n\n";
		continue;
	}

	// Check if already published
	if (!is_just_published($last_episode, $file_path)) {
		if (publish_to_mastodon($last_episode, $mastodon_url, $mastodon_token, $template)) {
			mark_as_published($last_episode, $file_path);
			echo "✓ Successfully published!\n";
		} else {
			echo "✗ Error publishing to Mastodon\n";
		}
	} else {
		echo "Episode already published\n";
	}

	echo "\n";
}

echo "All podcasts processed!\n";