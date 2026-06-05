# WebP Image Converter — Plugin WordPress

Plugin WordPress pour convertir des images en WebP, JPEG, PNG, GIF et SVG directement dans le navigateur via l'API Canvas.

## Installation

1. Télécharger / cloner ce repository dans `/wp-content/plugins/` de votre installation WordPress
2. Activer le plugin dans **Extensions > Extensions installées**
3. Ajouter le shortcode `[webp_converter]` dans n'importe quelle page ou article

## Utilisation

### Shortcode

```
[webp_converter]
[webp_converter formats="webp,jpeg,png"]
```

**Attribut `formats`** — liste des formats disponibles (par défaut : tous) :
- `webp` — WebP
- `jpeg` — JPEG
- `png` — PNG
- `gif` — GIF
- `svg` — SVG

### Réglages

**Réglages > Image Converter** permet de configurer :

- Formats activés (checkboxes)
- Qualité JPEG (1-100, défaut 90)
- Qualité PNG (1-100, défaut 100)
- Qualité WebP (1-100, défaut 90)

## Structure

```
converter-wordpress-plugin/
├── converter-wordpress-plugin.php   # Fichier principal du plugin
├── admin/
│   ├── class-converter-admin.php     # Menu admin
│   └── class-converter-settings.php # API Settings WordPress
├── public/
│   ├── class-converter-public.php    # Shortcode + assets
│   ├── partials/
│   │   └── converter-public-display.php  # Template HTML
│   ├── css/
│   │   └── converter-public.css
│   └── js/
│       └── converter-public.js
├── includes/
│   └── class-converter-loader.php    # Loader pattern WP
└── assets/
    └── block.json                    # Métadonnées block Gutenberg
```

## Dépendances côté client

- JSZip 3.10.1 (CDN cdnjs) — pour le téléchargement ZIP de plusieurs images

## Compatibilité

- WordPress 5.0+
- PHP 7.4+
- Navigateurs supportant Canvas API et WebP côté client