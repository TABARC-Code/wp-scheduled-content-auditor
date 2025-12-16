<p align="center">
  <img src=".branding/tabarc-icon.svg" width="180" alt="TABARC-Code Icon">
</p>

# WP Scheduled Content Auditor

WordPress loves to pretend scheduled posts are a solved problem.  
Then it quietly misses one at 3am and you get to explain to a client why their big announcement is still in the future.

This plugin exists so I can see, in one place:

- What is scheduled
- What is probably late
- What cron thinks it is doing
- And a couple of panic buttons to fix things

No dashboards full of graphs. Just a blunt little auditor.

## What it does

- Lists all scheduled posts across public post types
- Splits them into:
  - Late or likely missed schedule
  - Upcoming, still in the future
- Shows:
  - Title
  - Type
  - Author
  - Scheduled time
  - How late they are
- Gives simple actions for late posts:
  - Publish now
  - Bump schedule by one hour
- Shows basic cron info for publish events:
  - How many publish_future_post events exist
  - When the next one is due

It does not try to fix cron. It just tells me when cron is clearly asleep at the wheel.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer (PHP 8 recommended)
- A user who can access Tools and publish posts

## Installation

Clone or download the repository:

```bash
git clone https://github.com/TABARC-Code/wp-scheduled-content-auditor.git
Drop it into:

text
Copy code
wp-content/plugins/wp-scheduled-content-auditor
Then in the admin:

Go to Plugins

Activate “WP Scheduled Content Auditor”

Visit Tools
Scheduled Auditor

If that menu item does not show up, check your capabilities and whether someone has mangled your Tools menu.

How to use it
Cron summary
At the top you will see a small cron summary.

If there are publish_future_post events queued, it will tell you how many there are and when the next one should run.

If there are none, either:

Nothing is scheduled, or

Cron is on strike, or

Your host has broken pseudo cron again

This is not deep monitoring. It is just a quick reality check.

Late or likely missed schedule
This table is the fun part. Anything in here:

Is still marked as status future

Has a scheduled date in the past, with a tiny grace buffer

So either cron did not fire, or something went wrong during publish.

For each row you can:

Click the title to edit the post, if you want to inspect it first

Hit “Publish now” if you have now given up on the schedule

Hit “Bump +1 hour” if you want to give cron one more chance to act like a grown up

The age column tells you roughly how late it is:

Minutes late

Hours late

Days late for properly abandoned content

Upcoming scheduled content
The second table is less dramatic.

These are posts that are still properly in the future.

They exist mainly so you can check that you actually have something coming up tomorrow or next week.

If you see a very important post here and know your cron is unreliable, this is your reminder to fix cron before it embarrasses you.

What it does not do
It does not change cron configs.

It does not move posts out of the trash, reschedule from drafts or anything clever.

It does not pretend to recover posts that never got scheduled correctly in the first place.

It does not send notifications or emails.

This is observability with two big red buttons, not a full scheduler.

Safety notes
Only users with permission to publish and edit posts can run the actions.

“Publish now” changes status to publish and updates the publish time to now.

“Bump +1 hour” just adds an hour to the existing scheduled time.

If you click these on the wrong post, that is on you. Take backups. Test on staging.

If you are dealing with high traffic or critical content, treat every “Publish now” like a deployment.

Roadmap
Things I might add if I feel responsible later:

Pagination for oversized sites that schedule absolutely everything

Filters and hooks so missed schedule events can be logged externally

A small dashboard widget with “you have N late posts”

A cron configuration hint, for hosts that are predictably bad

Per post type controls and defaults

Things that probably will not happen:

Auto fixing cron for you do it your self

Background auto publishing of missed content without human review

Fancy graphs
