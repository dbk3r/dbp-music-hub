# DBP Music Hub

Ein professionelles WordPress-Plugin für Audio-Management und E-Commerce. Verwalte Audio-Dateien, erstelle einen Music Store mit WooCommerce-Integration und biete deinen Besuchern einen modernen Audio-Player.

## 🎵 Features

### Core-Funktionalität
- **Custom Post Type** für Audio-Dateien mit vollständiger WordPress-Integration
- **Drei Taxonomien**: Kategorien, Tags und Genres für flexible Organisation
- **Umfangreiche Meta-Felder**: Künstler, Album, Erscheinungsjahr, Dauer, Lizenzmodell, Preis, Vorschau-Datei
- **Moderner Audio-Player** mit HTML5 und Custom Controls
- **Responsive Design** für mobile und Desktop-Geräte

### Audio-Player
- ▶️ Play/Pause-Button mit Animation
- 📊 Progress Bar mit Seek-Funktion
- 🔊 Lautstärke-Regler
- ⬇️ Download-Button (optional)
- ⌨️ Tastatur-Navigation (Space, K, Pfeiltasten, M)
- 🎨 Anpassbare Farben über Admin-Einstellungen
- 🌙 Dark Mode Unterstützung

### WooCommerce-Integration
- Automatische Produkt-Erstellung beim Veröffentlichen von Audio-Dateien
- Synchronisation von Preis, Titel, Beschreibung und Thumbnails
- Downloadbare digitale Produkte
- Kategorien und Tags werden übernommen
- Vorschau-Dateien für kostenlose Hörproben

### Suche & Filter
- Erweiterte Suche nach Titel, Künstler, Album
- Filter nach Genre, Kategorie und Preis
- Integration in WordPress-Standardsuche
- Benutzerdefinierte Such-Query für Meta-Felder

### Shortcodes
- `[dbp_audio_player id="123"]` - Einzelner Player
- `[dbp_audio_list category="rock" limit="10"]` - Audio-Liste mit Filtern
- `[dbp_audio_search]` - Such-Formular mit allen Filtern

### Admin-Bereich
- Übersichtliche Meta Boxes für Audio-Details
- WordPress Media Uploader für Audio-Dateien
- Color Picker für Player-Anpassung
- Einstellungs-Seite unter "Einstellungen → DBP Music Hub"
- Deutsche Übersetzung (i18n-ready)

## 📋 Systemanforderungen

- **WordPress**: 5.8 oder höher
- **PHP**: 7.4 oder höher
- **Optional**: WooCommerce 4.0+ für E-Commerce-Funktionen

## 🚀 Installation

### Methode 1: WordPress Admin
1. Lade die Plugin-Dateien als ZIP-Archiv herunter
2. Gehe zu **WordPress Admin → Plugins → Installieren**
3. Klicke auf **Plugin hochladen**
4. Wähle die ZIP-Datei aus und klicke auf **Jetzt installieren**
5. Aktiviere das Plugin nach der Installation

### Methode 2: FTP/SFTP
1. Lade die Plugin-Dateien in das Verzeichnis `/wp-content/plugins/dbp-music-hub/` hoch
2. Gehe zu **WordPress Admin → Plugins**
3. Aktiviere "DBP Music Hub"

### Nach der Installation
1. Gehe zu **Einstellungen → DBP Music Hub**
2. Konfiguriere die Player-Farben und Optionen
3. Aktiviere optional die WooCommerce-Integration
4. Erstelle deine erste Audio-Datei unter **Audio-Dateien → Neue hinzufügen**

## 📖 Verwendung

### Audio-Dateien erstellen
1. Gehe zu **Audio-Dateien → Neue hinzufügen**
2. Gib Titel und Beschreibung ein
3. Lade eine Audio-Datei (MP3/WAV) hoch
4. Fülle die Meta-Felder aus (Künstler, Album, etc.)
5. Optional: Lade eine Vorschau-Datei für kostenlose Hörproben hoch
6. Setze Genres, Kategorien und Tags
7. Veröffentliche die Audio-Datei

### Shortcodes verwenden

