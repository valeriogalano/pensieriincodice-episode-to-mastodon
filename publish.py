import json
import logging
import os
import re
import sys
import xml.etree.ElementTree as ET

import requests

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("mastodon")

ITUNES_NS = 'http://www.itunes.com/dtds/podcast-1.0.dtd'
PUBLISHED_FILE_TEMPLATE = './published_episodes_{podcast_id}.txt'


def load_podcasts_config(config_file: str) -> list:
    if not os.path.exists(config_file):
        raise FileNotFoundError(f"File di configurazione non trovato: {config_file}")
    with open(config_file, 'r') as f:
        return json.load(f)


def fetch_last_episode(feed_url: str) -> dict:
    response = requests.get(feed_url)
    response.raise_for_status()

    root = ET.fromstring(response.content)
    item = root.find('./channel/item')

    if item is None:
        raise Exception("Nessun episodio trovato nel feed")

    title = item.findtext('title', '').strip()
    link = item.findtext('link', '').strip()

    if not title or not link:
        raise Exception(f"Titolo o link mancante: {title=} {link=}")

    keywords_el = item.find(f'{{{ITUNES_NS}}}keywords')
    hashtags = ''
    if keywords_el is not None and keywords_el.text:
        hashtags = ' '.join(
            '#' + re.sub(r'\s+', '', kw.strip())
            for kw in keywords_el.text.split(',')
            if kw.strip()
        )

    return {'title': title, 'link': link, 'hashtags': hashtags}


def is_published(link: str, podcast_id: str) -> bool:
    file_path = PUBLISHED_FILE_TEMPLATE.format(podcast_id=podcast_id)
    if not os.path.exists(file_path):
        return False
    with open(file_path, 'r') as f:
        return link in f.read()


def mark_as_published(link: str, podcast_id: str) -> None:
    file_path = PUBLISHED_FILE_TEMPLATE.format(podcast_id=podcast_id)
    logger.info(f"Segnato come pubblicato: {link}")
    with open(file_path, 'a') as f:
        f.write(f"{link}\n")


def publish_to_mastodon(episode: dict, api_url: str, token: str, template: str) -> None:
    content = template \
        .replace('{title}', episode['title']) \
        .replace('{link}', episode['link']) \
        .replace('{hashtags}', episode['hashtags'])

    logger.info(f"Pubblicazione su Mastodon: {content[:80]}...")

    response = requests.post(
        api_url,
        headers={'Authorization': f'Bearer {token}'},
        data={'status': content}
    )

    if response.status_code not in (200, 201):
        raise Exception(f"Errore Mastodon API {response.status_code}: {response.text}")

    logger.info("Post pubblicato con successo!")


if __name__ == "__main__":
    token = os.environ['MASTODON_TOKEN']
    api_url = os.environ.get('MASTODON_API_URL', 'https://mastodon.uno/api/v1/statuses')

    podcasts = load_podcasts_config('./podcasts.json')
    logger.info(f"Trovati {len(podcasts)} podcast da processare")

    for podcast in podcasts:
        logger.info(f"=== {podcast['name']} ===")

        episode = fetch_last_episode(podcast['feed_url'])
        logger.info(f"Ultimo episodio: {episode['link']}")

        if is_published(episode['link'], podcast['id']):
            logger.info("Episodio già pubblicato, skip.")
            continue

        publish_to_mastodon(episode, api_url, token, podcast['template'])
        mark_as_published(episode['link'], podcast['id'])

    logger.info("Tutti i podcast processati.")
