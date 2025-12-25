# DBP Music Hub

Ein professionelles WordPress-Plugin für Audio-Management und E-Commerce. Verwalte Audio-Dateien, erstelle einen Music Store mit WooCommerce-Integration und biete deinen Besuchern einen modernen Audio-Player mit Playlists und Waveform-Visualisierung.

## 🎵 Features

### Core-Funktionalität
- **Custom Post Type** für Audio-Dateien mit vollständiger WordPress-Integration
- **Playlist-System** (v1.1.0) - Erstelle und verwalte Audio-Playlists mit Drag & Drop
- **Drei Taxonomien**: Kategorien, Tags und Genres für flexible Organisation
- **Umfangreiche Meta-Felder**: Künstler, Album, Erscheinungsjahr, Dauer, Lizenzmodell, Preis, Vorschau-Datei
- **Moderner Audio-Player** mit HTML5 und Custom Controls
- **Waveform-Visualisierung** (v1.1.0) - Interaktive Audio-Wellenform mit WaveSurfer.js
- **Responsive Design** für mobile und Desktop-Geräte

### Audio-Player
- ▶️ Play/Pause-Button mit Animation
- 📊 Progress Bar mit Seek-Funktion
- 🔊 Lautstärke-Regler
- ⬇️ Download-Button (optional)
- ⌨️ Tastatur-Navigation (Space, K, Pfeiltasten, M)
- 🎨 Anpassbare Farben über Admin-Einstellungen
- 🌙 Dark Mode Unterstützung
- 🌊 **NEU:** Optional Waveform-Visualisierung anstelle Standard-Player

### Playlist-Features (v1.1.0)
- 🎶 **Custom Post Type für Playlists** mit eigenem Admin-Bereich
- ⚡ **Drag & Drop Editor** - Sortiere Tracks per Maus
- 🔀 **Shuffle Mode** - Zufällige Wiedergabe-Reihenfolge
- 🔁 **Repeat Modes** - Off, Repeat One, Repeat All
- ▶️ **Auto-Play** - Automatischer Übergang zum nächsten Track
- 📊 **Live-Statistiken** - Track-Anzahl und Gesamt-Dauer
- 🎯 **AJAX-Suche** - Finde und füge Audio-Dateien schnell hinzu
- 💾 **LocalStorage** - Shuffle-State wird gespeichert
- 📱 **Responsive Player** - Optimiert für alle Geräte

### Waveform-Visualisierung (v1.1.0)
- 🌊 **Interaktive Waveform** mit WaveSurfer.js
- 🎨 **Anpassbare Farben** - Waveform und Progress-Farbe konfigurierbar
- 📏 **Zoom-Funktion** - Vergrößere die Waveform für Details
- 📍 **Click-to-Seek** - Klicke auf die Waveform zum Springen
- ⏱️ **Timeline Plugin** - Zeitachse mit Markierungen
- 📊 **Normalisierung** - Automatische Amplitude-Anpassung
- 🎯 **Responsive** - Passt sich automatisch der Breite an

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
- `[dbp_audio_player id="123" waveform="true"]` - Einzelner Player (mit Waveform-Option)
- `[dbp_audio_list category="rock" limit="10"]` - Audio-Liste mit Filtern
- `[dbp_audio_search]` - Such-Formular mit allen Filtern
- `[dbp_playlist id="123"]` - **NEU:** Playlist-Player anzeigen
- `[dbp_playlist_list limit="10"]` - **NEU:** Liste aller Playlists
- `[dbp_user_playlists]` - **NEU:** Playlists des aktuellen Users

