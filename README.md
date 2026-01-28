# Immobilien REST API

Ein leichtgewichtiges WordPress-Plugin, das einen **Custom REST API Endpoint** für Immobilien bereitstellt.
Ideal als **Lern- & Erweiterungsbasis** für eigene REST-Endpunkte.

---

## Features

- Eigener REST Namespace `km/v1`
- Immobilien **Liste & Single Endpoint**
- Paging & Suche
- Saubere Trennung von:
  - Plugin-Bootstrap
  - REST-Logik
- GitHub‑freundlich & erweiterbar

---

## Endpunkte

### 📦 Immobilien – Liste

```
GET /wp-json/km/v1/immobilien
```

**Query-Parameter**

| Parameter | Typ     | Beschreibung                 |
| --------- | ------- | ---------------------------- |
| per_page  | integer | Anzahl pro Seite (max. 50)   |
| page      | integer | Seite                        |
| search    | string  | Suche (Titel + Beschreibung) |

**Beispiel**

```
/wp-json/km/v1/immobilien?per_page=10&page=1&search=haus
```

---

### 🏠 Immobilie – Single

```
GET /wp-json/km/v1/immobilien/{id}
```

**Beispiel**

```
/wp-json/km/v1/immobilien/123
```

---

## Response-Struktur

```json
{
  "id": 123,
  "title": "Einfamilienhaus mit Garten",
  "beschreibung": "Schönes Haus in ruhiger Lage ...",
  "kaufpreis": 420000,
  "zimmer": 5,
  "quadratmeter": 140,
  "baujahr": 1998,
  "permalink": "https://example.de/immobilien/haus-123"
}
```

---

## Erwartete Datenbasis

### Custom Post Type

- `immobilie`

### Post Meta Felder

- `kaufpreis` (float)
- `zimmer` (float)
- `quadratmeter` (float)
- `baujahr` (int)

> Die Meta-Felder können z. B. über **ACF**, eigene Meta-Boxen oder Import-Skripte gepflegt werden.

---

## Plugin-Struktur

```
immobilien-rest-api/
├─ immobilien-rest-api.php
└─ immobilien/
   └─ api/
      └─ immobilien-rest-endpoint.php
```

---

## Installation

1. Ordner nach `wp-content/plugins/` kopieren
2. Plugin im WordPress Backend aktivieren
3. Endpoint im Browser testen:
   ```
   /wp-json/km/v1/immobilien
   ```

---

## Erweiterungsideen

- 🔍 Filter: `min_price`, `max_price`, `rooms`, `min_qm`
- 🧱 ACF statt `get_post_meta`
- 🔐 Auth (Application Passwords / JWT)
- 📊 Sortierung (Preis, Baujahr)
- 🗺️ Geo-Daten (Lat/Lng + Radius)

---

## Ziel dieses Projekts

Dieses Plugin ist bewusst **klar & übersichtlich** gehalten, um:

- WordPress REST API zu verstehen
- eigene Endpunkte sicher aufzubauen
- eine wiederverwendbare Grundlage für weitere APIs zu haben

---

**Autor:** Kopfmedia  
**Lizenz:** MIT
