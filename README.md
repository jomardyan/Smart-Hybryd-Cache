# Smart-Hybryd-Cache

## Installing from GitHub Actions

Download the `smart-hybrid-cache` workflow artifact and upload the downloaded
`smart-hybrid-cache.zip` file in WordPress under **Plugins > Add New > Upload
Plugin**.

Do not upload a ZIP that contains another ZIP file. WordPress must see the
`smart-hybrid-cache.php` plugin file directly inside the uploaded package.

## Building locally

Run:

```sh
make build
```

Then upload `build/smart-hybrid-cache.zip` in WordPress.