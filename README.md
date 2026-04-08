<div align="center">
  <img src="https://cdn.pensieriincodice.it/images/pensieriincodice-locandina.png" alt="Logo Progetto" width="150"/>
  <h1>Pensieri In Codice — Episode to Mastodon</h1>
  <p>GitHub Action che pubblica automaticamente i nuovi episodi del podcast su Mastodon.</p>
  <p>
    <img src="https://img.shields.io/github/stars/valeriogalano/pensieriincodice-episode-to-mastodon?style=for-the-badge" alt="GitHub Stars"/>
    <img src="https://img.shields.io/github/forks/valeriogalano/pensieriincodice-episode-to-mastodon?style=for-the-badge" alt="GitHub Forks"/>
    <img src="https://img.shields.io/github/last-commit/valeriogalano/pensieriincodice-episode-to-mastodon?style=for-the-badge" alt="Last Commit"/>
    <a href="https://pensieriincodice.it/sostieni" target="_blank" rel="noopener noreferrer">
      <img src="https://img.shields.io/badge/sostieni-Pensieri_in_codice-fb6400?style=for-the-badge" alt="Sostieni Pensieri in codice"/>
    </a>
  </p>
</div>

---

## Come funziona

Il workflow viene eseguito ogni 6 ore. Per ogni podcast configurato, controlla il feed RSS alla ricerca di nuovi episodi. Gli episodi già pubblicati vengono tracciati in un file `published_episodes_{podcast_id}.txt` per evitare duplicati. Il workflow può essere attivato anche manualmente dalla scheda Actions.

---

## Requisiti

- Un account Mastodon con access token (`write:statuses`)
- Uno o più feed RSS di podcast

---

## Installazione e configurazione

### 1. Clona la repository

```bash
git clone https://github.com/YOUR_USERNAME/pensieriincodice-episode-to-mastodon.git
cd pensieriincodice-episode-to-mastodon
```

### 2. Configura i secrets di GitHub Actions

In **Settings → Secrets and variables → Actions**, aggiungi il seguente **Secret**:

| Secret | Descrizione |
|---|---|
| `MASTODON_TOKEN` | Token di accesso Mastodon (da Impostazioni → Sviluppo → Nuova applicazione) |

### 3. Configura le variabili di GitHub Actions

Nella stessa sezione, sotto la scheda **Variables**, aggiungi:

| Variabile | Descrizione |
|---|---|
| `PODCAST1_RSS_URL` | URL del feed RSS del primo podcast |
| `PODCAST1_TEMPLATE` | Template del messaggio per il primo podcast |
| `PODCAST2_RSS_URL` | URL del feed RSS del secondo podcast |
| `PODCAST2_TEMPLATE` | Template del messaggio per il secondo podcast |

### 4. Template del messaggio

I placeholder disponibili sono `{title}` e `{link}`. Esempio:

```
🎙️ Nuovo episodio di Pensieri in Codice!

{title}

Ascoltalo qui: {link}

#Podcast #Tech
```

### 5. Configurazione dell'istanza Mastodon

L'istanza predefinita è `https://mastodon.uno`. Per usarne una diversa, modifica la variabile `$mastodon_url` in `publish.php`:

```php
$mastodon_url = 'https://TUA_ISTANZA/api/v1/statuses';
```

### 6. Aggiungere altri podcast

Modifica `.github/workflows/cron.yml` e aggiungi nuove configurazioni nello step "Create podcasts config":

```php
[
  "id" => "miopodcast",
  "name" => "Il mio podcast",
  "feed_url" => getenv("PODCAST3_RSS_URL"),
  "template" => getenv("PODCAST3_TEMPLATE")
]
```

Aggiungi poi le variabili corrispondenti (`PODCAST3_RSS_URL`, `PODCAST3_TEMPLATE`) in GitHub Actions.

---

## Contributi

Se noti qualche problema o hai suggerimenti, sentiti libero di aprire una **Issue** e successivamente una **Pull Request**. Ogni contributo è ben accetto!

---

## Importante

Vorremmo mantenere questo repository aperto e gratuito per tutti, ma lo scraping del contenuto di questo repository **NON È CONSENTITO**. Se ritieni che questo lavoro ti sia utile e vuoi utilizzare qualche risorsa, ti preghiamo di citare come fonte il podcast e/o questo repository.