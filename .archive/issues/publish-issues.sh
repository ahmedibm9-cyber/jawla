#!/usr/bin/env bash
# Publish the spec issues to GitHub. Run after: gh auth login -h github.com
set -e
cd "$(dirname "$0")"

create() { gh issue create --title "$1" --body-file "$2" --label "$3"; }

create "Repair the test suite: 13 schema errors + 1 tenancy failure" 01-repair-test-suite.md "bug,high-priority,tests"
create "Out-of-stock flagging flow: rep request → tri-role alarm (REQ-ALM-1…4)" 02-out-of-stock-alarm-flow.md "enhancement,high-priority,beta-blocker"
create "Geofence check-in per decision D-02 (500m, decline out-of-range)" 03-geofence-d02-compliance.md "bug,high-priority,beta-blocker"
create "Rep app: Visits and Orders tabs + list pages (REQ-CMP-4)" 04-visits-orders-tabs.md "enhancement,beta-blocker"
create "Rep in-app notifications bell" 05-rep-notifications-bell.md "enhancement"
create "Warehouse stock CSV import wizard (REQ-STK-1/2)" 06-stock-csv-import-wizard.md "enhancement,beta-blocker,blocked"
create "UI standards sweep: DS components + accessibility/RTL fixes" 07-ui-states-a11y-sweep.md "enhancement,ui"
echo "Done. Note: create labels first if missing: gh label create beta-blocker etc."
