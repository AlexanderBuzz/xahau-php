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

Captured 2026-10-07 from build `2026.6.21-release+3350`: 78 transaction types, 337 fields,
digest `33356850E2B1485D1695F5D0CD191FB44F45A91D63BB31110FDD6806C7120881`.

Applications that would rather not wait for a release here can fetch the
definitions at runtime and hand them over with `Xahau::useDefinitions()`.
