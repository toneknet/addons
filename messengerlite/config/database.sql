CREATE TABLE `m_message_group` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) NULL default NULL,
  `created_by` int(11) NOT NULL default '0',
  `companyid` int(11) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  `ordning` int(2) NULL default '99',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `m_message_group_member` (
  `id` int(11) NOT NULL auto_increment,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `can_reply` tinyint(1) NOT NULL default '1',
  `companyid` int(11) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  `ordning` int(2) NULL default '99',
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `m_message_thread` (
  `id` int(11) NOT NULL auto_increment,
  `groupid` int(11) NOT NULL,
  `user_sender_id` int(11) NOT NULL,
  `subject` varchar(190) NOT NULL,
  `status` varchar(20) NOT NULL default 'open',
  `created` datetime NULL default NULL,
  `updated` datetime NULL default NULL,
  `companyid` int(11) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  `ordning` int(2) NULL default '99',
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`),
  KEY `user_sender_id` (`user_sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `m_message` (
  `id` int(11) NOT NULL auto_increment,
  `threadid` int(11) NOT NULL,
  `sender_userid` int(11) NOT NULL,
  `message` text NOT NULL,
  `created` datetime NULL default NULL,
  `companyid` int(11) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  `ordning` int(2) NULL default '99',
  PRIMARY KEY (`id`),
  KEY `threadid` (`threadid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
