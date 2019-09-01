# smart-imap

IMAP mail need not sit in the inbox...

# Purpose

I don't think IMAP mail should sit in the inbox. Instead, I think that all mail should be sorted as the following:

Where emails are local-part@domain
1. Extract out local-part
2. Create IMAP folder named local-part if it doesn't exist
3. Move message from Inbox to local-part

For emails that do not have a local-part, or where the items in the local-part do not match the domain, they should be moved to the UNSORTED folder.

For emails in a special list, they can automatically go to SPAM.

You should be able to also make emails from local-part-1 and local-part-2 both go to local-part-folder (much like SPAM emails).

# Recommended Cron

    */1 * * * * /usr/bin/php -f smart-imap.php
