--
-- table structure for table `mcdata`
--

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

