# grav-plugin-indexnow

> Grav CMS plugin — Automatically submit modified pages to IndexNow (Bing/Yandex) on every save.

## Installation

```bash
cp -r grav-plugin-indexnow /var/www/grav/user/plugins/indexnow
```

Then enable the plugin in Grav Admin → Plugins → IndexNow, or set `enabled: true` in `user/config/plugins/indexnow.yaml`.

## Configuration

| Parameter | Default | Description |
|-----------|---------|-------------|
| `enabled` | `false` | Enable/disable the plugin |
| `key` | — | Your IndexNow API key |
| `host` | — | Your site hostname (e.g. `example.com`) |
| `key_file` | — | Public URL where the key file is hosted |

## Hooks

| Event | Description |
|-------|-------------|
| `onAdminAfterSave` | Submits the saved page URL to IndexNow |
| `onMcpAfterSave` | Submits the page URL when saved via MCP server |

## License

MIT — Jm Rohmer / [arleo.eu](https://arleo.eu)
