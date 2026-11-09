# Xahau definitions

`definitions.json` is the `server_definitions` response of a Xahau node with the
request-scoped `status` key removed and nothing else touched. The node's own
digest stays in `hash`, so a copy can be told from the node's current set at a
glance; a test checks it is there. The `features` section is the amendment
status at capture time and changes without any change to the codec; a differing
digest can mean no more than that.

Refresh it after an amendment:

```console
curl -sS -X POST https://xahau.network -H 'Content-Type: application/json' \
  -d '{"method":"server_definitions","params":[{}]}' \
| python3 -c "
import sys, json
r = json.load(sys.stdin)['result']
r.pop('status', None)
json.dump(r, open('definitions.json', 'w'), indent=2)
"
```

Captured 2026-11-09 from build `2026.10.13-release+4119`: 77 transaction types, 347 fields,
digest `74146CB48A882D4B211E92FB56659DF9C335997823A69944F65A48714737E5C0`.

Applications that would rather not wait for a release here can fetch the
definitions at runtime and hand them over with `Xahau::useDefinitions()`.
