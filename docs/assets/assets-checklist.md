---
title: WordPress.org Assets Checklist
description: Required assets for plugin listing and release
keywords: [assets, banner, icon, screenshots]
category: admin-guide
audience: admin
difficulty: beginner
last_updated: 2025-10-30
version: 1.6.0
---

## Plugin Icon
- File: `assets/icon-128x128.png` (128x128)
- File: `assets/icon-256x256.png` (256x256)
- Optional: `assets/icon.svg`
- Design: Simple, recognizable; suggested motif: shield/check or cookie+check

## Plugin Banner
- File: `assets/banner-772x250.png` (772x250)
- File: `assets/banner-1544x500.png` (1544x500, retina)
- Design: Matches icon; text: “Lightweight Consent Management”
- Colors: Align with brand and in-plugin primary color

## Screenshots
- Files: `assets/screenshot-1.png` … `assets/screenshot-6.png`
- Format: PNG preferred (JPG acceptable)
- Size: 1280x720 or larger, maintain aspect ratio
- Content: Follow `docs/assets/screenshots.md`

## Placement (WordPress.org SVN)
- Place icons and banners in `/assets/` at the repository root (not in plugin ZIP)
- Place screenshots in `/assets/` as `screenshot-1.png` … `screenshot-6.png`
- Plugin code remains under `/trunk/` or versioned tags

## QA Checklist
- Filenames and sizes match exact requirements
- Text legible at common retina and non-retina DPIs
- Visual consistency between icon, banner, and screenshots
- No sensitive URLs or data visible in images

