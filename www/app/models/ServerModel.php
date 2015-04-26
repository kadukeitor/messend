<?php


	namespace models;

	use DB\SQL;
	use DB\SQL\Mapper;

	class ServerModel {

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
			$this->mapper = new ServerSqlModel( $this->db, 'server' );

		}

		function register( $server, $user ) {


			if ( $this->mapper->count( array( 'server=? AND user=?', $server, $user ) ) ) {

				$this->mapper->load( array( 'server=? AND user=?', $server, $user ) );


			} else {

				$this->mapper->key    = bin2hex( openssl_random_pseudo_bytes( 8 ) );
				$this->mapper->server = $server;
				$this->mapper->user   = $user;
				$this->mapper->save();

			}


			return $this->mapper->cast();

		}

	}


	class ServerSqlModel extends Mapper {


	}