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

Please enter your database settings in the file <code>./application/config/database.php</code>. You can specify two database connections: 1) for local testing and 2) for the production machine. Simply change <code>www.yourdomain.com</code> in line <code>if ($_SERVER['SERVER_NAME'] == "www.yourdomain.com")</code> to the domain of your production server.

1) Please setup your MySQL database. You can create a new user solely for the mailchimp-autoresponder-reports tool or re-use an existing one. The application will only need one table to store the data in.

2) Connect to MySQL with your user and create the table with the following script:
 
```
DROP TABLE IF EXISTS `mcdata`;
CREATE TABLE IF NOT EXISTS `mcdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mc_id` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  `calendar_week` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `web_id` int(11) NOT NULL,
  `list_id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `create_time` datetime DEFAULT NULL,
  `send_time` datetime DEFAULT NULL,
  `emails_sent` int(11) NOT NULL,
  `summary-opens` int(11) DEFAULT NULL,
  `summary-clicks` int(11) DEFAULT NULL,
  `summary-unsubscribes` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
```


### Mailchimp API Key

To access your mailchimp automation / autoresponder e-mails, you need to provide your Mailchimp API key. You can find it by logging into Mailchimp and follow the instructions here: http://kb.mailchimp.com/accounts/management/about-api-keys#Find-or-Generate-Your-API-Key

After you have obtained the API key from Mailchimp, you have to enter it into the <code>./application/config/config.php</code> at the bottom where it reads <code>$config['Mailchimp_API_KEY'] = 'put_your_API_key_here';</code>


### Cronjob for updates

On Unix/Linux based systems, you'd use cron to execute the database update. Our interval should be once a week (like every sunday night at 23:55h or 11.55pm).

<code>
55 23 * * 0 wget -q --spider http://www.yourdomain.com/mailchimp/index.php/mcreports/updatedb
</code>


### Installation guide for Ubuntu 14.04.3 server

Please follow this guide to install mailchimp-autoresponder-reports on a fresh installation of Ubuntu 14.04.3. You can also watch the setup process tutorial video on youtube:

[![Install mailchimp-autoresponder-reports on Ubuntu server](http://img.youtube.com/vi/Ap-8OE_AaoE/0.jpg)](http://www.youtube.com/watch?v=Ap-8OE_AaoE)

You will be prompted for your root / user password several times.

1. Make sure your current Ubuntu machine is up to date and install all the latest updates. The following one-liner will just update your installation:

```
sudo apt-get update; sudo apt-get upgrade; sudo apt-get dist-upgrade; sudo apt-get clean all; sudo apt-get --purge autoremove;
```

2. Now install all dependencies necessary (OpenSSH, LAMP Server, git, wget, curl)

```
sudo apt-get install apache2 libapache2-mod-php5 php5 php5-mysql php5-curl mysql-server git wget curl
```

3. Get the sourcecode from github. I assume you install mailchimp-autoresponder-reports within your home directory.

```
cd ~
git clone https://github.com/maknesium/mailchimp-autoresponder-reports.git
```

4. Now, download the dependencies for mailchimp-autoresponder-reports (Mailchimp PHP API and PHPoffice)

```
cd ~/mailchimp-autoresponder-reports/vendor
mkdir craigballinger
cd craigballinger
git clone https://github.com/craigballinger/mailchimp-api-php.git
```

```
cd ~/mailchimp-autoresponder-reports/vendor
mkdir phpoffice
cd phpoffice
git clone https://github.com/PHPOffice/PHPExcel.git
mv PHPExcel/ phpexcel
```

5. We have to setup the basic MySQL database now....

```
mysql -u root -p
(enter your mysql root password)
```

...and when you're promted by the <code>mysql></code> promt now, you have to create a new database and a new database user by typing in the following statements:

```
create database mailchimp_db;
CREATE USER 'mailchimp'@'localhost' IDENTIFIED BY 'mailchimp';
GRANT ALL PRIVILEGES ON mailchimp_db.* TO 'mailchimp'@'localhost';
FLUSH PRIVILEGES;
exit;
```

Note that we set the database name to **mailchimp_db**, the database user to **mailchimp** with the password **mailchimp**. You SHOULD use your chosen password here! Don't go with the one from this tutorial! 

6. We now have to import the SQL database script in order to have the database structure setup correctly.

```
cd ~/mailchimp-autoresponder-reports
mysql -u mailchimp -p mailchimp_db < init_database.sql
(enter mailchimp user password)
```

And verify that things worked:

```
mysql -u mailchimp -p mailchimp_db
(enter mailchimp user password)
```

and when you see the <code>mysql></code> prompt then enter the following command which should show the imported database structure:

```
SHOW CREATE TABLE mcdata;
exit;
```

7. We're now proceeding to the configuration of the application.

```
nano mailchimp-autoresponder-reports/application/config/database.php
```

and enter your database settings accordingly:

```
$db['development']['username'] = 'mailchimp';
$db['development']['password'] = 'mailchimp';
$db['development']['database'] = 'mailchimp_db';
```

Now we have to set the Mailchimp API key. They key can be obtained from within the mailchimp settings as decribed here in section "Find or Generate Your API Key": http://kb.mailchimp.com/accounts/management/about-api-keys

When you got your key, save it inside the configuration file as described here:

```
nano mailchimp-autoresponder-reports/application/config/config.php
```

And paste your key inside this value. It should look similar to this:

```                          
$config['Mailchimp_API_KEY'] = '123456789123456789abcdefghijklmn-us7';
```

8. Now, we need to link the mailchimp-autoresponder-application to the web server

```
sudo ln -sf /home/user/mailchimp-autoresponder-reports /var/www/html/mailchimp
```

Just in case.... we restart the webserver to make sure everything is working to the latest configuration:

```
sudo service apache2 restart
```

You can now call from within a browser the two URLs:

1. http://localhost/mailchimp/index.php/mcreports/updatedb > to update the database by collection all autoresponder data from mailchimp
2. http://localhost/mailchimp/index.php/mcreports/getexport > to generate the report and see how your autoresponders have perfomed over time

9. Finally, setup your cron job to run the application every week (here it runs every Sunday at 23:55 / 11:55pm) to collect the data from mailchimp.

Open crontab with:

```
crontab -e
```

and put this line at the bottom of the file:

```
55 23 * * 0 wget -q --spider http://localhost/mailchimp/index.php/mcreports/updatedb
```


# Finally! - Get your report

Simply call the url <code>http://www.yourdomain.com/mailchimp/index.php/mcreports/getexport</code> via browser or with wget/curl to fetch an excel document containing all the data. Please mind that you have to wait at least one interval (e.g. one week) to get any data out of the tool. So you might want to call the url <code>http://www.yourdomain.com/mailchimp/index.php/mcreports/getexport</code> right now and schedule a call via cron every week from now.

The report will look similar to this example:

![mailchimp-autoresponder-reports example export](mailchimp_export_screenshot1.png?raw=true "mailchimp-autoresponder-reports example export")
