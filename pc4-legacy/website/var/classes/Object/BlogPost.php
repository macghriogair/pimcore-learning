<?php 

/** 
* Generated at: 2018-03-02T15:55:51+01:00
* Inheritance: no
* Variants: no
* Changed by: admin (2)
* IP: ::1


Fields Summary: 
- title [input]
- subtitle [input]
- body [wysiwyg]
- author [input]
- posted_at [datetime]
*/ 

namespace Pimcore\Model\Object;



/**
* @method \Pimcore\Model\Object\BlogPost\Listing getByTitle ($value, $limit = 0) 
* @method \Pimcore\Model\Object\BlogPost\Listing getBySubtitle ($value, $limit = 0) 
* @method \Pimcore\Model\Object\BlogPost\Listing getByBody ($value, $limit = 0) 
* @method \Pimcore\Model\Object\BlogPost\Listing getByAuthor ($value, $limit = 0) 
* @method \Pimcore\Model\Object\BlogPost\Listing getByPosted_at ($value, $limit = 0) 
*/

class BlogPost extends Concrete {

public $o_classId = 2;
public $o_className = "BlogPost";
public $title;
public $subtitle;
public $body;
public $author;
public $posted_at;


/**
* @param array $values
* @return \Pimcore\Model\Object\BlogPost
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get title - Title
* @return string
*/
public function getTitle () {
	$preValue = $this->preGetValue("title"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->title;
	return $data;
}

/**
* Set title - Title
* @param string $title
* @return \Pimcore\Model\Object\BlogPost
*/
public function setTitle ($title) {
	$this->title = $title;
	return $this;
}

/**
* Get subtitle - Subtitle
* @return string
*/
public function getSubtitle () {
	$preValue = $this->preGetValue("subtitle"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->subtitle;
	return $data;
}

/**
* Set subtitle - Subtitle
* @param string $subtitle
* @return \Pimcore\Model\Object\BlogPost
*/
public function setSubtitle ($subtitle) {
	$this->subtitle = $subtitle;
	return $this;
}

/**
* Get body - Body
* @return string
*/
public function getBody () {
	$preValue = $this->preGetValue("body"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("body")->preGetData($this);
	return $data;
}

/**
* Set body - Body
* @param string $body
* @return \Pimcore\Model\Object\BlogPost
*/
public function setBody ($body) {
	$this->body = $body;
	return $this;
}

/**
* Get author - Author
* @return string
*/
public function getAuthor () {
	$preValue = $this->preGetValue("author"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->author;
	return $data;
}

/**
* Set author - Author
* @param string $author
* @return \Pimcore\Model\Object\BlogPost
*/
public function setAuthor ($author) {
	$this->author = $author;
	return $this;
}

/**
* Get posted_at - Posted at
* @return \Carbon\Carbon
*/
public function getPosted_at () {
	$preValue = $this->preGetValue("posted_at"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->posted_at;
	return $data;
}

/**
* Set posted_at - Posted at
* @param \Carbon\Carbon $posted_at
* @return \Pimcore\Model\Object\BlogPost
*/
public function setPosted_at ($posted_at) {
	$this->posted_at = $posted_at;
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

