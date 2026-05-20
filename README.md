# 🗺️ GPX Trail Repository

Prosta, lekka aplikacja webowa do hostowania i wyświetlania tras GPX. Idealna dla vlogerów rowerowych, trekkingowych i twórców treści zawierającej trasy.


**👉 [Zobacz demo](https://gpxrepo.deploy.net.pl) Bez zależności, bez bazy danych.**

---
## 📸 Screenshots
### tlo, avatar, baner reklamowy, podsumowanie tras
![tlo, avatar + podsumowanie tras](https://gpxrepo.deploy.net.pl/img/baner_gorny.png)

### szczegoły tras
![Route Details](https://gpxrepo.deploy.net.pl/img/szczegoly_tras.png)

### wizualizacja trasy
![Wizualizacja trasy](https://gpxrepo.deploy.net.pl/img/wizualizacja_trasy.png)

##  **URUCHOM NA BIEDAHOSTINGU**

Chcesz mieć swoje repozytorium ?

### 🔥 **Zamów na Bieda Hosting!**

-  **MINI** - 15 zł/rok (100 MB na pliki) - Darmowa subdomena `twoja-nazwa.deploy.net.pl`
-  **STARTER** - 25 zł/rok (250 MB na pliki) - Darmowa subdomena `twoja-nazwa.deploy.net.pl`
-  **PRO** - 100 zł/rok (1 GB na pliki) - Darmowa subdomena `twoja-nazwa.deploy.net.pl`


**Zawiera:**
- ✅ Pełna wersja ze zbiorczą mapą
- ✅ Cache system
- ✅ Instalacje potrzebnego kodu
- ✅ SSL certyfikat
- ✅ Backup automatyczne (7 dni)

**👉 [Zarejestruj się na biedahosting.pl](https://biedahosting.pl)**

Albo:
- 📧 Email: kontakt@biedahosting.pl
- 💬 "Chcę repo tras!"

---

##  **SELF-HOSTED **

Wolisz hostować sam? Żaden problem!

Ściągnij wersję bez mapy z GitHub'a i umieść na swoim hostingu.

---

##  Cechy

- 📍 **Interaktywna mapa** - Leaflet + OpenStreetMap
- 📊 **Profil wysokościowy** - Chart.js visualization
- 📥 **Download GPX** -  śledzenie pobrań
- 👍 **System plusów** - Oceń trasy (rate limiting)
- 🎬 **Integracja YouTube** - Link do filmu  do każdej trasy (opcjonalnie)
- 📝 **Opisy Markdown** - Dokumentacja tras
- 🔍 **Wyszukiwanie** - Po nazwie trasy
- 📱 **Responsywny design** - Desktop i mobile
- ⚡ **Szybki cache** - Wczytywanie nowych tras w oddzielnym procesie


---

##  Szybki Start

### 1. **Setup**

```bash
# Pobierz pliki
https://github.com/Piotrek-Biedahosting/gpxrepo
cd gpxrepo

# Wrzuć na hosting via FTP/panel
```

**WAŻNE:** Plik `generate-cache.php` wrzuć **POZA /public_html/** (np. do `/home/username/`) ze względów bezpieczeństwa!

### 2. **Struktura folderów**

```
public_html/
├── index.html                 # Główna aplikacja
├── get-routes.php             # API - lista tras
├── get-route-points.php       # API - punkty GPX
├── get-description.php        # API - opisy tras
├── get-metadata.php           # API - metadata (YouTube, etc)
├── like-route.php             # API - system plusów
├── track.php                  # Server-side download tracker
├── .htaccess                  # URL rewrite rules
├── trasy/                     #  Folder z GPX files
│   ├── trasa1.gpx
│   ├── trasa1.txt             # Opis markdown (opcjonalny)
│   └── trasa2.gpx
├── avatar/                    #  Avatar uzytkownika
│   └── avatar.jpg
├── header-bg/                 #  Tło nagłówka
│   └── tlo.jpg
├── reklamy/                   #  Banery reklamowe
│   ├── banner.jpg
│   └── banner-url.txt         #  Link baneru


# POZA /public_html/ (dla bezpieczeństwa):
/home/username/
└── generate-cache.php         # ⚙️ Cache generator (cron job)

# Foldery tworzone automatycznie:
public_html/
├── .points-cache/             # Cache punktów GPS
├── .downloads.json            # Licznik pobrań
├── .likes.json                # Licznik polubień
├── .rate-limit.json           # Limit polubień
└── routes-cache.json          # Cache tras
```

### 3. **Dodaj swoje trasy**

Wrzuć pliki GPX do folderu `/trasy/`:

```
trasa-nazwa.gpx
```

(Nazwy plików będą automatycznie oczyszczane)

### 4. **Dodaj opcjonalne opisy**

Stwórz plik tekstowy z opisem w Markdown:

```
trasa-nazwa.txt
```

### 5. **Dodaj YouTube video**

Stwórz `trasy-metadata.json` w `/public_html/`:

```json
{
  "trasa-nazwa.gpx": {
    "youtube": "bUcd5tEktHk",
    "title": "Opcjonalny tytuł video"
  }
}
```


### 6. **Logo i branding**

- `avatar/avatar.jpg` - Twoje zdjęcie (avatara)
- `header-bg/tlo.jpg` - Tło nagłówka
- `reklamy/banner.jpg` - Banner reklamowy
- `reklamy/banner-url.txt` - Link do banera (tekst)

### 7. **Udostępnianie linków do tras**

Możesz wysyłać linki bezpośrednio do poszczególnych tras! URL parametr `?trasa=` automatycznie:
- ✅ Scrolluje do trasy
- ✅ Podświetla ją na żółto (60 sekund)
- ✅ Pokazuje wszystkie informacje

#### Przykłady:

```
https://twoja-domena.com/?trasa=nazwa-pliku-trasy

https://twoja-domena.com/?trasa=tatry_poludniowe
```

**Format nazwy w URL:**
- Zamień spacje na podkreślenia (_)
- Zamień polskie w nazwie pliku gpx znaki (ąćęłńóśźż) na normalne (acelnoszz)
- Bez `.gpx` rozszerzenia


#### Przypadki użycia:

1. **Udostępnianie w YouTube:**
   ```
   W opisie filmu: https://twoja-domena.com/?trasa=nazwa-trasy
   ```

2. **Social media (Instagram, TikTok):**
   ```
   "Trasę z nowego filmu: https://twoja-domena.com/?trasa=tatry"
   ```


#### Jak sprawdzić nazwę trasy w URL?

Plik GPX + nazwa pliku = nazwa w URL:

```
Plik: Kraków-Lublin-280km.gpx
URL:  ?trasa=Krakow-Lublin-280km

Plik: wielka_pętla.gpx
URL:  ?trasa=wielka_petla

Plik: Tatry - Słowacja.gpx
URL:  ?trasa=Tatry-Slowacja
```

---

### 8. **Auto-refresh cache (Cron)**

Aby cache się automatycznie aktualizował co X minut, dodaj **cron job**:

#### Via cPanel:

1. **cPanel → Cron Jobs**
2. **Add New Cron Job:**
   ```
   /usr/bin/php /home/username/generate-cache.php
   ```
3. **Common Settings:**
   - Co 5 minut: `*/5 * * * *`
   - Co godzinę: `0 * * * *`
   - Codziennie: `0 0 * * *`

#### Via SSH:

```bash
crontab -e
```

Dodaj linię:
```
*/5 * * * * /usr/bin/php /home/username/generate-cache.php
```

(Zastąp `/home/username/` swoją ścieżką)



**Czym się zajmuje skrypt:**
- ✅ Sprawdza czy GPX pliki się zmieniły
- ✅ Jeśli tak → regeneruje cache
- ✅ Jeśli nie → nic nie robi (szybko!)
- ✅ Sanityzuje nazwy plików


**Logi:**


```bash
*/5 * * * * /usr/bin/php /home/username/generate-cache.php >> /home/username/cache.log 2>&1
```

### 9. **Baner reklamowy**

Wrzuć baner w `/reklamy/`:

```
reklamy/
├── banner.jpg          # Zdjęcie banera
└── banner-url.txt      # Link do banera
```

Zawartość `banner-url.txt`:
```
https://twoja-strona.com
```

**Czym może być baner:**
- 🔗 Link do Twojego YouTube'a
- 💰 Link afiliacyjny
- ☕ Buy me a coffee
- 💳 Patreon
- 🛍️ Sklep merchandise


Baner wyświetli się w nagłówku aplikacji z aktywnym linkiem!

---

## ⚙️ Wymagania

- **PHP 7.4+** (rekomendowane PHP 8.0+)
- Hosting z support dla:
  - SimpleXML (parsowanie GPX)
  - File operations (odczyt/zapis)
  - .htaccess (URL rewrite)
- **FTP/Panel dostęp** do wrzucania plików

## 🔧 Konfiguracja

Wszystko działa **out of the box** - brak dodatkowej konfiguracji!

Jeśli potrzebujesz zmian - edytuj `index.html`:
- Kolory przycisków (szukaj `#2d5016`)
- Wymiary mapy (szukaj `height: 500px`)
- Tekst nagłówków (szukaj `<h1>`)

---


### Ikony

Zamień ikony emoji na Twoje:
- 🗺️ Mapa
- 🎬 Film
- 📥 Pobierz GPX
- 📖 Opis
- 👍 Plusik

### Responsywność

Mobile breakpoints:
- `@media (max-width: 768px)` - Tablet
- `@media (max-width: 480px)` - Phone

---



## 🚀 Deployment

### Shared Hosting (np. biedahosting.pl)

1. **Via FTP:**
   ```
   Wrzuć wszystkie pliki do /public_html/
   załóz foldery: /trasy/, /avatar/, /header-bg/, /reklamy/, /galeria/
  
   ```



## 📄 Licencja

MIT License - Używaj i modyfikuj swobodnie!

---

## 🤝 Support

Pytania? Problemy?

- 📧 Email: kontakt@biedahosting.pl

- 💡 Sugestie: Utwórz Pull Request

---



## 🌟 Credits

Built with:
- [Leaflet](https://leafletjs.com/) - Maps
- [OpenStreetMap](https://www.openstreetmap.org/) - Tiles
- [Chart.js](https://www.chartjs.org/) - Elevation profiles
- [Marked.js](https://marked.js.org/) - Markdown rendering

---




---
