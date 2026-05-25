# Smart-Hybryd-Cache

## Installing from GitHub Actions

Open the workflow run on GitHub and download the `smart-hybrid-cache` artifact.
GitHub delivers it as `smart-hybrid-cache.zip`. Upload that file as-is in
WordPress under **Plugins > Add New > Upload Plugin** — do not extract it
first, and do not upload any ZIP nested inside another ZIP.

The artifact ZIP contains the plugin files (`smart-hybrid-cache.php`,
`includes/`, `assets/`, `readme.txt`, …) directly at its root, so WordPress
installs it as the `smart-hybrid-cache` plugin.

## Building locally

Run:

```sh
make build
```

Then upload `build/smart-hybrid-cache.zip` in WordPress.