# Current Increment

## Objective
Capture the next admin and public-listing improvements as a tracked backlog for a future implementation increment.

## Scope
- Add an admin workflow to list already approved club records and open them for editing.
- Add an admin workflow to edit event records after submission/approval.
- Update event display formatting so public event dates render as `dd/mm/yyyy`.
- Update calendar day rendering so events are grouped and displayed by club first, then event title.

## Acceptance Work
- **Admin approved clubs list + edit**
  - Add an "Approved Clubs" view in admin.
  - Include search/filter by club name and area.
  - Provide edit/save flow for approved club payload fields.
  - Preserve review/audit history when edits are made.
- **Admin event edit mechanism**
  - Add an "Events" management view in admin for approved and pending events.
  - Allow editing event payload fields and saving updates.
  - Keep status handling (`pending`, `approved`, `changes_requested`, `rejected`, `withdrawn`) intact.
- **Event date format**
  - Render event dates in public views as `dd/mm/yyyy`.
  - Apply consistently in list/timeline cards and calendar-related displays.
- **Calendar ordering/content**
  - For each day cell/detail, display event lines as:
    - `<club name>`
    - `<event title>`
  - Sort/group events by club name within the same day.

## Verification Still Required
- Confirm desired sorting for club names (A-Z vs custom priority).
- Confirm whether date format applies to admin-only views or public views only.
- Confirm if historical archived events should be reformatted in the same way.

## Immediate Next Action
Plan implementation tasks and sequence changes across `admin/index.php`, `api/public.php`, and `assets/site.js`.