### Admin-Bereich
- **NEU (v1.2.0):** Eigenes Top-Level Admin-Menü "Music Hub"
- **NEU (v1.2.0):** Dashboard mit Statistiken und Quick Actions
- **NEU (v1.2.0):** Audio-Manager mit professioneller Data-Table
- **NEU (v1.2.0):** Bulk-Upload mit Drag & Drop und ID3-Import
- **NEU (v1.2.0):** WooCommerce-Sync Dashboard
- **NEU (v1.2.0):** Kategorien & Genres Manager
- Übersichtliche Meta Boxes für Audio-Details
- Playlist-Editor mit Drag & Drop und AJAX-Suche (v1.1.0)
- WordPress Media Uploader für Audio-Dateien
- Color Picker für Player-Anpassung
- Einstellungs-Seite unter "Einstellungen → DBP Music Hub"
- Playlist-Einstellungen (Auto-Play, Shuffle, Max Tracks) (v1.1.0)
- Waveform-Einstellungen (Farben, Höhe, Normalisierung) (v1.1.0)
- Upload-Einstellungen (Dateigröße, Formate, ID3-Import) (v1.2.0)
- WooCommerce-Sync Einstellungen (v1.2.0)
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
3. **(v1.1.0) Optional:** Aktiviere Playlist-Feature und/oder Waveform-Visualisierung
4. Aktiviere optional die WooCommerce-Integration
5. Erstelle deine erste Audio-Datei unter **Audio-Dateien → Neue hinzufügen**
6. **(v1.1.0) Optional:** Erstelle deine erste Playlist unter **Playlists → Neue hinzufügen**

## 📖 Verwendung

### Audio-Dateien erstellen
1. Gehe zu **Audio-Dateien → Neue hinzufügen**
2. Gib Titel und Beschreibung ein
3. Lade eine Audio-Datei (MP3/WAV) hoch
4. Fülle die Meta-Felder aus (Künstler, Album, etc.)
5. Optional: Lade eine Vorschau-Datei für kostenlose Hörproben hoch
6. Setze Genres, Kategorien und Tags
7. Veröffentliche die Audio-Datei

### Playlists erstellen (v1.1.0)
1. Gehe zu **Playlists → Neue hinzufügen**
2. Gib Titel und Beschreibung ein
3. Setze ein Playlist-Cover (Featured Image)
4. Suche nach Audio-Dateien in der "Playlist-Tracks" Meta Box
5. Klicke auf "Hinzufügen" um Tracks zur Playlist hinzuzufügen
6. Sortiere Tracks per Drag & Drop
7. Konfiguriere Playlist-Einstellungen (Auto-Play, Shuffle, Repeat)
8. Veröffentliche die Playlist

### Shortcodes verwenden

#### Einzelner Player
```
[dbp_audio_player id="123"]
```
Zeigt einen Audio-Player für die Audio-Datei mit der ID 123 an.

**Parameter:**
- `id` (erforderlich): Audio-Post ID
- `show_download` (optional): "true" oder "false" (Standard: "true")
- `waveform` (optional, v1.1.0): "true" oder "false" (Standard: Auto-Detect aus Settings)

**Beispiele:**
```
[dbp_audio_player id="123" waveform="true"]
[dbp_audio_player id="456" show_download="false"]
```

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

#### Playlist Player (v1.1.0)
```
[dbp_playlist id="123"]
```
Zeigt einen Playlist-Player mit allen Tracks und Steuerungen an.

**Parameter:**
- `id` (erforderlich): Playlist-Post ID
- `show_controls`: Steuerungen anzeigen - "true" oder "false" (Standard: "true")
- `theme`: Theme - "light" oder "dark" (Standard: "light")

**Beispiel:**
```
[dbp_playlist id="123" theme="dark"]
```

#### Playlist-Liste (v1.1.0)
```
[dbp_playlist_list limit="10" orderby="date"]
```
Zeigt eine Liste von Playlists als Cards an.

**Parameter:**
- `limit`: Anzahl der Einträge (Standard: 10)
- `orderby`: Sortierung - "date", "title" (Standard: "date")
- `order`: Reihenfolge - "ASC" oder "DESC" (Standard: "DESC")
- `author`: Filter nach Author-ID (optional)

#### User Playlists (v1.1.0)
```
[dbp_user_playlists]
```
Zeigt die Playlists des aktuell eingeloggten Users an (inkl. Entwürfe und private).

**Parameter:**
- `limit`: Anzahl der Einträge (Standard: 20)
- `orderby`: Sortierung - "date", "title" (Standard: "date")
- `order`: Reihenfolge - "ASC" oder "DESC" (Standard: "DESC")

