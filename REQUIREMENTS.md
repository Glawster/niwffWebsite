# NI Walking Football Federation Website Requirements

## Backlog

### Events submission and publication workflow

Provide a web-based process that allows member clubs to notify NIWFF about an event they are organising or proposing.

#### Club event submission form

The website shall provide an event submission form for clubs. The form should capture, as a minimum:

- Club name
- Event title
- Event type
- Event date and time
- Venue and address
- Short description
- Eligibility / age categories, where relevant
- Entry or attendance details, including cost where applicable
- Contact details for the organising club
- Registration or information link, where applicable
- Supporting image / poster, where available
- Any additional information required by NIWFF

Submission of the form shall not automatically publish the event.

#### Approval workflow

- Submitted events shall be sent to the NIWFF web team for review.
- The web team shall be able to approve, reject, or request amendments to a submission.
- Only approved events shall be published as NIWFF events.
- The submission should retain enough information to allow the web team to contact the club if clarification is required.

#### NIWFF Events page

Once approved, the event shall be added to an Events page on the NIWFF website.

The Events page should:

- Show upcoming events in date order.
- Provide an event detail view containing the approved information.
- Clearly identify the organising club.
- Support links to registration or further information where supplied.
- Move past events out of the main upcoming-events view while retaining them where appropriate for archive/history purposes.

#### Facebook event

For each approved event, the web team shall also create a corresponding Facebook Event on the NIWFF Facebook presence.

The website workflow should make this easy by presenting the approved event information in a form that can be copied or reused for Facebook, including:

- Event title
- Date and time
- Venue
- Description
- Registration / information link
- Event image or poster

A future enhancement may automate or partially automate creation of the Facebook Event, subject to Facebook/Meta API permissions and platform limitations.

#### Status

Backlog item — requirements definition and implementation planning required.


### International Teams section (all representative age groups)

Provide a prominent **International Teams** top-level navigation item with an overview page at `/international-teams` and dedicated pages for each current NIWFF representative squad. Do not restrict the section to the existing Over-60 player-profile page.

#### International Teams overview

- Introduce the Northern Ireland walking football international programme and how teams represent NIWFF.
- Show a card for each approved/current squad, grouped by age category and, where relevant, men's and women's squads.
- Support age groups such as **Over 60s, Over 65s and Over 70s**, already mentioned in the legacy site, plus any other age groups NIWFF confirms; do not imply that all categories are currently active.
- Link each squad card to a dedicated page; hide draft or inactive squads from the public overview or clearly label them as historical.
- Highlight upcoming international fixtures and recent results using the shared Events/Results content source.
- Link to international-team news, photographs and federation partners where appropriate.

#### Individual team pages

Each team page shall support:

- Team name, gender/category where appropriate, and age eligibility definition (confirmed by NIWFF).
- Team photograph and badge where authorised; accessible descriptive alternative text.
- Current team manager, coaches and officials (subject to verification).
- Current squad, with player name, playing role/position, optional club affiliation and photograph, only with appropriate consent.
- Upcoming fixtures, recent results and tournament history with dates and links to event details.
- Related news, image galleries and notable achievements.
- Contact or enquiry route managed by NIWFF rather than publishing private player contact details.
- Season/year selection or an archive for previous squads so past and present are not mixed.

#### Editorial and data management

- Teams are structured data with stable IDs and URLs, not a single hard-coded player-profile page.
- Multiple age categories and gender divisions can be created, edited, made inactive or archived without changing site code.
- Squad membership, staff, fixtures and results are linked to the relevant team and season.
- No legacy names, positions, photographs or fixtures shall be displayed as current until reviewed and approved by NIWFF.
- The recovered `/player-profiles` URL should redirect to the new International Teams section or an appropriate historical squad page after migration.

#### Acceptance criteria

1. International Teams is available from primary navigation and has its own landing page.
2. An approved team can be added for a new age group without duplicating a page template or changing code.
3. Each published team has a separate accessible, mobile-friendly page, and unpublished squads remain hidden.
4. Fixtures and results shown on team pages match the shared Events/Results records; old fixtures never appear as upcoming.
5. Historical staff and squad information is clearly marked by season and is never presented as current without NIWFF approval.
6. Legacy `/player-profiles` links resolve to relevant new content through a redirect.

#### Status

Backlog item — content model and historical source data prepared; page implementation and NIWFF content approval outstanding.
