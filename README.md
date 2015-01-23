# mailchimp-autoresponder-reports

This tool will help you to track the success of your automation mails or autoresponders in mailchimp. Sends, openrate, klickrate etc. will be tracked over time and you can see if your tweaks to automation mails / autoresponders improve over time.

The tool is called by a cron job in intervals - let's say once a week. It then stores all current data about the autoresponders into a database. After weeks go by and the database gets populated and you can now begin to pull an excel sheet out of the tool with the following information - all on a weekly basis for specific calendar weeks:

- **Sends**
- **Opens**
- **Openrate**
- **Clicks**
- **Clickrate**
- **Unsubscribed**
- **Unsub/Sends**
- **Unsub/Opens**

of all autoresponders. In addition to that it will list the SUM / AVG of all numbers of a specific calendar week (like all sends, opens etc.).

If you have at least data for two consecutive weeks - the tool will also show you the the weekly change of a value in percent.
For example: an autoresponder got opened 100 times in calendar week 10 and opened 120 times in calendar week 11 - the WoW (week over week) change will show an 20% increase in open for that autoresponder.

The report will look similar to this example:

![mailchimp-autoresponder-reports example export](mailchimp_export_screenshot1.png?raw=true "mailchimp-autoresponder-reports example export")

## Installation

To use the mailchimp-autoresponder-reports tool, you have got to install it on a local machine or a web server. The machine does need to have internet access to fetch autoresponder data from mailchimp.


### Prerequisites

* Webserver with PHP Module or PHP CLI
* MySQL Database (will work with any Codeigniter supported database)
* cron or other job scheduler

Most shared hosting machines will cover the above.


### Database settings

Please enter your database settings in the file <code>./application/config/config.php</code>. You can specify two database connections: 1) for local testing and 2) for the production machine. Simply change <code>www.yourdomain.com</code> in line <code>if ($_SERVER['SERVER_NAME'] == "www.yourdomain.com")</code> to the domain of your production server.


### Mailchimp API Key

To access your mailchimp automation / autoresponder e-mails, you need to provide your Mailchimp API key. You can find it by logging into Mailchimp and follow the instructions here: http://kb.mailchimp.com/accounts/management/about-api-keys#Find-or-Generate-Your-API-Key

After you have obtained the API key from Mailchimp, you have to enter it into the <code>./application/config/config.php</code> at the bottom where it reads <code>$config['Mailchimp_API_KEY'] = 'put_your_API_key_here';</code>


### Cronjob for updates

On Unix/Linux based systems, you'd use cron to execute the database update. Our interval should be once a week (like every sunday night at 23:55h or 11.55pm).

<pre><code>
55 23 * * 0 wget -q --spider http://www.yourdomain.com/mailchimp/index.php/mcreports/updatedb
</code></pre>



# Finally! - Get your report

Simply call the url <code>http://www.yourdomain.com/mailchimp/index.php/mcreports/getexport</code> via browser or with wget/curl to fetch an excel document containing all the data. Please mind that you have to wait at least one interval (e.g. one week) to get any data out of the tool. So you might want to call the url <code>http://www.yourdomain.com/mailchimp/index.php/mcreports/getexport</code> right now and schedule a call via cron every week from now.

The report will look similar to this example:

!mailchimp_export_screenshot1.png!