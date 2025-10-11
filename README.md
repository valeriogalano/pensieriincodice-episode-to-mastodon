# pensieriincodice-episode-to-mastodon

## Description
This GitHub Action posts to Mastodon the latest episode of your podcasts. It supports multiple podcasts with separate tracking and custom message templates for each.

## Setup

### 1. Clone the repository
```bash
git clone https://github.com/YOUR_USERNAME/pensieriincodice-episode-to-mastodon.git
cd pensieriincodice-episode-to-mastodon
```

### 2. Configure GitHub Actions Secrets
Go to your repository **Settings > Secrets and variables > Actions** and add the following **Secret**:

- `MASTODON_TOKEN`: Your Mastodon API access token
  - Get it from your Mastodon instance: **Settings > Development > New Application**
  - Grant `write:statuses` permission
  - Copy the generated access token

### 3. Configure GitHub Actions Variables
In the same section, under the **Variables** tab, add:

- `PODCAST1_RSS_URL`: RSS feed URL for your first podcast
- `PODCAST1_TEMPLATE`: Message template for the first podcast
- `PODCAST2_RSS_URL`: RSS feed URL for your second podcast
- `PODCAST2_TEMPLATE`: Message template for the second podcast

### 4. Message Templates
Valid placeholders for templates: `{title}`, `{link}`

Example templates:
```
🎙️ Nuovo episodio di Pensieri in Codice!

{title}

Ascoltalo qui: {link}

#Podcast #Tech
```

**Note:** Mastodon supports plain text and Markdown. Add hashtags for better discoverability.

### 5. Mastodon Instance Configuration
The default Mastodon instance is `https://mastodon.uno`. To use a different instance, edit the `$mastodon_url` variable in `publish.php`:

```php
$mastodon_url = 'https://YOUR_INSTANCE/api/v1/statuses';
```

### 6. Adding More Podcasts
To add more podcasts, edit `.github/workflows/cron.yml` and add additional podcast configurations in the "Create podcasts config" step:

```php
[
  "id" => "mypodcast",
  "name" => "My Podcast",
  "feed_url" => getenv("PODCAST3_RSS_URL"),
  "template" => getenv("PODCAST3_TEMPLATE")
]
```

Then add the corresponding GitHub Actions variables (`PODCAST3_RSS_URL`, `PODCAST3_TEMPLATE`).

## How It Works
- The workflow runs automatically every 6 hours
- Each podcast is tracked separately in `published_episodes_{podcast_id}.txt`
- Only new episodes are published
- The workflow can also be triggered manually from the Actions tab