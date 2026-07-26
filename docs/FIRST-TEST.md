# First test

A short walkthrough of the system as it stands. It takes about ten minutes.

Read the "What is real and what is not" section before you start, so you know
what you are looking at.

---

## What is real and what is not

**Real:** the arbitrage engine. It compares odds across bookmakers, finds cases
where the prices let you cover every outcome and still profit, splits the stake
so the return is the same whichever result comes in, and tracks what you placed
and what you earned. The maths is covered by 23 automated tests.

**Not real yet:** the odds themselves. No connection to Sisal, Eurobet, Snai or
any other bookmaker exists. The prices you will see are simulated and move every
two minutes so the screen behaves like a live market.

This matters. The numbers on screen are correct arithmetic on invented prices.
The engine does not know or care where prices come from, so when a real feed is
connected nothing about the detection logic changes.

---

## 1. Log in

Go to **https://betting.ispledger.com** and press **Open dashboard**.

Username `admin`, password `admin`.

Change this password straight away in the **Users** tab. The site is public.

Use the **EN / IT** switch at the top right if you prefer Italian.

---

## 2. Overview

The first screen shows four numbers:

- **Bookmakers tracked** how many operators are configured, currently 33
- **Events today** upcoming matches with stored prices
- **Active surebets** opportunities open right now
- **Profit / loss** your running result across placed bets

Underneath is a list of the best current opportunities.

The active surebet count changes on its own every couple of minutes. That is the
simulated market moving. In a real deployment it moves because the bookmakers
moved their prices.

---

## 3. Surebets, the main screen

Open the **Surebets** tab. Each card is one opportunity and shows:

- the match and the market, for example Over/Under 2.5
- the **ROI**, your profit as a percentage of what you put in
- one row per outcome: which bookmaker has the best price, what the price is,
  how much to stake there, and what that leg returns

At the bottom of each card: total staked, payout and profit.

Two controls at the top:

- **Minimum ROI** hides anything below the percentage you set. Set it to 1 to
  see only the stronger opportunities.
- **Bankroll** is the total you would put across both legs. Change it from 1000
  to 5000 and every stake recalculates.

### The thing worth checking yourself

Look at any card and confirm the last column. Every leg returns close to the
same amount. That is the whole point: the outcome of the match does not change
what you get back.

Then check the arithmetic. Take the two prices, divide 1 by each, and add them.
For example with prices 1.72 and 2.48:

```
1 / 1.72 = 0.5814
1 / 2.48 = 0.4032
total    = 0.9846
```

Below 1 means guaranteed profit. `1 / 0.9846 = 1.0156`, so 1.56% return, which
is what the card shows. Above 1 means the bookmakers' margin wins and there is
no opportunity, which is the normal case.

---

## 4. Place a bet

On any card, press **Place bet**.

This records the position. It does **not** place anything with a bookmaker.
Nothing in this system talks to Sisal or Eurobet. It is your ledger.

You land on the **Bets** tab, where you will see the bet with both legs, the
price and stake locked in at the moment you pressed the button. Prices are
frozen deliberately, so later market movement cannot distort what you actually
committed.

---

## 5. Settle it

Still on the **Bets** tab, find your open bet. In the last column choose which
outcome won and press **Settle**.

The profit appears immediately.

### The test that proves the concept

Place two bets on the same opportunity, then settle one on each outcome.

Both come back profitable. In our test a 1000 stake returned 15.63 profit if
OVER won and 15.61 if UNDER won. A difference of two cents, purely from rounding
stakes to whole cents.

That is arbitrage. You are not predicting the result. You are covering every
result at prices that add up in your favour.

---

## 6. The other tabs

- **Events** every fixture, how many bookmakers quote it, and the best price for
  each outcome. Useful for spotting matches where too few bookmakers are
  quoting for an opportunity to exist.
- **Users** add people and set passwords. Any user can place and settle bets.
  Only admins can add users or change settings.
- **Settings** company name, language and timezone.
- **Automation rules**, **Bookmakers**, **Activity log** are placeholders. They
  are the slots the next stage drops into.

---

## What to tell us after testing

1. Is the surebet card showing what you need, or is something missing that you
   check before placing?
2. Is the stake split presented the way you work, or would you rather enter a
   stake per bookmaker?
3. Which bookmakers do you actually place on, out of the 33 configured?
4. Do you need Over/Under and 1X2 only, or other markets too?
5. Do you want alerts by Telegram, email or phone push, and from what ROI?

---

## The one open question

The system needs a source of real odds. Two options:

**A licensed odds feed.** A company sells structured prices from many
bookmakers. Reliable, legal, and it plugs into what already exists. It costs a
monthly fee and coverage of the specific Italian bookmakers has to be confirmed
before committing.

**Reading the bookmaker sites directly.** No monthly data fee, but the sites
prohibit it in their terms, several actively block it, and it breaks whenever
they change their pages, so it needs ongoing repair.

Everything in this test works the same way under either choice. This is the
decision that needs to be made before the system can show real money.