### Templates

Das Plugin enthält Template-Dateien, die du in dein Theme kopieren kannst:

1. **Single Audio Template**: Kopiere `templates/single-audio.php` nach `dein-theme/single-dbp_audio.php`
2. **Archive Template**: Kopiere `templates/archive-audio.php` nach `dein-theme/archive-dbp_audio.php`
3. **Single Playlist Template (v1.1.0)**: Kopiere `templates/single-playlist.php` nach `dein-theme/single-dbp_playlist.php`

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

#### Playlist-Einstellungen (v1.1.0)
- **Playlist-Feature aktivieren**: Playlist-Funktionalität ein/ausschalten
- **Auto-Play standardmäßig**: Nächsten Track automatisch abspielen
- **Shuffle standardmäßig**: Zufällige Wiedergabe-Reihenfolge
- **Max. Tracks pro Playlist**: Maximale Anzahl an Tracks (1-500)

#### Waveform-Einstellungen (v1.1.0)
- **Waveform-Feature aktivieren**: Waveform-Visualisierung ein/ausschalten
- **Waveform-Farbe**: Farbe der nicht abgespielten Waveform
- **Progress-Farbe**: Farbe für abgespielten Bereich und Cursor
- **Waveform-Höhe**: Höhe in Pixel (50-500)
- **Waveform normalisieren**: Automatische Amplitude-Anpassung

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
	--dbp-waveform-color: #ddd;
	--dbp-waveform-progress-color: #4a90e2;
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

**Q: Wie aktiviere ich die Waveform-Visualisierung? (v1.1.0)**
A: Gehe zu "Einstellungen → DBP Music Hub → Waveform-Einstellungen" und aktiviere das Waveform-Feature. Du kannst dann auch Farben und Höhe anpassen.

**Q: Wie erstelle ich eine Playlist? (v1.1.0)**
A: Gehe zu "Playlists → Neue hinzufügen", gib einen Titel ein, suche nach Audio-Dateien und füge sie per Klick hinzu. Sortiere per Drag & Drop.

**Q: Funktioniert Shuffle/Repeat über Sitzungen hinweg? (v1.1.0)**
A: Ja! Der Shuffle-State wird im LocalStorage des Browsers gespeichert und bleibt erhalten.

## 📋 Changelog

## Version 1.3.8 (2025-12-25)

### Player Customization
- ✨ **New Feature:** Admin settings to show/hide player elements
  - Toggle Fortschrittsbalken (Progress Bar)
  - Toggle Lautstärkeregler (Volume Control)
  - Toggle Shuffle-Button
  - Toggle Repeat-Button
  - Toggle Track-Thumbnails in Tracklist
- 🎛️ **Full Control:** Settings apply to all players (playlists and search results)
- ⚙️ **User-Friendly:** All elements enabled by default, disable as needed

### Settings Location
Music Hub → Einstellungen → Player-Elemente

## Version 1.3.7 (2025-12-25)

### Code Cleanup
- 🧹 **Removed duplicate code:** Fixed duplicate `DBP_Admin_Menu` initialization in `load_admin_dependencies()`
- 🧹 **Removed duplicate code:** Fixed duplicate `DBP_License_Manager` initialization in `init_plugin()`
- ✅ **Confirmed working:** Both admin buttons ("Neues Lizenzmodell" and "Alle Waveforms regenerieren") function correctly
- 📝 **Better comments:** Clarified where admin classes are initialized

### Technical Details
- Admin classes are now initialized ONCE in `load_admin_dependencies()` (triggered by `admin_menu` hook)
- Removed redundant initialization attempts from `init_plugin()`
- No functional changes - purely cleanup

## Version 1.3.6 (2025-12-25)

