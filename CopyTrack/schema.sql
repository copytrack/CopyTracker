
CREATE TABLE accounts (
	acct_id mediumint(8) UNSIGNED NOT NULL auto_increment,
	creation_date int(11) UNSIGNED DEFAULT '0' NOT NULL,
	req_trans_note tinyint(1) UNSIGNED DEFAULT '0' NOT NULL,
	account_name varchar(255) DEFAULT '' NOT NULL,
	account_phone bigint(10) UNSIGNED DEFAULT '',
	account_notes mediumtext,
	copies_bw mediumint(8) DEFAULT '0' NOT NULL,
	copies_color mediumint(8) DEFAULT '0' NOT NULL,
	PRIMARY KEY (acct_id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;

CREATE TABLE transactions (
	trans_id mediumint(8) UNSIGNED NOT NULL auto_increment,
	trans_timestamp int(11) UNSIGNED DEFAULT '0' NOT NULL,
	trans_notes mediumtext,
	copies_bw mediumint(8) DEFAULT '0' NOT NULL,
	copies_color mediumint(8) DEFAULT '0' NOT NULL,
	startbal_bw mediumint(8) DEFAULT '0' NOT NULL,
	startbal_bw mediumint(8) DEFAULT '0' NOT NULL,
	oper_id mediumint(8) DEFAULT '0' NOT NULL,
	PRIMARY KEY (trans_id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;

CREATE TABLE operators (
	oper_id mediumint(8) UNSIGNED NOT NULL auto_increment,
	clerk_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
	clerk_name varchar(255) DEFAULT '<i>Name not set.</i>' NOT NULL,
	clerk_initials varchar(4) DEFAULT '--' NOT NULL,
	PRIMARY KEY (oper_id)
) CHARACTER SET `utf8` COLLATE `utf8_bin`;



$('confirmDebit').addEvent('click', function(event) {
		event.stop();
		
		
	));

	$('doDebit').addEvent('click', function(event) {
		event.stop();
		
		var deb = $('doDebit');
		
		var asyncDebit = new Request({
			method: 'post', 
			url: 'async.php',
			onRequest: function() { 
				deb.set('html', 'Saving...');
				deb.set('styles', {
					'border-left': '3px solid #ffdb13' 
				});
			},
			onComplete: function(response) { 
				if (response == 'Success')
				{
					deb.set('html', 'Saved!');
					deb.set('styles', {
						'border-left': '3px solid #39b54a'
					});
				}
				else if (response == 'Failure')
				{
					deb.set('html', 'Couldn\'t Save!');
					deb.set('styles', {
						'border-left': '3px solid #ed1c24',
						'color': '#ed1c24'
					});
				}
				else
				{
					deb.set('html', 'Error!');
					deb.set('styles', {
						'border-left': '3px solid #ed1c24',
						'color': '#ed1c24'
					});
				}
			}
		}).send(strBuild);
	});
