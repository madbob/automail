AutoMail
========

This package wraps the SMTP, IMAP and POP3 autoconfiguration API described by
Mozilla.

For more informations, [read the Mozilla documentation](https://developer.mozilla.org/en-US/docs/Mozilla/Thunderbird/Autoconfiguration).

# Installation

`composer require madbob/automail`

# Usage

```php
require 'vendor/autoload.php';

use AutoMail\AutoMail;
use AutoMail\NotFoundException;

try {
	/*
		Pass your mail address to AutoMail::discover() to obtain an array with
		all available configurations, both for incoming and outgoing messages
	*/
	$configuration = AutoMail::discover('yourmailaddress@libero.it');

	print_r($configuration);

	/*
		[
			'incoming' => [
				[
					'protocol' => 'IMAP',
					'hostname' => 'imapmail.libero.it',
					'port' => 993,
					'socketType' => 'SSL',
					'authentication' => 'password-cleartext',
					'username' => 'yourmailaddress@libero.it'
				],
				[
					'protocol' => 'POP3',
					'hostname' => 'popmail.libero.it',
					'port' => 995,
					'socketType' => 'SSL',
					'authentication' => 'password-cleartext',
					'username' => 'yourmailaddress@libero.it'
				]
			],
			'outgoing' => [
				[
					'protocol' => 'SMTP',
					'hostname' => 'smtp.libero.it',
					'port' => 465,
					'socketType' => 'SSL',
					'authentication' => 'password-cleartext',
					'username' => 'yourmailaddress@libero.it'
				]
			]
		]
	*/
}
catch(NotFoundException $e) {
	echo $e->getMessage();
}
```

# License

This code is free software, licensed under the The GNU General Public License
version 3 (GPLv3). See the LICENSE.md file for more details.

Copyright (C) 2017 Roberto Guido <bob@linux.it>