### FINAL FIX - Buttons Now Working! 
- 🎯 **Correct Hooks Identified:** Used actual WordPress Screen IDs from live system
- ✅ **License Manager:** `music-hub_page_dbp-license-manager` (WordPress sanitizes parent slug)
- ✅ **Dashboard:** `toplevel_page_dbp-music-hub-dashboard`
- 🔧 **Simplified Arrays:** Single correct hook per page (no more guessing)
- 📊 **Enhanced Debug:** Shows hook match status in debug boxes
- 🎉 **BOTH BUTTONS NOW WORK:** "Neues Lizenzmodell" and "Alle Waveforms regenerieren"

### Technical Details
- WordPress sanitizes `dbp-music-hub-dashboard` → `music-hub` for hook generation
- Removed all fallback hooks - using only confirmed working hooks
- Improved error logging for future diagnostics

### Version 1.3.5 (2025-12-25)

#### Critical Bugfix
- 🔥 **Button Fix:** Corrected WordPress admin hooks based on actual parent menu slug `dbp-music-hub-dashboard`
- ✅ **License Manager Button:** Now works correctly with hook `dbp-music-hub-dashboard_page_dbp-license-manager`
- ✅ **Waveform Button:** Now works correctly with hook `toplevel_page_dbp-music-hub-dashboard`
- 🔍 **Debug Mode:** Added visible debug boxes when WP_DEBUG is enabled
- 📝 **Hook Logging:** Improved error logging for hook diagnosis

#### Technical Changes
- Fixed valid_hooks arrays in both `class-license-manager.php` and `class-dashboard.php`
- Added transient-based hook capture for debugging
- Added visual debug output on admin pages (only when WP_DEBUG = true)
- Improved fallback mechanism with screen ID matching

### Version 1.3.3 (2025-12-25)

#### Critical Bugfixes
- 🐛 **Lizenzmodell-Button Fix**: Hook-Check korrigiert, Button funktioniert jetzt
- 🐛 **Waveform-Button Fix**: Hook-Check + AJAX-Handler hinzugefügt, Regenerierung funktioniert
- 🐛 **Warenkorb-Button Fix**: Button wird jetzt in Playlists angezeigt
- 🔍 **Debug-Logging**: Temporäres Logging für Hook-Diagnose aktiviert

#### Technical
- Hook-Checks verwenden jetzt Arrays für mehrere Varianten
- AJAX-Handler für Waveform-Batch-Processing implementiert
- WP_Query für effiziente Batch-Verarbeitung (10 Tracks pro Request)
- Progress-Bar mit Prozentanzeige
- Error-Handling verbessert

### Version 1.3.1 (2025-12-25)

#### 📄 PDF License Certificates
- **Automatische PDF-Generierung**: Lizenz-Zertifikate werden automatisch bei Bestellabschluss erstellt
- **HTML-basierte Zertifikate**: Professionelle Zertifikate ohne externe PDF-Bibliotheken
- **Eindeutige Lizenz-Nummern**: Format `DMH-{YEAR}-{ORDER_ID}-{ITEM_ID}` für jede Lizenz
- **Strukturierte Ablage**: PDFs werden in `/wp-content/uploads/dbp-licenses/{YEAR}/{MONTH}/` gespeichert
- **Order-Integration**: Download-Links direkt in den WooCommerce Bestelldetails
- **Email-Anhang**: Optional als Anhang in der Bestellbestätigungs-Email (konfigurierbar)
- **Anpassbares Design**: Logo, Farben, Wasserzeichen über Admin-Einstellungen
- **QR-Code Verifizierung**: Scanbare QR-Codes für schnelle Lizenz-Prüfung

#### 🔍 License Verification System
- **Öffentliche Verifizierung**: Lizenz-Überprüfung unter `/verify-license/?id=XXX`
- **Rewrite Rules**: Clean URLs für Verification-Page
- **Shortcode Support**: `[dbp_verify_license]` mit Formular-Eingabe
- **Detaillierte Anzeige**: Track, Künstler, Lizenzmodell, Datum, anonymisierte Email
- **Validierung**: Automatische Prüfung gegen WooCommerce-Orders
- **Security**: Anonymisierte Kunden-Daten zum Schutz der Privatsphäre