#### Einzelner Player
```
[dbp_audio_player id="123"]
```
Zeigt einen Audio-Player für die Audio-Datei mit der ID 123 an.

**Parameter:**
- `id` (erforderlich): Audio-Post ID
- `show_download` (optional): "true" oder "false" (Standard: "true")

#### Audio-Liste
```
[dbp_audio_list category="rock" limit="10" orderby="date" show_player="true"]
```
Zeigt eine Liste von Audio-Dateien mit Filtern an.

**Parameter:**
- `category`: Kategorie-Slug
- `genre`: Genre-Slug
- `tag`: Tag-Slug
- `artist`: Künstlername
- `limit`: Anzahl der Einträge (Standard: 10)
- `orderby`: Sortierung - "date", "title", "rand" (Standard: "date")
- `order`: Reihenfolge - "ASC" oder "DESC" (Standard: "DESC")
- `show_player`: Player anzeigen - "true" oder "false" (Standard: "true")
- `show_thumbnail`: Thumbnail anzeigen - "true" oder "false" (Standard: "true")
- `columns`: Anzahl der Spalten (Standard: 3)

#### Such-Formular
```
[dbp_audio_search]
```
Zeigt ein Such-Formular mit Genre-, Kategorie- und Preis-Filtern an.

**Parameter:**
- `show_genre`: Genre-Filter anzeigen - "true" oder "false" (Standard: "true")
- `show_category`: Kategorie-Filter anzeigen - "true" oder "false" (Standard: "true")
- `show_price`: Preis-Filter anzeigen - "true" oder "false" (Standard: "true")

### Templates

Das Plugin enthält zwei Template-Dateien, die du in dein Theme kopieren kannst:

1. **Single Audio Template**: Kopiere `templates/single-audio.php` nach `dein-theme/single-dbp_audio.php`
2. **Archive Template**: Kopiere `templates/archive-audio.php` nach `dein-theme/archive-dbp_audio.php`

### WooCommerce-Integration

Wenn WooCommerce installiert und die Integration aktiviert ist:

1. Beim Veröffentlichen einer Audio-Datei wird automatisch ein WooCommerce-Produkt erstellt
2. Das Produkt wird als "downloadable" und "virtual" markiert
3. Die Audio-Datei wird als Download-Datei hinzugefügt
4. Preis, Titel und Beschreibung werden synchronisiert
5. Beim Aktualisieren der Audio-Datei wird auch das Produkt aktualisiert

### Einstellungen

Gehe zu **Einstellungen → DBP Music Hub** um folgende Optionen zu konfigurieren:

#### Allgemeine Einstellungen
- **Standard-Lizenzmodell**: Standard, Extended oder Commercial

#### Player-Einstellungen
- **Primärfarbe**: Farbe für Buttons und Progress Bar
- **Hintergrundfarbe**: Player-Hintergrund
- **Autoplay aktivieren**: Audio automatisch abspielen (kann von Browsern blockiert werden)
- **Download-Button anzeigen**: Download-Button im Player anzeigen

#### Integrationen
- **WooCommerce-Integration**: Automatische Produkt-Erstellung aktivieren/deaktivieren

## 🎨 Anpassung

### CSS-Variablen
Das Plugin verwendet CSS Custom Properties für einfache Anpassungen:

```css
:root {
	--dbp-primary-color: #3498db;
	--dbp-bg-color: #f5f5f5;
	--dbp-text-color: #2c3e50;
	--dbp-border-color: #ddd;
	--dbp-hover-color: #2980b9;
}
```

### Hooks & Filter

#### Actions
- `dbp_music_hub_loaded` - Wird nach Plugin-Initialisierung ausgeführt
- `dbp_music_hub_activated` - Wird bei Plugin-Aktivierung ausgeführt
- `dbp_music_hub_deactivated` - Wird bei Plugin-Deaktivierung ausgeführt
- `dbp_audio_save_meta_box` - Wird nach Meta-Box-Speicherung ausgeführt
- `dbp_woocommerce_product_created` - Wird nach WooCommerce-Produkt-Erstellung ausgeführt
- `dbp_woocommerce_product_updated` - Wird nach WooCommerce-Produkt-Update ausgeführt

