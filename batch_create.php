<?php
/*
  You must generate your own Developer API key.
	Replace the string <YOUR_API_KEY_HERE> with your api key below see the ****....
	You must also put a ticket in to request permision to add
	sub accounts.  Otherwise this won't work.
*/

// Model classes to make JSON easy
class User {
	public $username = "";
	public $allowParentProjects = false;
	public $email = "";
}
class Project {
	public $name = "";
	public $type = "XPDev";
	public $abbreviatedName = "";
	public $publicProject = false;
}
class UserPermision {
	public $permission = "Admin";
	public $username = "";
	public $projectId;
}
// Controllers
class Request {
	function makeRequest($method, $data)
	{
		// ******************************************************
		// You must replace your API token here ie that long string of random chars
		$api = 'X-XPDevToken: <YOUR_API_KEY_HERE>';
		$data_string = json_encode($data);
		$ch = curl_init('https://xp-dev.com/api/v1/' . $method);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
		    'Content-Type: application/json',
		    'Accept: application/json',
		     $api  
		    )                                                                       
		);                                                                                                                   
		
		return curl_exec($ch);
	}

}
class Group {

	public $users = array();
	public $name = "";
	
	function create()
	{
		echo "Creating accounts for group" . $this->name . "\n";
		$requester = new Request();
		// create the accounts for the users in the group
		foreach ($this->users as &$user)
		{
			$result = $requester->makeRequest('user/childuser', $user);
			$decoded = json_decode($result);
			if (array_key_exists("error", $decoded))
			{
				// So if the result is an error message then I will try again by adding cs321 to the end
				echo "Error - Username " . $user->username . " already taken.  Trying adding cs321 to end.\n";
				$user->username = $user->username . "cs321";
				$result = $requester->makeRequest('user/childuser', $user);
			}
		}

		// create the project
		$proj = new Project();
		$proj->name = $this->name;
		$proj->abbreviatedName = $this->name;
		$createdProj = $requester->makeRequest('projects/', $proj);
		if (array_key_exists("error", json_decode($createdProj)))
		{
			$proj->abbreviatedName = $this->name . "cs321";
			$createdProj = $requester->makeRequest('projects/', $proj);
		}
		print_r($createdProj); // print the data returned by xp-dev
		$decoded = json_decode($createdProj);
		$projID = $decoded->response->id; // get the project id in order to add users to project
		// add the users to the project id as administrators
		foreach ($this->users as &$user)
		{
			$perm = new UserPermision();
			$perm->username = $user->username;
			$perm->projectId = $projID;
			print_r($requester->makeRequest('projectpermissions/', $perm));
		}			
	}
}

// Get the data  Could create a nice html page and post these files...
$names = "./names.lsv"; // the names of the projects separated by newlines
$nameLines = file($names);
$emails = "./emails.lsv"; // the email addresses of the groups one on each line. seperate groups by a blank line.
$emailLines = file($emails);
// create the groups
$groups = array();
$curGroup = 0;
foreach($nameLines as $line_num => $name)
{
	$groups[] = new Group();
	$groups[$curGroup]->name = str_replace("\n", "",$name);
	echo $groups[$curGroup]->name;
	echo "<br>";
	$curGroup = $curGroup + 1;
}
// add the users to the groups
$curGroup = 0;
foreach($emailLines as $line_num => $email)
{
	if (strcmp($email, "") == 1)// next group since found a blank line
	{
		$curGroup += 1;
	}
	else
	{
		$user = new User();
		// extract the username
		$parts = explode("@", $email);
		$user->username = str_replace("\n", "", $parts[0]);
		$user->email = str_replace("\n", "", $email);
		$groups[$curGroup]->users[] = $user;
	}
}
// now loop over the groups and create the projects!!!
foreach ($groups as &$curGroup)
{
	$curGroup->create();
}

?>
