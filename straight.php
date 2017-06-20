<?php

// Include the Composer autoloader
require 'vendor/autoload.php';

use League\Csv\Reader;
use Carbon\Carbon;

$climate = new League\CLImate\CLImate;

$climate->flank('Converting ABWF\'s Giftworks smartlists to JSON.');
$climate->br();


$possibleConversions = ['members', 'addresses', 'donations', 'eastwest', 'membership_types', 'projects', 'member_project'];

$input = $climate->input('Please select the data to convert:');
$input->accept($possibleConversions, true);

$conversion = $input->prompt();

$out = array();


switch (strtolower($conversion)) {
	case 'member_project':
		$columnsToKeep = ['Id' => 'id', 'FKDonorId' => 'member_id', 'FKJobId' => 'project_id', 'Hours' => 'hours_total'];

		$dateColumns   = [];
		$idColumn      = 'Id';
		$file          = 'volunteer_volunteer_hours';
		break;
	case 'projects':
		$columnsToKeep = ['Id' => 'project_id', 'Title' => 'name', 'StartDate' => 'date_start', 'EndDate' => 'date_end'];

		$dateColumns   = ['date_start', 'date_end'];
		$idColumn      = 'Id';
		$file          = 'Projects';
		break;
	case 'members':
		$columnsToKeep = ['Id' => 'member_id', 'First Name' => 'first_name', 'Last Name' => 'last_name', 'Organization' => 'company_name'];

		$dateColumns   = [];
		$idColumn      = 'Id';
		$file          = 'Donors';
		break;
	case 'membership_types':
		$columnsToKeep = ['Id' => 'member_id', 'Groups' => 'membership_types_names'];

		$dateColumns   = [];
		$idColumn      = 'Id';
		$file          = 'Donors';
		break;
	case 'addresses':
		$columnsToKeep = ['Id' => 'member_id', 'Phone' => 'phone_primary', 'Email' => 'email_primary', 'Phone 2' => 'phone_secondary', 'Email 2' => 'email_secondary', 'Address Line 1' => 'address_line1', 'Address Line 2' => 'address_line2', 'City' => 'town', 'State' => 'state_abbreviation', 'ZIP/Postal Code' => 'zip', 'Country' => 'country_name'];

		$dateColumns   = [];
		$idColumn      = 'Id';
		$file          = 'Donors';
		break;
	case 'eastwest':
		$columnsToKeep = ['Country' => 'country_name', 'ZIP' => 'zip', 'State short' => 'state_abbreviation', 'East/West' => 'east_west', 'Town' => 'name'];

		$dateColumns   = [];
		$idColumn      = 'ZIP';
		$file          = 'EastWestAB';
		break;
	case 'donations':
		$columnsToKeep = ['Payment Id' => 'id', 'Donor Id' => 'member_id', 'Donation Date' => 'donated_at', 'Received' => 'amount', 'Payment Type' => 'payment_json_type', 'Check Number' => 'paymnet_json_check_number', 'Fund' => 'fund_name', 'Campaign' => 'campaign_name'];

		$dateColumns   = ['donated_at'];
		$idColumn      = 'Payment Id';
		$file          = 'Donations';
		break;
	default:
		die('incorrect data choice');
		break;
}

$csv    = Reader::createFromPath('csv_in/'.$file.'.csv');
$header = array_filter($csv->fetchOne());
$data   = $csv->fetchAssoc($header);
unset($data[0]);

foreach ($data as $line => $values) {
	foreach ($columnsToKeep as $column => $field) {
		$out[$values[$idColumn]][$field] = $values[$column] ? $values[$column] : NULL;
		if (in_array($field, $dateColumns) && !is_null($values[$column]) && $values[$column] != '') {
			$out[$values[$idColumn]][$field] = Carbon::createFromFormat('n/j/Y', $values[$column])->toDateString();
		}
	}
}


$input = $climate->confirm('Overwrite exisitng file?');
if ($input->confirmed()) {
	file_put_contents('json_out/'.$conversion.'.json', json_encode($out));
} else {
	file_put_contents('json_out/'.$conversion.date('r').'.json', json_encode($out));
}
