# mediawiki-weather-widget

A weather widget for MediaWiki that displays live data from a personal weather station (PWS) registered on [Weather Underground / Wunderground](https://www.wunderground.com).

Built and tested on a **Synology NAS** running the MediaWiki package (v1.43), but should work on any web server with PHP and OpenSSL support.

## What it looks like

The widget shows:
- 🌡️ Current temperature with an animated thermometer graphic
- 🤔 "Feels like" temperature
- 💧 Humidity
- 💨 Wind speed and direction
- 🔵 Air pressure
- 🌧️ Daily precipitation

Data auto-refreshes every 10 minutes in the browser. The PHP proxy caches the last response for 5 minutes to reduce API calls.

## Architecture

```
MediaWiki page
  └── {{Special:IframePage/Vader}}        ← Extension:IframePage
        └── vader-widget.html             ← standalone HTML/CSS/JS
              └── wunderground-proxy.php  ← PHP proxy (CORS + caching)
                    └── api.weather.com   ← Wunderground REST API
```

A proxy is required because the Wunderground API blocks direct browser requests due to CORS restrictions.

## Requirements

- **Synology NAS** (or any Linux web server with PHP)
- **PHP** with `openssl` module enabled (required for HTTPS calls)
- **Wunderground account** with a registered personal weather station
- **MediaWiki** with [Extension:IframePage](https://www.mediawiki.org/wiki/Extension:IframePage) installed

## Installation

### 1. Get your Wunderground API key and station ID

- API key: [wunderground.com](https://www.wunderground.com) → My Profile → Member Settings → **API Keys** (32-character hex string)
- Station ID: visible on [wundermap](https://www.wunderground.com/wundermap) or in your station settings (e.g. `IHUDDI4`)

### 2. Configure the proxy

```bash
cp config.php.example config.php
```

Edit `config.php` and fill in your API key and station ID.

### 3. Upload files to Synology

Copy these files to your Synology web root (e.g. `/volume1/web/`):

```
wunderground-proxy.php
config.php              ← your real config (never commit this)
vader-widget.html
```

**Enable PHP modules** in DSM → Web Station → PHP Settings:
- ✅ openssl
- ✅ curl (optional)

**Test the proxy** by opening it directly in a browser:
```
http://YOUR-SYNOLOGY-IP/wunderground-proxy.php
```
You should see a JSON response with weather data.

### 4. Update the widget URL

In `vader-widget.html`, update `PROXY_URL` to point to your proxy:

```javascript
var PROXY_URL = "http://YOUR-SYNOLOGY-IP/wunderground-proxy.php";
```

**Test the widget standalone:**
```
http://YOUR-SYNOLOGY-IP/vader-widget.html
```

### 5. Install Extension:IframePage in MediaWiki

1. Download [Extension:IframePage](https://github.com/wikimedia/mediawiki-extensions-IframePage) (branch `REL1_43`)
2. Rename the folder to `IframePage`
3. Upload to `/volume1/web_packages/mediawiki/extensions/IframePage/`
4. Add to `LocalSettings.php`:

```php
wfLoadExtension( 'IframePage' );

$wgIframePageSrc = [
    'Vader' => 'http://YOUR-SYNOLOGY-IP/vader-widget.html'
];
$wgIframePageAllowPath = false;
```

5. Restart the MediaWiki package in DSM Package Center

### 6. Use the widget on a wiki page

In wikitext source mode, add:

```
{{Special:IframePage/Vader}}
```

To float the widget to the right with text flowing alongside it:

```
<div style="float:right; width:340px; margin: 0 0 10px 10px;">
{{Special:IframePage/Vader}}
</div>

Your wiki text goes here and flows to the left of the widget.

<div style="clear:both;"></div>
```

## Files

| File | Purpose |
|---|---|
| `wunderground-proxy.php` | PHP proxy — fetches API data, handles CORS and caching |
| `config.php.example` | Configuration template — copy to `config.php` and fill in secrets |
| `vader-widget.html` | Standalone widget — HTML, CSS and JavaScript |
| `.gitignore` | Excludes `config.php` and `wx-cache.json` from version control |

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Proxy returns `"Unable to find the wrapper https"` | OpenSSL module not enabled in PHP profile |
| Proxy returns `"Invalid apiKey"` | Wrong API key — must be 32-char key from Member Settings, not station upload password |
| Widget shows error every few requests | Wunderground rate limiting or station offline — caching in proxy mitigates this |
| White page after adding extension to `LocalSettings.php` | Check `extension.json` manifest version compatibility with your MediaWiki version |
| `<iframe>` tag shows as plain text in wiki | Extension:IframePage not loaded — check Special:Version |

## Notes

- `config.php` and `wx-cache.json` are excluded from version control via `.gitignore` — never commit your API key
- The proxy caches responses for 5 minutes (`$cacheTime = 300`) — adjust as needed
- The widget auto-refreshes every 10 minutes (`REFRESH_INTERVAL`) — this is independent of the proxy cache

## License

MIT