#### Filter
- `dbp_audio_post_type_args` - Post Type Argumente anpassen
- `dbp_audio_category_args` - Kategorie-Taxonomie-Argumente anpassen
- `dbp_audio_tag_args` - Tag-Taxonomie-Argumente anpassen
- `dbp_audio_genre_args` - Genre-Taxonomie-Argumente anpassen
- `dbp_audio_player_html` - Player-HTML anpassen
- `dbp_audio_list_query_args` - Audio-Liste Query-Argumente anpassen
- `dbp_audio_advanced_search_args` - Erweiterte Such-Argumente anpassen

### Beispiel: Player-HTML anpassen
```php
add_filter( 'dbp_audio_player_html', 'custom_player_html', 10, 2 );
function custom_player_html( $html, $audio_id ) {
	// HTML anpassen
	return $html;
}
```

## 🔧 Entwicklung

### Dateistruktur
```
dbp-music-hub/
├── dbp-music-hub.php           # Haupt-Plugin-Datei
├── README.md                    # Dokumentation
├── includes/
│   ├── class-audio-post-type.php
│   ├── class-audio-meta-boxes.php
│   ├── class-audio-player.php
│   ├── class-woocommerce-integration.php
│   ├── class-search.php
│   └── class-shortcodes.php
├── admin/
│   ├── class-admin-settings.php
│   └── css/
│       └── admin-styles.css
├── public/
│   ├── js/
│   │   └── audio-player.js
│   └── css/
│       └── player-styles.css
└── templates/
    ├── single-audio.php
    └── archive-audio.php
```

### Code-Standards
- WordPress Coding Standards
- Präfix `dbp_` für Funktionen und `DBP_` für Klassen
- Alle Texte über i18n-Funktionen
- Nonces bei allen Forms
- Sanitization und Escaping
- Capability Checks

## 🐛 Häufige Probleme (FAQ)

**Q: Der Audio-Player wird nicht angezeigt**
A: Stelle sicher, dass eine Audio-Datei hochgeladen wurde und die Audio-Datei-URL korrekt ist.

**Q: WooCommerce-Produkte werden nicht erstellt**
A: Prüfe ob WooCommerce installiert und die Integration unter "Einstellungen → DBP Music Hub" aktiviert ist.

**Q: Audio-Dateien werden nicht in der Suche gefunden**
A: Die Suche durchsucht Titel, Beschreibung, Künstler und Album. Stelle sicher, dass diese Felder ausgefüllt sind.

**Q: Wie ändere ich die Player-Farben?**
A: Gehe zu "Einstellungen → DBP Music Hub" und nutze die Color Picker für Primär- und Hintergrundfarbe.

**Q: Kann ich Vorschau-Dateien verwenden?**
A: Ja! Lade eine Vorschau-Datei im Meta-Feld hoch. Diese wird im Player anstelle der vollständigen Datei abgespielt.

**Q: Unterstützt das Plugin mehrere Audio-Formate?**
A: Der Player unterstützt alle Browser-kompatiblen Formate (MP3, WAV, OGG, AAC). MP3 wird empfohlen für beste Kompatibilität.

**Q: Kann ich das Design anpassen?**
A: Ja! Nutze CSS Custom Properties oder kopiere die Template-Dateien in dein Theme und passe sie an.

## 🤝 Mitwirken

Beiträge sind willkommen! Bitte erstelle Pull Requests oder Issues auf GitHub.

### Entwickler-Setup
1. Klone das Repository
2. Stelle sicher, dass WordPress und WooCommerce (optional) installiert sind
3. Aktiviere das Plugin
4. Teste deine Änderungen

## 📝 Lizenz

Dieses Plugin ist lizenziert unter der **GPL v2 oder höher**.

```
DBP Music Hub - Audio-Management und E-Commerce für WordPress
Copyright (C) 2024 DBK3R

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

## 👨‍💻 Autor

**DBK3R**
- GitHub: [@dbk3r](https://github.com/dbk3r)

## 🌟 Support

Bei Fragen oder Problemen erstelle bitte ein Issue auf GitHub oder kontaktiere den Support.

---

**Viel Erfolg mit deinem Music Hub! 🎵**
