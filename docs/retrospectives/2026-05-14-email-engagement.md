# Email engagement audit & redesign — retrospective

**Date:** 2026-05-14
**Scope:** One working session redesigning the engagement-email system end-to-end (volume, recognition, operations).

---

## Part 1: The project and the challenge

### What Hartverwarmers is

A Dutch-language Laravel platform built for activity coordinators in Flemish residential care homes. The users — called "animatoren" or "begeleidsters" in the field — are mostly women aged 35–55, working in woonzorgcentra (nursing/elderly homes) across Flanders. They organize daily activities for residents: crafts, music sessions, cognitive games, themed celebrations. The job is emotional, physical, and creative all at once. They work in shifts, visit the platform during breaks or short preparation windows, and most aren't tech-natives.

The platform exists because every coordinator is **reinventing the same wheel alone**. One in Ghent designs a Mother's Day activity from scratch; another in Bruges is doing the same that week, never knowing the other exists. Hartverwarmers is the bulletin board they all share — coordinators upload "fiches" (practical write-ups of activities that actually worked with their residents) and others adapt them for their own context. The content is structured around the **DIAMANT** pedagogical model: seven goals — Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie, Talent — that frame what makes an activity meaningful for older adults.

It's pre-launch in spirit: ~5,000 user accounts in the database (mostly seeded/imported from a prior data migration), no monthly newsletter has actually been sent yet, and the founders — Frederik and Maite — are tuning the engagement loop *before* the first real send. That timing is important: it means design changes can still be aggressive without breaking trust with a live audience.

### The state of the email system

When we started, the platform had nine distinct engagement email types layered up over months of building:

- **Four onboarding emails** (welcome on signup, curated activities on day 3–5, top-5 fiches on day 7–14, a "you've downloaded 5 — wanna give back?" prompt)
- **Three bookmark milestones** (first bookmark on your fiche, then 10, then 50)
- **One comment digest** (configurable daily, weekly, or never)
- **One monthly newsletter** (cohort-based, never actually sent)

Each was thoughtfully built in isolation. But the *system* — the way these emails compose for a single user across time — had never been audited end-to-end. A new active user in their first month could plausibly receive 6–8 emails. There was no global cap. The comment digest defaulted to daily. One subject line ("time to give something back?") subtly framed downloading as a debt. The monthly newsletter, once it started sending, had no exit ramp for dormant users — it would just keep going forever.

### The challenge, in three layers

What surfaced through the audit wasn't one problem but three, each at a different layer of the system:

**1. The volume layer — spam risk.** Were too many emails landing in too short a window for too little benefit? The audit produced five user-journey simulations that exposed the worst-case pile-ups (a new contributor in week two could receive welcome + curated + first-bookmark + milestone + comment digest within a few days, plus a newsletter at day 30). Real risk, especially because the audience has *low* spam tolerance — care workers aren't going to triage; they unsubscribe or filter.

**2. The recognition layer — engagement gap.** The mirror question: were *highly engaged* contributors getting enough back? After the 50-bookmark milestone, the system went silent on them. Their fiches kept getting saved, commented on, promoted to "diamantje" status by Frederik and Maite — but contributors never heard about any of it. The platform's most valuable users were operating on faith.

**3. The operational layer — schedule collision.** Once the engagement system was healthier, an operational detail became visible: every email-sending command was scheduled at 08:00 — landing in care-home staff's busiest handover window, where phones are ignored. Same trigger time also wasted the 24h cap design (collisions were resolved by arbitrary scheduler order rather than by importance).

### Why the stakes were specific

A few constraints shaped every decision:

- **Audience low-tech and time-poor.** "It works if they understand the email in 5 seconds during a coffee break." No nested settings, no academic copy.
- **Flemish-Dutch (not Holland Dutch) with peer voice.** "We hebben jouw fiche uitgekozen" not "Het curatorenpanel heeft besloten". The platform is a colleague, not an authority. Care vocabulary: "bewoners" not "patiënten".
- **Two-person founding team.** No room for a moderation queue or a manual outreach ritual that scales linearly with users. Whatever we built had to run itself.
- **Pre-launch domain reputation.** Sending newsletters to 5,000 dormant accounts forever — even before the first send — would burn deliverability before launch. The inactivity gate wasn't optional; it was a launch prerequisite.

That's the soil. The session's value wasn't "we shipped seven commits" — it was that we left each layer (volume, recognition, operations) with a defensible answer to **"why does this exist, who is it for, and when does it stop?"**.

---

## Part 2: The method we used

We ran the same loop three times. Each pass started from a worry, not a feature request.