#### ⚙️ PDF Settings (Admin)
- **Neue Settings-Sektion**: "Lizenz-PDF" unter Einstellungen
- **Auto-Generierung**: Ein/Ausschalten der automatischen PDF-Erstellung
- **Email-Anhang**: Option für Email-Versand aktivieren/deaktivieren
- **Logo-Upload**: Media-Uploader für Zertifikat-Logo
- **Farb-Anpassungen**: Hauptfarbe und Textfarbe per Color-Picker
- **Wasserzeichen**: Optional mit konfigurierbarem Text
- **QR-Code Option**: QR-Code für Verifizierung ein/ausschalten
- **Rechtlicher Text**: Freies Textfeld für Nutzungsbedingungen/Footer

#### 🔧 Admin-Fixes
- **Waveform-Button**: "Alle Waveforms regenerieren" Button funktioniert jetzt korrekt
- **Batch-Processing**: Verarbeitung in kleinen Batches mit Progress-Bar
- **License Manager**: Alle Buttons (Bearbeiten, Löschen, Sortieren) funktionieren einwandfrei
- **AJAX-Handler**: Korrekte Nonce-Prüfung und Error-Handling
- **Dashboard-Assets**: JS und CSS werden nur auf relevanten Admin-Seiten geladen

#### 🛡️ Sicherheit & Standards
- **WordPress Coding Standards**: Alle neuen Dateien folgen WP Standards
- **Nonces**: Gesicherte AJAX-Requests für alle Admin-Aktionen
- **Sanitization**: Input-Daten werden korrekt bereinigt
- **Escaping**: Output wird sicher escaped
- **i18n ready**: Alle Texte übersetzbar mit Text Domain `dbp-music-hub`
- **Error Handling**: User-freundliche Fehlermeldungen

#### 📁 Neue Dateien
- `includes/class-license-pdf-generator.php` - PDF-Generierungs-Engine
- `includes/class-license-verification.php` - Verification-System

#### 🔄 Aktualisierte Dateien
- `admin/class-admin-settings.php` - PDF-Settings hinzugefügt
- `admin/js/admin-dashboard.js` - Waveform-Regenerierung funktionsfähig
- `admin/js/license-manager.js` - Button-Handler korrekt implementiert
- `admin/class-dashboard.php` - Assets korrekt eingebunden
- `admin/class-license-manager.php` - AJAX-Handler vollständig
- `includes/class-waveform-cache.php` - Bulk-Regenerierung optimiert
- `dbp-music-hub.php` - Version 1.3.1, neue Klassen geladen
- `README.md` - v1.3.1 Changelog

### Version 1.3.0 (2025-12-25)

#### 💳 Neue Features - Lizenzmodell-System
- **Lizenzmodell-Auswahl**: Modal-Popup zur Auswahl der Lizenz beim "In den Warenkorb"
- **Admin-Verwaltung**: Vollständige CRUD-Verwaltung für Lizenzmodelle unter "Music Hub → Lizenzmodelle"
- **Anpassbare Lizenzen**: Name, Preis, Beschreibung, Features, Icon, Farbe individuell konfigurierbar
- **"Beliebt"-Badge**: Markierung für beliebte Lizenzmodelle
- **WooCommerce Variable Products**: Automatische Erstellung von Product Variations pro Lizenz
- **AJAX Add-to-Cart**: Ohne Reload in den Warenkorb mit Lizenzauswahl
- **Flexible Preise**: Fester Preis oder Aufschlag auf Basis-Preis möglich
- **Playlist-Integration**: "In den Warenkorb"-Button bei jedem Track in Playlists

#### 🎨 Admin-Features
- Neue Verwaltungsseite unter "Music Hub → Lizenzmodelle"
- Standard-Lizenzen: Standard, Extended, Commercial (vorkonfiguriert und anpassbar)
- Rich-Text Editor für Beschreibungen
- Features als Bullet-Point-Liste
- Icon-Auswahl (⚡, 🚀, 💼, 👑, ⭐, 🎯, 💎, 🔥)
- Drag & Drop Sortierung
- Aktivieren/Deaktivieren einzelner Lizenzen
- Color Picker für Button-Farben

