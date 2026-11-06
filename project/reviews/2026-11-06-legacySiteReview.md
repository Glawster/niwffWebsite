# NIWFF website rebuild content and requirements review

## Source and scope

Baseline: archived public site (6 November 2026), five pages. Earlier planning conversations establish the proposed navigation and events workflow. Original survey answers have not been found in this repository; the survey-derived final decisions must be verified rather than inferred.

## Legacy content mapping

| Legacy content | Proposed destination | Editorial action |
| --- | --- | --- |
| Introduction, suitability, health/social benefits, principles and adaptations | Play / About | Rewrite for clarity; verify rules against NIWFF competition rules |
| Affiliated clubs (Hillsborough, Ards, Bangor, CALA, Shankill, Avoniel, Olympia, BraveHEARTS, Banbridge) | Find a Club, club profiles | Convert to structured records; validate contacts, costs, sessions and accessibility |
| Upcoming competitions and old schedules | Competitions & Events | Archive 2025; obtain 2026/2027 calendar; distinguish upcoming from past |
| News articles and external coverage | News | Split into individual dated articles; preserve original media credits and external links |
| NI representative team profiles and squad | NI Teams | Confirm personnel/squads, age groups, consent and accuracy |
| Gallery imagery | Gallery / articles | Preserve originals where permitted; identify event, photographer, alt text and image permissions |
| Secretary/contact information | Contact / Governance | Confirm current responsible officer and privacy notice |
| External IFA and Facebook links | Resources / About / site footer | Verify and maintain |

## Proposed information architecture

Home; Play / Find a Club; Competitions & Events; International Teams (overview and separate squad pages for age groups); News; Resources; About / Governance; Contact.

Main visitor journey: “I want to play” should answer suitability, location, session, cost and how to contact a club. Website contains authoritative long-lived information; social channels handle community updates.

## Editorial and workflow requirements for confirmation

- Clubs represented by structured data fields: name, contacts, venue, schedule, eligibility, fees, social links, last-reviewed date and confirmation state.
- Club-submitted event: submission -> NIWFF approval -> website publication -> optionally create Facebook content.
- Historic competitions, results and news remain accessible but never appear as upcoming.
- International Teams is a top-level page with individual squad pages (e.g. Over 60, Over 65, Over 70), approved seasonal rosters and management, fixtures/results, gallery and history. Gender/category information must be explicitly verified by NIWFF.
- Contact forms need privacy notice, limited retention, anti-spam and accessible labels.
- Homepage highlights Find a Club, current events, representative teams and recent news.
- Proposed palette from previous design discussions: green, navy, blue and restrained yellow; branding subject to approval.

## Gaps and decisions

1. Locate and incorporate actual completed committee survey feedback; earlier discussion defined the survey, but answers are not present in the repository.
2. Verify current affiliated club list, prices, session times and contact consent before publication.
3. Request current NIWFF rules, tournament regulations, calendar, logos and original media ownership.
4. Confirm domain registrar control, expiry/renewal, DNS and target hosting. Preserve niwalkingfootball.com as canonical domain.
5. Decide content editing and approval roles before choosing static data files versus a CMS.
6. Inspect actual recovered media; 35 first-party image/scene URL references were found, not yet a complete site mirror.

## Migration approach

Keep existing site live; stage replacement separately; test links/accessibility/responsive behaviour; archive historical content; transfer DNS/registration only when access is verified; switch DNS after staging sign-off. Retain rollback configuration.