### Pass 1 — "Am I becoming spammy?"

A screenshot of nine email types. No code written first. The deliverable was:
- A catalogue of every email's trigger, content, and gating
- Five user-persona simulations through inboxes over months (Marleen the curious lurker, An the active downloader, Lieve the hit-fiche author, Sofie the power contributor, Karen the dormant returner)
- Four ranked issues by severity × likelihood

That output let the founder triage. P1, P2, P4 picked; P3 parked. P4 refined in conversation (the "give every user 3 newsletters before the inactivity gate" hybrid only emerged because the founder pushed back on the first proposal). Then we shipped.

### Pass 2 — "What about the opposite problem?"

Same loop, mirrored. The contributor side of the platform was under-fed. The deliverable was:
- Six lever types and ~15 specific ideas
- Top 5 ranked by impact-per-effort + brand fit
- Clarifying questions answered one at a time, each with concrete options + reasoning + a recommendation (recognition vs forward-push? first-published vs first-created? recognition-only or spotlight? backfill or forward-only? re-fire skipped milestones?)

Answers came fast because the options were concrete. Then spec → plan → SDD execution.

### Pass 3 — "Wait, everything fires at 8am."

The founder spotted a pattern in the freshly shipped code. The response:
- Pulled the actual schedule
- Reasoned about cap-priority interaction
- Proposed a staggered schedule ordered by cap-priority (rarest email type fires first)

Shipped in 5 minutes.

### The pattern that made it work

We used the LLM for **exploration, not for typing**.

The phases each had a different deliverable and rigor:

| Phase | Output | Question being answered |
|---|---|---|
| Audit | Persona simulations + ranked issues | "What's actually wrong?" |
| Triage | Picks + parked items + open questions | "What's worth solving?" |
| Brainstorm | Options with recommendations | "How could we solve it?" |
| Spec | Locked decisions + edge cases | "What exactly are we building?" |
| Plan | Bite-sized TDD tasks | "How does an engineer execute this?" |
| Implement | Code + tests + commits | "Does it work?" |
| Review | Issues fresh eyes catch | "Did we build what we meant to?" |

A common failure mode with LLMs is collapsing all of these into one phase — "build this thing" — which produces shippable code that solves the wrong problem. We didn't. Each phase had its own conversation and its own output.

### Why this style suits LLMs specifically

Three things humans are slow at, LLMs are fast at:

1. **Imagining many user journeys quickly.** Simulating Marleen-over-six-months is essentially free for an LLM and exhausting for a human. Five personas in pass 1, even though only one was the founder.
2. **Being a patient interlocutor.** "One question at a time with reasoning" matches how the brainstorming skill is designed. A human collaborator would tire of this; an LLM doesn't.
3. **Holding asymmetric context.** The LLM was forgetting nothing across the session — every prior decision available, every spec section consistent. That's what made the spec/plan documents writable in one shot.

### Three things the founder did that made it work

These are non-trivial. Worth naming:

- **Came with worries, not specs.** "I'm afraid of becoming spammy" and "highly engaged people don't get feedback" are diagnoses, not work orders. The diagnosis framing keeps the LLM in analyst mode rather than producer mode.
- **Pushed back on recommendations.** The 3-cycle grace window only existed because the founder said "with option A, dormant users never get re-engaged". The initial LLM proposal was incomplete; that friction made it better.
- **Spotted things mid-flow.** The 08:00 collision wasn't on the LLM's radar — the founder noticed because they were reading the actual schedule, not just the abstract design. Partnership signal, not an oversight saved.

### When to reuse this pattern

Good fit:
- Open-ended worries ("our X feels off")
- Decisions where the trade-offs are non-obvious
- Systems where downstream behavior depends on interlocking rules (like the 24h cap)
- Refactors where you want to map the territory before moving

Less good fit:
- Bug fixes with a clear repro (just fix it)
- Implementations of fully-specified features (just build it)
- Time-boxed prototypes (skip the spec)

---

## What shipped

Eight commits, +39 tests, 1093 passing total. The commits themselves matter less than the **library of conscious decisions** behind them:

- warmer mail_3 subject line (no debt framing)
- weekly comment-digest default + backfill of existing daily users
- newsletter inactivity gate after a 3-cycle grace window
- click-tracking route so anonymous newsletter clicks count as activity
- global 24h cap across non-transactional emails (with explicit exemptions)
- instant diamantje award notification
- yearly contributor anniversary email anchored on first-published-fiche date
- staggered morning schedule (07:00 → 11:30) ordered by cap-priority

Each of those is defensible. That's the deliverable.
