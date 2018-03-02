<?php 

/** 
* Generated at: 2018-03-02T14:16:44+01:00
* Inheritance: no
* Variants: no
* Changed by: admin (2)
* IP: ::1


Fields Summary: 
- lastname [input]
- firstname [input]
- birthdate [date]
*/ 

namespace Pimcore\Model\Object;



/**
* @method \Pimcore\Model\Object\Person\Listing getByLastname ($value, $limit = 0) 
* @method \Pimcore\Model\Object\Person\Listing getByFirstname ($value, $limit = 0) 
* @method \Pimcore\Model\Object\Person\Listing getByBirthdate ($value, $limit = 0) 
*/

class Person extends Concrete {

public $o_classId = 1;
public $o_className = "Person";
public $lastname;
public $firstname;
public $birthdate;


/**
* @param array $values
* @return \Pimcore\Model\Object\Person
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get lastname - Nachname
* @return string
*/
public function getLastname () {
	$preValue = $this->preGetValue("lastname"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->lastname;
	return $data;
}

/**
* Set lastname - Nachname
* @param string $lastname
* @return \Pimcore\Model\Object\Person
*/
public function setLastname ($lastname) {
	$this->lastname = $lastname;
	return $this;
}

/**
* Get firstname - Vorname
* @return string
*/
public function getFirstname () {
	$preValue = $this->preGetValue("firstname"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->firstname;
	return $data;
}

/**
* Set firstname - Vorname
* @param string $firstname
* @return \Pimcore\Model\Object\Person
*/
public function setFirstname ($firstname) {
	$this->firstname = $firstname;
	return $this;
}

/**
* Get birthdate - Geburtstag
* @return \Carbon\Carbon
*/
public function getBirthdate () {
	$preValue = $this->preGetValue("birthdate"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->birthdate;
	return $data;
}

/**
* Set birthdate - Geburtstag
* @param \Carbon\Carbon $birthdate
* @return \Pimcore\Model\Object\Person
*/
public function setBirthdate ($birthdate) {
	$this->birthdate = $birthdate;
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

