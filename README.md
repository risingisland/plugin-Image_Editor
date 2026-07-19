# Image Editor

A [GetSimpleCMS-CE](https://github.com/GetSimpleCMS-CE/GetSimpleCMS-CE) plugin that lets you browse the images in `data/uploads` and edit them directly in the admin panel using [Toast UI Image Editor](https://github.com/nhn/tui.image-editor) — crop, rotate, flip, filters, text, drawing, shapes, and masking, all in the browser.

Edits are always saved as **new copies**, never overwriting the original file.

## Features

- Grid browser for `data/uploads`, with folder navigation and thumbnails (reuses GetSimple's own thumbnail conventions, so edited copies show up correctly in the native file browser too)
- Full Toast UI Image Editor toolbar: crop, flip, rotate, draw, shapes, icons, text, mask, filters
- Google Fonts picker for the text tool (23 fonts included by default, easy to add more)
- "Lock Aspect Ratio" checked by default in the Resize tool
- Choose the output filename and format (PNG / JPG / WebP) before saving
- Multi-language support (English and Spanish included)

## Requirements

- GetSimpleCMS-CE 3.3.22+
- PHP with the GD extension (used for thumbnail generation)
- A modern browser (uses the Fetch API, FileReader, and the CSS Font Loading API)

## Installation

1. Copy `ImageEditor.php` and the `ImageEditor/` folder into your site's `plugins/` directory, as siblings:
   ```
   plugins/
   ├── ImageEditor.php
   └── ImageEditor/
       ├── editor.php
       ├── lang/
       │   ├── en_US.php
       │   └── es_ES.php
       └── tui/
   ```
   
2. Activate the plugin from GetSimple's admin panel. A new "Image Editor" item will appear in the Files sidebar section.

## Usage

1. Open **Image Editor** from the admin sidebar.
2. Browse to the image you want to edit and click **Edit**.
3. Make your changes using Toast UI's toolbar.
4. Set a filename and output format, then click **Save as Copy**.

The saved copy appears alongside the original in the same folder, with both thumbnail variants (`thumbsm.*` and `thumbnail.*`) generated automatically.

## Notes and known limitations

- **SVG files aren't editable** — the Edit button is disabled for them, since a vector format doesn't make sense in a raster canvas editor.
- **WebP export isn't supported in Safari.** Per the WebP spec, when a browser doesn't support a requested export format, it silently falls back to PNG — so a Safari user selecting "WebP" will get a `.png` file instead, with a warning shown in the UI when that format is selected.
- **Output format doesn't preserve the original codec on its own** — PNG, JPG, and WebP are the three save options regardless of the source file's format; there's no automatic re-encode to match the original.
- **Google Fonts are loaded from Google's CDN**, not vendored locally, so the font picker requires the browser (not necessarily the server) to have internet access.
- The font list is defined in two places that need to stay in sync: the `GIE_GOOGLE_FONTS` array and the Google Fonts `<link>` tag, both in `editor.php`.

## Adding more fonts

Add the font name to the `GIE_GOOGLE_FONTS` array in `editor.php`, and add a matching `&family=Font+Name` parameter to the Google Fonts `<link>` tag a few lines above it. Use fonts you're confident are in Google's current catalog — a renamed or retired family will just silently fall back to a default font when selected.

## Adding more languages

Copy `lang/en_US.php` to a new `lang/xx_XX.php` file and translate the values. Keep the array keys identical across all language files — GetSimple does not fall back to English for missing keys within a language file that exists, so every key needs a translation once a language file is added.

## Author

**risingisland** — [ko-fi.com/ericmontgomery](https://ko-fi.com/ericmontgomery)
