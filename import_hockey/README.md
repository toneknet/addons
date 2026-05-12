# Import Hockey

Import hockey games from [swehockey.se](https://stats.swehockey.se) into your scheduling system. The module parses the game schedule page, calculates shift start/stop times based on configurable offsets, and inserts events into the database after a manual review step.

## Details

- **Version:** 0.1
- **Type:** Module
- **Author:** Martin Tonek
- **Author URI:** https://www.tonek.se

## How it works

The import follows a three-step process:

**Step 1 — Fetch data**
- Go to [stats.swehockey.se](https://stats.swehockey.se) and navigate to the desired league's *Schedule & Results / Schedule* page.
- Paste the URL into the form.
- Select the team, base staffing count (*grundbemanning*), and shift offsets (pass start/slut).
- Click *Ladda upp* to fetch and parse the schedule.

**Step 2 — Review**
- The parsed games are displayed as a preview with calculated start/stop times.
- Rows with missing or preliminary game times (00:00) are highlighted in red.
- Confirm the data looks correct.

**Step 3 — Save to database**
- Click *Kontrollerade och godkända för att läggas till* to insert the events.


## Requirements

- PHP module system with `db`, `input`, and `addJS` helpers available.
- The `simple_html_dom` plugin must be present at `PLUGINPATH . "simple_html_dom/simple_html_dom.php"`.
- Edit access (`own_access['edit']`) is required to use the module.

## Files

| File | Description |
|---|---|
| `import_hockey.php` | Main module class |
| `html/import_hockey.js` | Front-end JS (auto-fills team defaults on team select) |
| `lang/sv.php` | Swedish language strings |
| `index.php` | Module entry point |
