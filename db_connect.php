<?php

session_start();

class dbconnect
{
  private $_localhost = '216.10.241.228';
  private $_user = 'uniquvhx_arpit';
  private $_password = 'arpit@1234567890';

  // Databse of 2024-25 change database
  private $_dbname = 'uniquvhx_whatsapp';

  protected $connection;
  public function __construct()
  {
    if (!isset($this->connection)) {

      $this->connection = new mysqli($this->_localhost, $this->_user, $this->_password, $this->_dbname);
    }
    return $this->connection;
  }
}