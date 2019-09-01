<?php
// 2018-Aug-18

$config_file = './config.yml';
$config = yaml_parse(file_get_contents($config_file));

$globals = array_key_exists('globals', $config) ? $config['globals'] : null;
$debug = array_key_exists('debug', $globals) ? $globals['debug'] : false;
$hosts = array_key_exists('hosts', $config) ? $config['hosts'] : null;
$folders = array_key_exists('folders', $config) ? $config['folders'] : null;

if ($debug) { echo "DEBUG ON\n"; }

foreach ($hosts as $host => $host_config) {
	if ($debug) { echo "${host}: Connecting to ${host_config['server']}\n"; }
	$res = imap_open("{" . $host_config['server'] . ":" . $host_config['port'] . "/service=imap/novalidate-cert" . "}" . $host_config['folder_inbox'], $host_config['username'], $host_config['password']);
	if (!$res) {
		if ($debug) { echo "${host}: Imap Failed to connect to ${host_config['server']}\n"; }
		continue;
	}

	// do a message loop
	$mbox = imap_check($res);
	$number_messages = $mbox->Nmsgs;
	if ($debug) { echo "${host}: Number of messages = ${number_messages}\n"; }
	if ($number_messages == 0) {
		imap_close($res);
		continue;
	}
	$range = "1:" . $number_messages;
	// now, we'll get the messages
	$messages = imap_fetch_overview($res, $range);

	foreach ($messages as $msg)
	{
		$msgno = $msg->msgno;
		if ($debug) { echo "${host}: Message number ${msgno}\n"; }
		$header = imap_rfc822_parse_headers(imap_fetchheader($res, $msgno));
		$recipient = null;
		foreach (array('to', 'cc', 'bcc') as $prop)
		{
			if (is_null($recipient) && property_exists($header, $prop))
			{
				$recipients = null;
				if ($prop == 'to')
				{
					$recipients = $header->to;
				}
				if ($prop == 'cc')
				{
					$recipients = $header->cc;
				}
				if ($prop == 'bcc')
				{
					$recipients = $header->bcc;
				}
				foreach ($recipients as $r)
				{
					if (strtolower($r->host) == strtolower($host))
					{
						$recipient = $r->mailbox;
						if ($debug) { echo "${host}: Found recipient ${recipient}\n"; }
					}
				}
			}
			if (!is_null($recipient))
			{
				break;
			}
		}
		if (is_null($recipient))
		{
			if ($debug) { echo "${host}: recipient not found, assigning to ${host_config['folder_unsorted']}\n"; }
			$recipient = $host_config['folder_unsorted'];
		}
		else
		{
			$recipient = strtolower($recipient);
		}

		// get all folders we have on this host
		$imap_folders = array();
		$server_folders = imap_listmailbox($res, "{" . $host_config['server'] . ":" . $host_config['port'] . "}", "*");
		// these come through as {server:port}mailbox, so we just clean them up a bit
		$to_remove = "{" . $host_config['server'] . ":" . $host_config['port'] . "}";
		$imap_folders = str_replace($to_remove, "", $server_folders);
		if ($debug) { echo "${host}: folders on host = " . count($imap_folders) . "\n"; }

		// unifolder!
		$host_folders = $folders[$host];
		foreach ($host_folders as $new_destination => $conditional_mailboxes)
		{
			if (is_array($conditional_mailboxes))
			{
				foreach ($conditional_mailboxes as $conditional_mailbox)
				{
					if (strtolower($conditional_mailbox) == strtolower($recipient))
					{
						$recipient = $new_destination;
						if ($debug) { echo "${host}: folder reassigned to ${recipient}\n"; }
					}
				}
			}
		}

		// can't move to the inbox
		if (strtolower($recipient) == strtolower($host_config['folder_inbox']))
		{
			if ($debug) { echo "${host}: inbox folder detected, not moving it anywhere\n"; }
			continue;
		}

		// see if we need to create this new folder
		if (!in_array($recipient, $imap_folders))
		{
			$mkfolder = imap_utf7_encode("{" . $host_config['server'] . ":" . $host_config['port'] . "}" . $recipient);
			if ($debug) { echo "${host}: creating folder ${mkfolder}\n"; }
			if (!imap_createmailbox($res, $mkfolder))
			{
				if ($debug) { echo "${host}: failed to create folder, skipping\n"; }
				continue;
			}
		}

		if (!imap_mail_move($res, $msgno, imap_utf7_encode($recipient)))
		{
			if ($debug) { echo "${host}: failed to move message ${msgno} to ${recipient}, skipping\n"; }
			continue;
		}
		if ($debug) { echo "${host}: moved ${msgno} to ${recipient}\n"; }

	}

	imap_expunge($res);
	imap_close($res);
}
