# IdiotsGuide  
WP Scheduled Content Auditor

This is the guide for the version of me who has three tabs open, a client pinging on chat, and no patience left for WordPress lying about scheduled posts.

It is not about being stupid. It is about being tired.

## What problem this solves

Normal WordPress behaviour:

- You schedule a post for 3am.
- WordPress promises to publish it.
- Cron fails quietly.
- The morning comes. The post is still scheduled.
- Nobody tells you. The front page looks stale and the client is confused.

The Scheduled Content Auditor gives me a simple page that answers one question:

> "Is anything scheduled that should already be live and is not?"

Plus a small clue about whether cron is even trying.

## Where to find it

After activating the plugin:

1. Go to the WordPress admin.
2. Hover over Tools.
3. Click Scheduled Auditor.

If it does not show up, you probably do not have permission to edit posts. Or something else has mangled your admin.

## The page, in plain language

The page has three main parts.

### 1. Cron summary

At the top you will see a little block with cron info.

- If there are publish events queued, you will see how many and when the next one is due.
- If it says there are no publish_future_post events, either:
  - Nothing is scheduled, or
  - Cron is not doing its job.

The plugin does not repair cron. It just points at it and says “you might want to look at that”.

### 2. Late or likely missed schedule

This is the important table.

Anything in here is:

- A scheduled post.
- That should already have gone live by now.
- But has not.

Columns:

- Title  
  Click this if you want to open and inspect or edit the post.
- Type  
  Post, page, or some custom type that someone thought was a good idea.
- Author  
  Which user created it. Helpful when you need to ask “did you really want this to go out?”
- Scheduled for  
  When WordPress was meant to publish it.
- Age  
  How late it is, in minutes, hours, or days.
- Actions  
  Your panic buttons.

Actions available:

- Publish now  
  Sets the post to publish status and makes it live immediately.
- Bump +1 hour  
  Adds one hour to the scheduled time and hopes cron behaves next time.

When to use which:

- If it should have gone out already and you are fine with that, use Publish now.
- If you want to delay it slightly because the timing is now wrong, use Bump +1 hour and then maybe adjust the exact time manually in the edit screen.

### 3. Upcoming scheduled content

This table shows content that is still in the future. It is not late yet.

This is mainly for sanity:

- If you thought you scheduled something for tomorrow and it is not here, you probably did not press the right button.
- If you see something scheduled far out that you forgot about, this is your reminder.

No actions here. Just awareness.

## Things to be careful about

Yes, this plugin gives you power. Power always comes with ways to make a mess.

### Do not click Publish now randomly

If you see a long list of late posts:

- Check the titles before publishing.
- Ask yourself if each one should still go live.
- Some of them may be old drafts that someone scheduled by mistake.

### Remember that publishing is public

The moment you click Publish now:

- The post becomes visible according to whatever your theme and routing are doing.
- Feeds may pick it up.
- Search engines might crawl it.

Do not use this button as a toy on a production site.

### Be aware of time zones

WordPress stores dates in a mix of local and GMT values. Users set whatever timezone they feel like.

This plugin shows times using the site’s configured date and time format. If it looks wrong, check:

- Settings  
  General  
  Timezone
- Whether whoever configured the site picked the wrong one.

### Do not treat this as a fix for bad hosting

If your cron is broken because:

- The host does not run PHP often enough.
- Someone disabled wp-cron and never set up a real cron job.
- Traffic is low and nothing hits the site at 3am.

This plugin will not magically fix that. It will just make the problem obvious.

You still need to:

- Configure a proper server cron to call wp-cron.php, or
- Use a cron monitoring service, or
- Accept that your scheduled posts are at the mercy of random page views.

## Simple mental model

If your brain is tired, use this model.

- Green zone  
  Upcoming scheduled content. Nothing to panic about.

- Amber zone  
  Cron summary looks a bit too empty. Maybe nothing is scheduled. Maybe cron is dead.

- Red zone  
  Late or likely missed schedule table is not empty. This is the list that actually matters.

Check the red zone first. Always.

## Safe routine to follow

When you remember this exists, do:

1. Open Tools  
   Scheduled Auditor.
2. Look at the Late or likely missed schedule table.
3. For each row:
   - Decide if it should still go live.
   - If yes, click Publish now.
   - If not, edit it, un-schedule it, or move it to drafts.
4. Glance at Upcoming scheduled content to see if anything important is on the horizon.
5. Glance at cron summary and decide if you need to go nag whoever manages the server.

Takes a couple of minutes. Saves that awkward “why is the home page stale” conversation.

## When not to use this

You should not be mashing these buttons:

- During a live launch without backups.
- On a site you do not understand, in a language you cannot read.
- When your internet is flaky and you are not sure if the button went through.
- If you are already debugging something fragile.

This tool is for controlled cleanups and quick fixes, not for frantic crisis mode while everyone is yelling.

## Final thoughts

If scheduled posts worked reliably on every host, this plugin would be unnecessary.

They do not. So here we are.

Use it when you need to know, quickly and calmly, what is supposed to be live and is not. Use it sparingly. And if you find yourself in here every week, maybe it is time to have a serious talk with your hosting provider.
LICENSE
text
Copy code
WP Scheduled Content Auditor
Copyright (c) 2024 TABARC-Code

This program is free software. You can redistribute it and or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version.

This program is distributed in the hope that it will be useful, but without any warranty, without even the implied warranty of merchantability or fitness for a particular purpose.

You should have received a copy of the GNU G You break it youron your own type of thing.eneral Public License along with this program. If not, see:
https://www.gnu.org/licenses/gpl-3.0.html
