WHAT IT IS:


Wrote a bit of code to check the tesla store for changes on used/new cars.
It keeps track of cars added, cars sold and price history.

If any changes are noticed (price change, car sold or added)
It will send an email out.


WHY?

Tesla likes to lower prices every day or so on used cars until they sell, this will keep a nice record of price history and let you know when the price goes down.
I'm using a file based nosql db for simplicity. If i feel like it down the road i might add some code to generate some stats out of it, but i dont expect to need it really.


HOW TO USE:

php 8 (7.4 might be fine)

composer install

Open env.example and follow instructions, need sendgrid api key and a few other things, make sure to copy everything over to .env and fill them in

run by using 'php scraper.php'

ideally set up a cron job to run this every few hours, it only emails on changes.

Configure your queries as you see fit from the tesla store UI, i defined an env var for each one, but you can customize this in the code in TeslaScraper.php
Should be very easy to figure out..