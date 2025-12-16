# wp-scheduled-content-auditor
Checks what is scheduled, what is late, and what WordPress quietly forgot to publish.  as it does it so often.

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
