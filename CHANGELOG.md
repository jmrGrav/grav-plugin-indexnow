## v1.0.1
### 17-04-2026
* Add default indexnow.yaml for GPM compliance (defaults only, no secrets)
* Remove indexnow.yaml from .gitignore — user override goes in user/config/plugins/

# Changelog

## v1.0.0
### 17-04-2026
* Initial release
* Automatic URL submission to IndexNow (Bing/Yandex) on every page save
* Supports onAdminAfterSave and onMcpAfterSave hooks
* Submits 3 URL variants per page (canonical, /fr, /en)
* Full traceability in grav.log
