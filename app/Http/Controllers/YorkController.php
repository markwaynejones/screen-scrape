<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Models\User;

// below namespace is for screen scraper functionality
use Goutte\Client;

class YorkController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function main()
    {

		// get below numbers from CSV eventually
		$pinArray = array(
		'06C0455O',
		'10H0088O',
		'07H2248E',
		'05K0085E',
		'99H1056O'
		);

		$finalOutput = array();

		foreach($pinArray as $pin)
		{

			$finalOutput[$pin] = $this->retrieveUserData($pin);
			
		}

		// print_r($finalOutput);
		// die;		

		
		foreach($finalOutput as $pin => $data)
		{

			$user = new User;

        	$user->full_name = $data['userFullName'];
        	$user->location = $data['userLocation'];
        	$user->expiry_date = $data['userExpiryDate'];

        	$user->save();			
			
		}
		

		return view('york-pins')->with('finalOutput', $finalOutput);

    }

	public function retrieveUserData($pin) {

		$client = new Client();

		$crawler = $client->request('GET', 'https://www.nmc.org.uk/registration/search-the-register/');

		$form = $crawler->selectButton('Search')->form();

		$crawler = $client->submit($form, array('PinNumber' => $pin));

		$crawler = $client->click($crawler->filter('.more-link')->link());

		$userFullName = $crawler->filter('div.practitioner-meta dl dd')->slice(0,1);
		$userFullName = $userFullName->text();

		$userLocation = $crawler->filter('div.practitioner-meta dl dd')->slice(1,1);
		$userLocation = $userLocation->text();

		$userExpiryDate = $crawler->filter('div.practitioner-meta dl dd')->slice(2,1);
		$userExpiryDate = $userExpiryDate->text();

		$userFullName = $crawler->filter('div.practitioner-meta dl dd')->slice(0,1);
		$userFullName = $userFullName->text();

		$registerEntries = array();

		$crawler->filter('table:nth-of-type(1) tr td')->each(function ($node) use(&$registerEntries) {
		    
		    $registerEntries[] = $node->text();

		});

		$registerQualifications = array();

		$crawler->filter('table:nth-of-type(2) tr td')->each(function ($node) use(& $registerQualifications) {

		    $registerQualifications[] = $node->text();

		});

		$finalRegisterEntries['entry-name'] = trim($registerEntries[0]);
		$finalRegisterEntries['start-date'] = trim($registerEntries[1]);

		$finalRegisterQualifications['qualification-name'] = trim($registerQualifications[0]);
		$finalRegisterQualifications['start-date'] = trim($registerQualifications[1]);

		$fullDataArray['userFullName'] = $userFullName;

		$fullDataArray['userLocation'] = $userLocation;
		$fullDataArray['userExpiryDate'] = $userExpiryDate;

		$fullDataArray['userFullName'] = $userFullName;

		$fullDataArray['registerEntries'] = $finalRegisterEntries;

		$fullDataArray['registerQualifications'] = $finalRegisterQualifications;

		return $fullDataArray;

	}


}