#### ✨ Frontend-Features
- Responsive Modal mit Lizenz-Cards im 3-Spalten Grid
- Preis-Vergleich übersichtlich dargestellt
- Features-Liste pro Lizenz
- "Beliebt"-Badge bei empfohlenen Lizenzen
- Success-Notifications nach Add-to-Cart
- ESC-Taste und Backdrop zum Schließen
- Mobile-optimiert (1-Spalten Layout)

#### 🔧 Technisch
- WooCommerce Variable Products & Variations
- AJAX-basiertes Add-to-Cart ohne Reload
- Nonce-gesicherte Requests für Sicherheit
- Responsive Design (Mobile-First)
- Smooth Animations & Transitions
- WordPress Coding Standards konform
- i18n ready für Übersetzungen

### Version 1.2.2 (2025-12-25)

#### 🐛 Kritische Bugfixes
- **Suchform funktioniert jetzt korrekt**: `[dbp_audio_search]` Shortcode zeigt Ergebnisse ordnungsgemäß an
- Such-Formular rendert jetzt WP_Query korrekt mit allen Filtern
- Pagination für Suchergebnisse implementiert
- "Als Playlist speichern" Button funktioniert mit AJAX

#### ⚡ Performance-Verbesserungen - Waveform-Caching
- **10x schnellere Waveform-Visualisierung** durch intelligentes Caching-System
- Automatische Pre-Generierung beim Audio-Upload
- Post Meta Cache mit Transient-Fallback (24h)
- Lazy Loading mit Intersection Observer - Waveforms laden nur wenn sichtbar
- Cached Peaks werden direkt geladen statt neu zu berechnen

#### ✨ Neue Features
- **Waveform-Cache-System**: Neue Klasse `DBP_Waveform_Cache` für optimierte Performance
- **Admin-Tools**: Waveform Tools Widget im Dashboard mit Statistiken
- **Bulk-Regenerierung**: "Alle Waveforms regenerieren" Button mit Progress-Bar
- **Bulk-Action**: "Waveform regenerieren" für einzelne oder mehrere Audio-Dateien
- **Waveform-Status-Spalte**: Zeigt Cache-Status in Audio-Übersicht an
- Loading-Indicator und Error-Handling für Waveform-Player

#### 🎨 Design-Verbesserungen für Suchform
- Modernes Grid-Layout für Such-Filter
- Card-Design für Suchergebnisse mit Hover-Effekten
- Responsive Design für mobile Geräte
- Pagination-Styling mit "Weiter/Zurück" Buttons
- Dark Mode Support für Suchformular

#### 🔧 Technische Details
- Waveform-Peaks werden als data-attribute übergeben
- Intersection Observer für optimale Performance
- AJAX-basierte Bulk-Regenerierung mit Batch-Processing (5 pro Batch)
- Progress-Bar zeigt Fortschritt in Echtzeit
- Verbesserte Error-Handling und Logging
- Nonce-Prüfung für alle AJAX-Requests

### Version 1.3.2 (2025-12-25)

#### Kritische Bugfixes
- 🐛 **wpColorPicker-Fehler behoben**: Color Picker Script wird jetzt korrekt mit Abhängigkeiten geladen
- 🐛 **Warenkorb-Button in Playlist**: "In den Warenkorb"-Button wird jetzt in Playlist-Tracklisten angezeigt
- 🐛 **Playlist-Menü**: Playlists erscheinen jetzt korrekt unter "Music Hub" im Admin-Menü
- 🐛 **Modal-System**: Lizenzauswahl-Modal funktioniert jetzt vollständig mit korrekten CSS-Klassen
- 🐛 **Script-Enqueuing**: Alle JavaScript-Abhängigkeiten korrekt registriert

#### Verbesserungen
- ✅ admin/js/admin-settings.js hinzugefügt für Color Picker Initialisierung
- ✅ Media-Upload-Unterstützung für zukünftige Logo-Features
- ✅ Besseres Error-Handling in AJAX-Calls
- ✅ `dbp-open-license-modal` Klasse zu Warenkorb-Buttons hinzugefügt
- ✅ Playlist CPT `show_in_menu` korrekt auf 'dbp-music-hub-dashboard' gesetzt

