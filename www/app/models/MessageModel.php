<?php


	namespace models;

	use DB\SQL;
	use DB\SQL\Mapper;

	class MessageModel {

		public $db;

		public $mapper;

		function __construct() {

			$f3 = \Base::instance();

			$this->db     = new SQL(
				"mysql:host=" . $f3->get( 'MYSQL_HOST' ) .
				";port=" . $f3->get( 'MYSQL_PORT' ) .
				";dbname=" . $f3->get( 'MYSQL_DB' ),
				$f3->get( 'MYSQL_USER' ),
				$f3->get( 'MYSQL_PASS' ) );
			$this->mapper = new MessageSqlModel( $this->db, 'message' );

		}

		function add( $key, $message ) {

			$this->mapper->reset();
			$this->mapper->key     = $key;
			$this->mapper->message = $message;
			$this->mapper->save();

			return $this->mapper->cast();

		}


	}


	class MessageSqlModel extends Mapper {


	}