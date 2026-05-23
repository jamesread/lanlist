# OliveTin: organizer favicon async job

**`bindingId`** in OliveTin must match **`OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH`** in `includes/config.php`. Argument names (**`jobId`**, **`organizerId`**) must match what [`public/misc.php`](../public/misc.php) passes to **`OliveTinClient::startAction`**. See also [`favicon-fetch-job-queue-plan.md`](favicon-fetch-job-queue-plan.md).

OliveTin’s YAML changes between releases—confirm **`actions`** / **`bindings`** / templating in the [OliveTin documentation](https://docs.olivetin.app/).

## Example `config.yaml` fragment (illustrative)

```yaml
# Example only — align field names with your OliveTin version.
actions:
  - title: "Lanlist: fetch organizer favicon"
    shell: run-favicon-job.sh --job-id {{ jobId }} --organizer-id {{ organizerId }}
    id: favicon-async
    #   cd /path/to/lanlist/scripts && \
    #   MYSQL_USER=... MYSQL_PASS=... MYSQL_DATABASE=lanlist \
    #   ./run-favicon-job.py --job-id {{ jobId }} --organizer-id {{ organizerId }}
```

## Runtime on the OliveTin host

- Repo checkout with `scripts/` and `public/` (PNG path: `public/resources/images/organizer-favicons/{id}.png`).
- Python 3 env for [`scripts/favicon-get.py`](../scripts/favicon-get.py).
- **`magick`** (ImageMagick) on `PATH`.
- **`MYSQL_*`** for the runner process (same as other favicon tooling).