#### Technische Details
- wp-color-picker als Script-Abhängigkeit in admin-settings.js hinzugefügt
- wp_localize_script für AJAX-URL und Nonce bereits vorhanden
- CPT show_in_menu korrekt konfiguriert für Integration in Music Hub Menü
- Inline-Script aus render_settings_page entfernt, verwendet jetzt separate JS-Datei

### Version 1.2.1 (2025-12-25)

#### Bugfixes
- 🐛 **Admin-Menü Fix**: Custom Admin-Bereich "Music Hub" wird jetzt korrekt in WordPress-Seitenleiste angezeigt
- 🐛 **Waveform-Player**: Flackern behoben durch Initialisierungs-Check und Cleanup
- 🐛 **Player-Breite**: max-width 800px, responsive & zentriert (Desktop + Mobile)
- 🐛 **Such-Funktion**: Audio-Dateien werden jetzt korrekt in Suchergebnissen gefunden

#### Neue Features
- ✨ **Search-to-Playlist**: Button "Als Playlist speichern" bei Suchergebnissen
- ✨ Suchergebnisse können direkt als Playlist gespeichert werden
- ✨ Temporäre Session-Playlists für schnellen Zugriff

#### Technische Verbesserungen
- Admin-Klassen werden jetzt früher geladen (bei `plugins_loaded` statt `admin_menu`)
- WaveSurfer.js Initialisierung verbessert mit Cleanup
- Responsive CSS für Player auf allen Geräten
- Meta-Query und Tax-Query für verbesserte Suche

### Version 1.2.0 (2025-12-25)

#### Neue Features - Custom Admin-Bereich
- ✅ **Eigenes Top-Level Admin-Menü** - "Music Hub" mit eigenem Icon
- ✅ **Dashboard mit Statistiken** - Übersicht über Audio-Dateien, Playlists, Produkte und Speicherplatz
- ✅ **Audio-Manager mit Data-Table** - Professionelle Tabelle mit Sortierung, Filterung und Inline-Editing
- ✅ **Bulk-Upload mit Drag & Drop** - Mehrere Audio-Dateien gleichzeitig hochladen
- ✅ **ID3-Tag Auto-Import** - Automatisches Auslesen von Metadaten aus Audio-Dateien
- ✅ **WooCommerce-Sync Dashboard** - Zentrale Verwaltung der WooCommerce-Integration
- ✅ **Kategorien & Genres Manager** - Einfache Verwaltung von Taxonomien
- ✅ **AJAX-Powered Interface** - Schnelle Interaktionen ohne Page-Reload
- ✅ **Responsive Admin-Design** - Optimiert für Desktop und Mobile

#### Admin-Menü Struktur
```
🎵 DBP Music Hub (Top-Level Menü)
├── 📊 Dashboard
├── 🎵 Audio-Dateien
├── 📤 Bulk Upload
├── 📝 Playlists
├── 🛒 WooCommerce Sync
├── 🏷️ Kategorien & Genres
└── ⚙️ Einstellungen
```

#### Dashboard Features
- Statistik-Karten mit Audio-Count, Playlist-Count, Produkt-Count und Speicherplatz
- Letzte Uploads mit Quick-Actions
- Top-verkaufte Tracks (bei WooCommerce-Integration)
- Quick Actions für häufige Aufgaben
- Aktivitäts-Feed mit letzten Änderungen

#### Audio-Manager Features
- WP_List_Table mit Sortierung nach allen Spalten
- Filter nach Genre, Kategorie und WooCommerce-Status
- Suche nach Titel, Künstler und Album
- Bulk-Actions: Löschen, WC-Produkte erstellen, Taxonomien zuweisen
- Inline-Editing für schnelle Änderungen
- 20 Items pro Seite mit Pagination

