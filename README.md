# Trust LATAM – A+ Content Library

This project provides a simple PHP-based interface to browse, preview, and download **A+ content packages** for marketplaces.  
Each product lives in its own folder with an `index.html` and related assets (images, CSS, JS, fonts, etc.), and the library:

- Lists all product folders and files in a clean UI
- Lets users **search** by product ID or name
- Allows sorting by **newest** or **oldest** content
- Generates a **ZIP** of any product folder on the fly
- Offers a **preview mode** with a floating “ZIP” bubble button, without modifying the original exported files

---

## Features

- 📂 **Directory listing**
  - Automatically scans the current directory and lists all subfolders and files.
  - Folders (products) are shown with last modified date.
  - Files are shown with size in MB.

- 🔍 **Search**
  - Client-side search (`JavaScript`) over product IDs and cleaned names.
  - “Search by ID or name” input with highlight and smooth scroll to the first match.

- 🧭 **Sorting**
  - Sort by **Newest First** (default) or **Oldest First**.
  - Sorting is based on the last modified time of each folder (recursive) or file.

- 📦 **ZIP download**
  - `?zip={folder}` builds a ZIP of the given folder using `ZipArchive`.
  - Recursively includes all files and subfolders inside the target folder.
  - ZIP is streamed to the browser and removed from temporary storage afterwards.

- 👀 **Preview mode with bubble button**
  - `?preview={folder}` loads the `index.html` (or `index.htm`) inside the folder.
  - Injects a floating **“ZIP” bubble button** on the rendered HTML that links back to `?zip={folder}`.
  - Original `index.html` and assets remain untouched, so the **exported ZIP does not contain any preview/bubble code**.

---

## Directory Structure

Example structure under the library root:

```text
/resellers-inserts/kabum/
├── index.php          # Main entry point (library UI + ZIP + preview)
├── fonts/             # Shared webfonts (optional, e.g. WOFF/WOFF2)
│
├── 24549-yvi/
│   ├── index.html     # Product content
│   ├── images/        # Product-specific images
│   ├── css/           # Product-specific styles
│   └── fonts/         # Product-specific fonts (optional)
│
├── 24550-halyx/
│   ├── index.html
│   ├── images/
│   ├── css/
│   └── fonts/
└── ...
