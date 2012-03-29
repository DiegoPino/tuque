<?php

require_once 'MagicProperty.php';
require_once 'FedoraDate.php';

abstract class AbstractObject extends MagicProperty {
  public $label;
  public $owner;
  public $state;
  public $id;
  public $createdDate;
  public $lastModifiedDate;

  abstract public function delete();
  abstract public function getDatastream();
  //abstract public function constructNewDatastream($id);
  //abstract public function ingestNewDatastream();
}

abstract class AbstractFedoraObject extends AbstractObject {
  protected $repository;
  protected $objectId;
  protected $objectProfile;

  public function  __construct($id, FedoraRepository $repository) {
    $this->repository = $repository;
    $this->objectId = $id;
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
  }

  protected function idMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectId;
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->id property.", E_USER_WARNING);
        throw new Exception();
        break;
    }
  }

  protected function stateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objState'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
        switch(strtolower($value)) {
          case 'd':
          case 'deleted':
            $this->objectProfile['objState'] = 'D';
            break;
          case 'a':
          case 'active':
            $this->objectProfile['objState'] = 'A';
            break;
          case 'i':
          case 'inactive':
            $this->objectProfile['objState'] = 'I';
            break;
          default:
            trigger_error("$value is not a valid value for the object->state property.", E_USER_WARNING);
            break;
        }
        break;
      case 'unset':
        trigger_error("Cannot unset the required object->state property.", E_USER_WARNING);
        break;
    }
  }

  protected function labelMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLabel'];
        break;
      case 'isset':
        if($this->objectProfile['objLabel'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objLabel']);
        }
        break;
      case 'set':
        $this->objectProfile['objLabel'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objLabel'] = '';
        break;
    }
  }

  protected function ownerMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objOwnerId'];
        break;
      case 'isset':
        if($this->objectProfile['objOwnerId'] === '') {
          return FALSE;
        }
        else {
          return isset($this->objectProfile['objOwnerId']);
        }
        break;
      case 'set':
        $this->objectProfile['objOwnerId'] = $value;
        break;
      case 'unset':
        $this->objectProfile['objOwnerId'] = '';
        break;
    }
  }
}

class NewFedoraObject extends AbstractFedoraObject {

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    $this->objectProfile = array();
    $this->objectProfile['objState'] = 'A';
    $this->objectProfile['objOwnerId'] = '';
    $this->objectProfile['objLabel'] = '';
  }

  public function delete() {
    $this->state = 'D';
  }

  public function getDatastream() {}
  public function newDatastream() {}
}

/**
 * @todo we need to support opportunistic locking here
 */
class FedoraObject extends AbstractFedoraObject {

  protected $datastreams = NULL;

  public function  __construct($id, FedoraRepository $repository) {
    parent::__construct($id, $repository);
    
    $this->objectProfile = $this->repository->api->a->getObjectProfile($id);
    $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
    $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
  }

  public function delete() {
    $this->state = 'd';
  }

  protected function populateDatastreams() {
    $this->datastreams = $this->repository->api->a->listDatastreams($this->id);
  }

  public function constructNewDatastream() {}
  public function ingestNewDatastream() {}
  public function getDatastream() {}

  public function purgeDatastream($id) {
    if(!isset($this->datastreams)) {
      $this->populateDatastreams();
    }

    if(!array_key_exists($id, $this->datastreams)) {
      return FALSE;
    }

    $this->repository->api->m->purgeDatastream($this->id, $id);
    return TRUE;
  }

  protected function stateMagicProperty($function, $value) {
    $previous_state = $this->objectProfile['objState'];
    $return = parent::stateMagicProperty($function, $value);

    if ($previous_state != $this->objectProfile['objState']) {
      $this->repository->api->m->modifyObject($this->id, array('state' => $this->objectProfile['objState']));
    }
    return $return;
  }

  protected function labelMagicProperty($function, $value) {
    $previous_label = $this->objectProfile['objLabel'];
    $return = parent::labelMagicProperty($function, $value);

    if ($previous_label != $this->objectProfile['objLabel']) {
      $this->repository->api->m->modifyObject($this->id, array('label' => $this->objectProfile['objLabel']));
    }
    return $return;
  }

  protected function ownerMagicProperty($function, $value) {
    $previous_owner = $this->objectProfile['objOwnerId'];
    $return = parent::ownerMagicProperty($function, $value);

    if ($previous_owner != $this->objectProfile['objOwnerId']) {
        $this->repository->api->m->modifyObject($this->id, array('ownerId' => $this->objectProfile['objOwnerId']));
    }
    return $return;
  }

  protected function createdDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objCreateDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->createdDate property.", E_USER_WARNING);
        break;
    }
  }

  protected function lastModifiedDateMagicProperty($function, $value) {
    switch($function) {
      case 'get':
        return $this->objectProfile['objLastModDate'];
        break;
      case 'isset':
        return TRUE;
        break;
      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly object->lastModifiedDate property.", E_USER_WARNING);
        break;
    }
  }
}