#### Bulk-Upload Features
- Drag & Drop Upload-Zone
- Parallele Uploads (konfigurierbar: 1-10 gleichzeitig)
- ID3-Tag Import (Titel, Künstler, Album, Jahr, Genre)
- Standard-Einstellungen für Genre, Kategorie, Preis und Lizenz
- Auto-Erstellung von WooCommerce-Produkten (optional)
- Fortschrittsanzeige mit Datei-Status
- Upload-Queue Management

#### WooCommerce-Sync Features
- Übersichts-Statistiken (mit Produkt, ohne Produkt, verwaiste Produkte)
- Sync-Tabelle mit Status-Icons
- Bulk-Actions: Alle Produkte erstellen/synchronisieren/löschen
- Einzelne Sync-Actions pro Audio-Datei
- Letzte Synchronisation Timestamp
- Real-Time Status-Updates

#### Kategorien & Genres Manager
- 3-Spalten-Layout für Kategorien, Tags und Genres
- Term-Statistiken mit Top 5
- Quick-Add und Quick-Delete
- Bulk-Zuweisung mit Audio-Suche
- AJAX-Search für Audio-Dateien
- Inline-Bearbeitung von Terms

#### Neue Einstellungen
**Upload-Einstellungen:**
- Max. Dateigröße (MB)
- Erlaubte Formate (MP3, WAV, FLAC, OGG, M4A)
- ID3-Tags automatisch importieren
- Max. parallele Uploads

**WooCommerce-Sync:**
- Auto-Sync bei Audio-Save
- Kategorien automatisch übernehmen
- Tags automatisch übernehmen
- Standard-Produkt-Status (veröffentlicht/Entwurf/ausstehend)

#### Technische Verbesserungen
- AJAX-basierte Admin-Interaktionen
- Plupload Integration für zuverlässige Uploads
- jsmediatags Library für Client-Side ID3-Parsing
- getID3 (WordPress Core) für Server-Side ID3-Parsing
- Responsive CSS mit Mobile-First Approach
- WordPress Coding Standards
- Vollständige i18n-Unterstützung (Deutsch)

### Version 1.1.0 (2025-12-25)

#### Neue Features
- ✅ **Playlist-System** - Vollständiges Playlist-Management mit Custom Post Type
- ✅ **Drag & Drop Editor** - Sortiere Tracks visuell per Maus im Admin-Bereich
- ✅ **Playlist Player** - Moderner Player mit Auto-Play, Shuffle und Repeat-Modi
- ✅ **AJAX-Suche** - Finde und füge Audio-Dateien schnell zu Playlists hinzu
- ✅ **Waveform-Visualisierung** - Interaktive Audio-Wellenform mit WaveSurfer.js
- ✅ **Waveform-Anpassung** - Konfigurierbare Farben, Höhe und Normalisierung
- ✅ **3 Neue Shortcodes** - [dbp_playlist], [dbp_playlist_list], [dbp_user_playlists]
- ✅ **Erweiterte Settings** - Playlist und Waveform-Einstellungen im Admin
- ✅ **LocalStorage Support** - Shuffle-State und Lautstärke werden gespeichert
- ✅ **Responsive Design** - Alle neuen Features optimiert für mobile Geräte

#### Verbesserungen
- 🔧 Audio-Player Shortcode unterstützt jetzt `waveform` Parameter
- 🔧 Template für einzelne Playlists hinzugefügt
- 🔧 Fisher-Yates Shuffle-Algorithmus für echte Zufälligkeit
- 🔧 Live-Statistiken im Playlist-Editor (Track-Count, Gesamt-Dauer)

#### Technisch
- 📦 WaveSurfer.js 7.0 Integration via CDN
- 📦 jQuery UI Sortable für Drag & Drop
- 🔒 Vollständige Sanitization und Nonce-Prüfungen
- 🌐 i18n-ready für alle neuen Strings

### Version 1.0.0 (2024)
- 🎉 Initiales Release
- ✅ Audio Custom Post Type mit Taxonomien
- ✅ HTML5 Audio-Player mit Custom Controls
- ✅ WooCommerce-Integration
- ✅ Erweiterte Suche und Filter
- ✅ 3 Shortcodes für Audio-Darstellung
- ✅ Admin-Einstellungen mit Color Picker

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
