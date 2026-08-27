# Xahau definitions

`definitions.json` is the `server_definitions` response of a Xahau node, with
the `status` and `hash` keys removed.

Refresh it after an amendment:

```console
curl -sS -X POST https://xahau.network -H 'Content-Type: application/json' \
  -d '{"method":"server_definitions","params":[{}]}' \
| python3 -c "
import sys, json
r = json.load(sys.stdin)['result']
for k in ('status', 'hash'): r.pop(k, None)
json.dump(r, open('definitions.json', 'w'), indent=2)
"
```

Captured 2026-08-25 from build `2026.6.21-release+3350`: 78 transaction types,
337 fields.

Applications that would rather not wait for a release here can fetch the
definitions at runtime and hand them over with `Xahau::useDefinitions()`.
